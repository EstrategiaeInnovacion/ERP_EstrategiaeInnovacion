<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Asistencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'empleado_id',
        'empleado_no',
        'nombre',
        'fecha',
        'entrada',
        'salida',
        'checadas',
        'horas_trabajadas',
        'tipo_registro',  // 'asistencia', 'falta', 'vacaciones', etc.
        'es_retardo',
        'es_justificado',
        'comentarios',
    ];

    protected $casts = [
        'fecha' => 'date',
        'checadas' => 'array',
        'es_retardo' => 'boolean',
        'es_justificado' => 'boolean',
    ];

    // Relación
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES (Filtros Reutilizables) - AQUÍ ESTÁ EL MEJOR DISEÑO
    |--------------------------------------------------------------------------
    */

    /**
     * Filtra por rango de fechas.
     */
    public function scopeEnPeriodo(Builder $query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }

    /**
     * Filtra asistencias puntuales o justificadas.
     */
    public function scopePuntuales(Builder $query)
    {
        return $query->whereNotNull('entrada')
                     ->whereNotNull('salida')
                     ->where(function ($q) {
                         $q->where('es_retardo', false)
                           ->orWhere('es_justificado', true);
                     });
    }

    /**
     * Filtra retardos que NO han sido justificados (solo asistencias).
     */
    public function scopeRetardosInjustificados(Builder $query)
    {
        return $query->where('tipo_registro', 'asistencia')
                     ->where('es_retardo', true)
                     ->where('es_justificado', false);
    }

    /**
     * Filtra solo faltas (tipo_registro = 'falta').
     */
    public function scopeSoloFaltas(Builder $query)
    {
        return $query->where('tipo_registro', 'falta');
    }

    /**
     * Filtra registros de asistencia en buen estado (sin retardo injustificado).
     */
    public function scopeAsistenciasOk(Builder $query)
    {
        return $query->where('tipo_registro', 'asistencia')
                     ->where(function ($q) {
                         $q->where('es_retardo', false)
                           ->orWhere('es_justificado', true);
                     });
    }

    /**
     * Filtra registros laborales (excluye vacaciones, incapacidades, permisos, descansos).
     */
    public function scopeLaborales(Builder $query)
    {
        return $query->whereIn('tipo_registro', ['asistencia', 'falta', 'incompleto']);
    }

    /**
     * Búsqueda inteligente por nombre o número.
     */
    public function scopeBuscar(Builder $query, $termino)
    {
        if ($termino) {
            return $query->where(function ($q) use ($termino) {
                $q->where('nombre', 'like', "%{$termino}%")
                  ->orWhere('empleado_no', 'like', "%{$termino}%");
            });
        }
        return $query;
    }
}