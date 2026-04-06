<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EvaluacionVentana extends Model
{
    protected $table = 'evaluacion_ventanas';

    protected $fillable = [
        'nombre',
        'fecha_apertura',
        'fecha_cierre',
        'activo',
        'creado_por',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre'   => 'date',
        'activo'         => 'boolean',
    ];

    /**
     * Devuelve true si hoy está dentro de la ventana activa más reciente.
     */
    public static function estaAbierta(): bool
    {
        $hoy = Carbon::today();

        return self::where('activo', true)
            ->where('fecha_apertura', '<=', $hoy)
            ->where('fecha_cierre', '>=', $hoy)
            ->exists();
    }

    /**
     * Retorna la ventana activa vigente o null.
     */
    public static function ventanaActual(): ?self
    {
        $hoy = Carbon::today();

        return self::where('activo', true)
            ->where('fecha_apertura', '<=', $hoy)
            ->where('fecha_cierre', '>=', $hoy)
            ->latest()
            ->first();
    }
}
