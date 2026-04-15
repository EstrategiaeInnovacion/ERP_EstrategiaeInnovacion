<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proyecto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
        'usuario_id',
        'fecha_inicio',
        'fecha_fin',
        'fecha_fin_real',
        'recurrencia',
        'notas',
        'archivado',
        'finalizado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_fin_real' => 'date',
        'archivado' => 'boolean',
        'finalizado' => 'boolean',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'proyecto_usuarios', 'proyecto_id', 'usuario_id');
    }

    public function actividades()
    {
        return $this->hasMany(Activity::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('archivado', false);
    }

    public function scopeArchivados($query)
    {
        return $query->where('archivado', true);
    }

    public function siguienteFechaJunta($desde = null)
    {
        $inicio = $desde ? Carbon::parse($desde) : Carbon::parse($this->fecha_inicio);
        $hoy = Carbon::now();

        while ($inicio->lte($hoy)) {
            switch ($this->recurrencia) {
                case 'semanal':
                    $inicio->addWeek();
                    break;
                case 'quincenal':
                    $inicio->addDays(15);
                    break;
                case 'mensual':
                    $inicio->addMonth();
                    break;
            }
        }

        return $inicio;
    }

    public function estaActivo()
    {
        $hoy = Carbon::now()->toDateString();

        return ! $this->archivado && $this->fecha_fin >= $hoy;
    }

    public function metricas()
    {
        $actividades = $this->actividades()->get();

        $total = $actividades->count();
        $completadas = $actividades->whereIn('estatus', ['Completado', 'Completado con retardo'])->count();
        $enProceso = $actividades->whereIn('estatus', ['En proceso', 'Planeado', 'Por Aprobar', 'Por Validar'])->count();
        $rechazadas = $actividades->where('estatus', 'Rechazado')->count();

        // Eficiencia: actividades completadas a tiempo
        $aTiempo = $actividades->where('porcentaje', 100)->count();
        $conRetraso = $actividades->where('estatus', 'Completado con retardo')->count();

        // Promedio de eficiencia
        $promedioEficiencia = $actividades->whereNotNull('porcentaje')->avg('porcentaje') ?? 0;

        // Días promedio
        $diasPlaneados = $actividades->whereNotNull('metrico')->avg('metrico') ?? 0;
        $diasReales = $actividades->whereNotNull('resultado_dias')->avg('resultado_dias') ?? 0;

        return [
            'total' => $total,
            'completadas' => $completadas,
            'en_proceso' => $enProceso,
            'rechazadas' => $rechazadas,
            'a_tiempo' => $aTiempo,
            'con_retraso' => $conRetraso,
            'promedio_eficiencia' => round($promedioEficiencia, 1),
            'dias_planeados' => round($diasPlaneados, 1),
            'dias_reales' => round($diasReales, 1),
            'porcentaje_completado' => $total > 0 ? round(($completadas / $total) * 100, 1) : 0,
        ];
    }
}
