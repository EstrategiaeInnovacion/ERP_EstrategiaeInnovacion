<?php

namespace App\Models\Sistemas_IT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Carbon\Carbon;

class MaintenanceBlockedSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_start',
        'date_end',
        'time_slot',
        'reason',
        'blocked_by',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
    ];

    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * Verifica si un horario específico está bloqueado
     */
    public static function isBlocked(string $date, ?string $timeSlot = null): bool
    {
        // Normalizar fecha
        $date = Carbon::parse($date)->format('Y-m-d');
        
        // Normalizar time_slot a HH:MM:SS para comparación con la DB
        $timeSlotNormalized = $timeSlot ? substr($timeSlot, 0, 5) . ':00' : null;
        
        return self::query()
            ->where(function ($query) use ($date) {
                // Fecha dentro del rango o fecha exacta
                $query->where(function ($q) use ($date) {
                    // Bloqueo de fecha única (sin date_end)
                    $q->where(function ($sub) use ($date) {
                        $sub->whereDate('date_start', $date)
                            ->whereNull('date_end');
                    })
                    // O bloqueo de rango (con date_end)
                    ->orWhere(function ($sub) use ($date) {
                        $sub->whereDate('date_start', '<=', $date)
                            ->whereDate('date_end', '>=', $date);
                    });
                });
            })
            ->where(function ($q) use ($timeSlotNormalized) {
                // Bloqueo de día completo (time_slot es null)
                $q->whereNull('time_slot');
                
                // O bloqueo de hora específica que coincida
                if ($timeSlotNormalized) {
                    $q->orWhere('time_slot', $timeSlotNormalized);
                }
            })
            ->exists();
    }

    /**
     * Obtiene los bloqueos para un rango de fechas
     */
    public static function getBlockedForRange(string $startDate, string $endDate): array
    {
        // Normalizar fechas
        $startDate = Carbon::parse($startDate)->format('Y-m-d');
        $endDate = Carbon::parse($endDate)->format('Y-m-d');
        
        $blocks = self::query()
            ->where(function ($query) use ($startDate, $endDate) {
                // Bloqueo que inicia dentro del rango
                $query->whereDate('date_start', '>=', $startDate)
                      ->whereDate('date_start', '<=', $endDate);
            })
            ->orWhere(function ($query) use ($startDate, $endDate) {
                // Bloqueo con rango que intersecta
                $query->whereNotNull('date_end')
                      ->whereDate('date_start', '<=', $endDate)
                      ->whereDate('date_end', '>=', $startDate);
            })
            ->get();

        $blockedSlots = [];
        
        foreach ($blocks as $block) {
            $start = Carbon::parse($block->date_start);
            $end = $block->date_end ? Carbon::parse($block->date_end) : $start->copy();
            
            // Iterar solo dentro del rango solicitado
            $rangeStart = Carbon::parse($startDate);
            $rangeEnd = Carbon::parse($endDate);
            
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                // Solo incluir si está dentro del rango solicitado
                if ($date->lt($rangeStart) || $date->gt($rangeEnd)) {
                    continue;
                }
                
                $dateStr = $date->format('Y-m-d');
                
                if ($block->time_slot) {
                    // Bloqueo de hora específica
                    if (!isset($blockedSlots[$dateStr]) || $blockedSlots[$dateStr] !== 'all') {
                        if (!isset($blockedSlots[$dateStr])) {
                            $blockedSlots[$dateStr] = [];
                        }
                        if (is_array($blockedSlots[$dateStr])) {
                            // Formatear time_slot a HH:MM
                            $timeSlot = substr($block->time_slot, 0, 5);
                            $blockedSlots[$dateStr][] = $timeSlot;
                        }
                    }
                } else {
                    // Día completo bloqueado
                    $blockedSlots[$dateStr] = 'all';
                }
            }
        }

        return $blockedSlots;
    }
}
