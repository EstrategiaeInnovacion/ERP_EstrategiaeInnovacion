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
        $dateCarbon = Carbon::parse($date);
        
        return self::query()
            ->where(function ($query) use ($dateCarbon, $timeSlot) {
                // Bloqueo por fecha específica
                $query->where(function ($q) use ($dateCarbon, $timeSlot) {
                    $q->whereDate('date_start', '<=', $dateCarbon)
                      ->where(function ($subQ) use ($dateCarbon) {
                          $subQ->whereNull('date_end')
                               ->whereDate('date_start', $dateCarbon);
                      })
                      ->orWhere(function ($subQ) use ($dateCarbon) {
                          $subQ->whereNotNull('date_end')
                               ->whereDate('date_end', '>=', $dateCarbon)
                               ->whereDate('date_start', '<=', $dateCarbon);
                      });
                })
                // Filtrar por horario si se especifica
                ->where(function ($q) use ($timeSlot) {
                    if ($timeSlot) {
                        $q->whereNull('time_slot') // Bloqueo de todo el día
                          ->orWhere('time_slot', $timeSlot);
                    } else {
                        $q->whereNull('time_slot'); // Solo bloqueos de día completo
                    }
                });
            })
            ->exists();
    }

    /**
     * Obtiene los bloqueos para un rango de fechas
     */
    public static function getBlockedForRange(string $startDate, string $endDate): array
    {
        $blocks = self::query()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date_start', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->whereNotNull('date_end')
                            ->where('date_start', '<=', $endDate)
                            ->where('date_end', '>=', $startDate);
                      });
            })
            ->get();

        $blockedSlots = [];
        
        foreach ($blocks as $block) {
            $start = Carbon::parse($block->date_start);
            $end = $block->date_end ? Carbon::parse($block->date_end) : $start->copy();
            
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                
                if (!isset($blockedSlots[$dateStr])) {
                    $blockedSlots[$dateStr] = [];
                }
                
                if ($block->time_slot) {
                    $blockedSlots[$dateStr][] = $block->time_slot;
                } else {
                    // Día completo bloqueado
                    $blockedSlots[$dateStr] = 'all';
                }
            }
        }

        return $blockedSlots;
    }
}
