<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Activity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'asignado_por',     // <--- NUEVO CAMPO AGREGADO
        'area',
        'cliente',
        'tipo_actividad',
        'nombre_actividad',
        'comentarios',
        'fecha_inicio',
        'fecha_compromiso',
        'fecha_final',
        'prioridad',
        'estatus',
        'metrico',
        'resultado_dias',
        'porcentaje',
        'evidencia_path',
        'motivo_rechazo',
        'hora_inicio_programada',
        'hora_fin_programada',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_compromiso' => 'datetime',
        'fecha_final' => 'datetime',
        'metrico' => 'integer',
        'hora_inicio_programada' => 'datetime:H:i',
        'hora_fin_programada' => 'datetime:H:i',
    ];

    // --- RELACIONES ---
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Relación opcional para saber quién la asignó
    public function asignador()
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }

    public function historial()
    {
        return $this->hasMany(ActivityHistory::class)->orderBy('created_at', 'desc');
    }

    // --- LÓGICA AUTOMÁTICA (Calculadora de Fechas) ---
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($activity) {
            
            $inicio = $activity->fecha_inicio ? Carbon::parse($activity->fecha_inicio)->startOfDay() : null;
            $compromiso = $activity->fecha_compromiso ? Carbon::parse($activity->fecha_compromiso)->startOfDay() : null;
            $final = $activity->fecha_final ? Carbon::parse($activity->fecha_final)->startOfDay() : null;
            $hoy = Carbon::now()->startOfDay();

            // 1. Métrico (Días Planeados)
            if ($compromiso && $inicio) {
                $dias = (int) $inicio->diffInDays($compromiso, false);
                $activity->metrico = max(0, $dias);
            }

            // 2. Resultado (Días Reales)
            if ($final && $inicio) {
                $activity->resultado_dias = (int) $inicio->diffInDays($final, false);
            } else {
                $activity->resultado_dias = null;
            }

            // 3. Porcentaje (Eficiencia)
            $activity->porcentaje = 0; 
            if ($final && $activity->resultado_dias !== null && $activity->metrico > 0) {
                if ($final->lessThanOrEqualTo($compromiso)) {
                    $activity->porcentaje = 100;
                } else {
                    if ($activity->resultado_dias > 0) {
                        $calc = ($activity->metrico / $activity->resultado_dias) * 100;
                        $activity->porcentaje = round($calc, 2);
                    }
                }
            } elseif ($final && $final->lessThanOrEqualTo($compromiso)) {
                $activity->porcentaje = 100;
            }

            // 4. Estatus Automático
            if ($activity->fecha_final) {
                if ($final->gt($compromiso)) {
                    $activity->estatus = 'Completado con retardo';
                } else {
                    $activity->estatus = 'Completado';
                }
            } else {
                if ($compromiso && $hoy->gt($compromiso)) {
                    $activity->estatus = 'Retardo';
                } else {
                    if ($activity->estatus === 'Retardo') {
                        $activity->estatus = 'En proceso';
                    }
                    if (empty($activity->estatus)) {
                        $activity->estatus = 'En blanco';
                    }
                }
            }
        });
    }
}