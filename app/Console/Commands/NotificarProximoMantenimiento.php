<?php

namespace App\Console\Commands;

use App\Models\Sistemas_IT\ComputerProfile;
use App\Models\User;
use App\Notifications\ProximoMantenimientoNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotificarProximoMantenimiento extends Command
{
    protected $signature = 'it:notificar-proximo-mantenimiento
                            {--dias=7 : Días de anticipación para avisar antes del mantenimiento}
                            {--dry-run : Simular sin enviar notificaciones}';

    protected $description = 'Notifica diariamente a los usuarios (o al equipo IT) sobre equipos con mantenimiento próximo.';

    public function handle(): int
    {
        $dias     = (int) $this->option('dias');
        $dryRun   = (bool) $this->option('dry-run');
        $hoy      = Carbon::today('America/Mexico_City');
        $horizonte = $hoy->copy()->addDays($dias);

        $this->info("Buscando equipos con mantenimiento entre {$hoy->toDateString()} y {$horizonte->toDateString()}...");

        // Perfiles cuyo próximo mantenimiento cae dentro del horizonte y aún no está en el pasado
        $perfiles = ComputerProfile::query()
            ->whereNotNull('next_maintenance_at')
            ->whereDate('next_maintenance_at', '>=', $hoy)
            ->whereDate('next_maintenance_at', '<=', $horizonte)
            ->with(['equipoAsignado.user'])
            ->get();

        if ($perfiles->isEmpty()) {
            $this->info('No hay equipos con mantenimiento próximo en ese rango.');
            return Command::SUCCESS;
        }

        $enviados = 0;
        $omitidos = 0;

        foreach ($perfiles as $profile) {
            // Evitar duplicar en el mismo día
            if ($profile->maintenance_reminder_sent_at?->eq($hoy)) {
                $this->line("  SKIP  [{$profile->identifier}] — ya se notificó hoy.");
                $omitidos++;
                continue;
            }

            $usuario = $profile->equipoAsignado?->user ?? null;

            if ($usuario) {
                // Notificar al usuario actual del equipo
                if (!$dryRun) {
                    $usuario->notify(new ProximoMantenimientoNotification($profile, false));
                }
                $this->line("  OK    [{$profile->identifier}] → {$usuario->email}");
            } else {
                // Fallback: notificar a todos los admins IT
                $adminsIT = $this->getAdminsIT();

                if ($adminsIT->isEmpty()) {
                    $this->warn("  WARN  [{$profile->identifier}] — sin usuario asignado y sin admins IT encontrados.");
                    continue;
                }

                foreach ($adminsIT as $admin) {
                    if (!$dryRun) {
                        $admin->notify(new ProximoMantenimientoNotification($profile, true));
                    }
                    $this->line("  OK    [{$profile->identifier}] → admin IT: {$admin->email}");
                }
            }

            // Marcar la fecha del último recordatorio
            if (!$dryRun) {
                $profile->update(['maintenance_reminder_sent_at' => $hoy->toDateString()]);
            }

            $enviados++;
        }

        $this->info("Resumen: {$enviados} notificación(es) enviadas, {$omitidos} omitidas (ya enviadas hoy).");

        return Command::SUCCESS;
    }

    /**
     * Obtiene los usuarios admin del área de Sistemas / posición TI o IT.
     */
    private function getAdminsIT()
    {
        return User::where('role', 'admin')
            ->where('status', 'approved')
            ->whereHas('empleado', function ($q) {
                $q->where('area', 'Sistemas')
                  ->orWhereIn('posicion', ['TI', 'IT']);
            })
            ->get();
    }
}
