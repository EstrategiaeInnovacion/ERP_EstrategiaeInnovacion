<?php

namespace App\Console\Commands;

use App\Models\Sistemas_IT\MaintenanceSlot;
use App\Models\Sistemas_IT\Ticket;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReleaseExpiredMaintenanceSlots extends Command
{
    protected $signature = 'maintenance:release-expired-slots 
                            {--dry-run : Mostrar qué se haría sin hacer cambios}
                            {--force : Forzar liberación incluso de fechas futuras}';

    protected $description = 'Libera slots de mantenimiento de tickets cancelados o cuya fecha ya pasó';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $nowMexico = Carbon::now('America/Mexico_City');
        
        $this->info('🔍 Buscando slots de mantenimiento para liberar...');
        $this->info("📅 Fecha actual: {$nowMexico->format('Y-m-d H:i:s')} (México)");
        
        if ($dryRun) {
            $this->warn('🧪 MODO DRY-RUN: No se harán cambios reales');
        }
        
        // Buscar tickets de mantenimiento cancelados que aún tienen slots ocupados
        $canceledTickets = Ticket::where('tipo_problema', 'mantenimiento')
            ->where('estado', 'cerrado')
            ->where('closed_by_user', true)
            ->whereNotNull('maintenance_slot_id')
            ->with('maintenanceSlot')
            ->get();
            
        $releasedCount = 0;
        $skippedCount = 0;
        
        foreach ($canceledTickets as $ticket) {
            $slot = $ticket->maintenanceSlot;
            
            if (!$slot) {
                $this->line("⚠️  Ticket {$ticket->folio}: Slot no encontrado");
                continue;
            }
            
            $slotDateTime = $slot->start_date_time;
            $isPastDate = $slotDateTime->lessThanOrEqualTo($nowMexico);
            $hasBookedCount = $slot->booked_count > 0;
            
            // Condiciones para liberar
            $shouldRelease = $hasBookedCount && ($isPastDate || $force);
            
            if ($shouldRelease) {
                $this->line("✅ Ticket {$ticket->folio}: Liberando slot {$slot->id}");
                $this->line("   📅 Fecha: {$slot->date->format('d/m/Y')} {$slot->start_time}");
                $this->line("   📊 Booked count antes: {$slot->booked_count}");
                
                if (!$dryRun) {
                    $slot->decrement('booked_count');
                    $slot->refresh();
                }
                
                $this->line("   📊 Booked count después: " . ($dryRun ? ($slot->booked_count - 1) : $slot->booked_count));
                $releasedCount++;
                
            } else {
                $reason = [];
                if (!$hasBookedCount) $reason[] = 'sin reservas';
                if (!$isPastDate && !$force) $reason[] = 'fecha futura';
                
                $this->line("⏭️  Ticket {$ticket->folio}: Omitido (" . implode(', ', $reason) . ")");
                $this->line("   📅 Fecha: {$slot->date->format('d/m/Y')} {$slot->start_time}");
                $this->line("   📊 Booked count: {$slot->booked_count}");
                $skippedCount++;
            }
        }
        
        // Buscar slots huérfanos (con booked_count > 0 pero sin tickets activos)
        $this->newLine();
        $this->info('🔍 Buscando slots huérfanos...');
        
        $orphanSlots = MaintenanceSlot::where('booked_count', '>', 0)
            ->whereDoesntHave('bookings')
            ->get();
            
        $orphanCount = 0;
        
        foreach ($orphanSlots as $slot) {
            $slotDateTime = $slot->start_date_time;
            $isPastDate = $slotDateTime->lessThanOrEqualTo($nowMexico);
            
            if ($isPastDate || $force) {
                $this->line("🧹 Slot huérfano {$slot->id}: Limpiando booked_count");
                $this->line("   📅 Fecha: {$slot->date->format('d/m/Y')} {$slot->start_time}");
                $this->line("   📊 Booked count antes: {$slot->booked_count}");
                
                if (!$dryRun) {
                    $slot->update(['booked_count' => 0]);
                }
                
                $this->line("   📊 Booked count después: 0");
                $orphanCount++;
            }
        }
        
        // Resumen
        $this->newLine();
        $this->info('📋 RESUMEN:');
        $this->line("✅ Slots liberados de tickets cancelados: {$releasedCount}");
        $this->line("⏭️  Tickets omitidos: {$skippedCount}");
        $this->line("🧹 Slots huérfanos limpiados: {$orphanCount}");
        
        if ($dryRun && ($releasedCount > 0 || $orphanCount > 0)) {
            $this->warn('Para ejecutar los cambios reales, ejecuta: php artisan maintenance:release-expired-slots');
        }
        
        return 0;
    }
}