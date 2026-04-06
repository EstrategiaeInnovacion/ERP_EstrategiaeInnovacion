<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PlaneacionVentana extends Model
{
    protected $table = 'planeacion_ventanas';

    protected $fillable = [
        'dia_semana',
        'hora_apertura',
        'hora_cierre',
        'activo',
        'creado_por',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public static $diasNombres = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    /**
     * Devuelve true si ahora mismo la ventana de planeación está abierta.
     * Consulta la BD primero; si no hay config activa usa el fallback (lunes 9-11).
     */
    public static function estaAbierta(): bool
    {
        $now = Carbon::now();

        $ventana = self::where('activo', true)
            ->where('dia_semana', $now->isoWeekday()) // 1=Mon..7=Sun
            ->first();

        if ($ventana) {
            $apertura = Carbon::parse($ventana->hora_apertura)->setDateFrom($now);
            $cierre   = Carbon::parse($ventana->hora_cierre)->setDateFrom($now);
            return $now->between($apertura, $cierre);
        }

        // Fallback original: lunes 9-11
        return $now->isMonday() && $now->hour >= 9 && $now->hour < 11;
    }

    /**
     * Retorna la configuración activa actual (para mostrar en UI).
     */
    public static function ventanaActual(): ?self
    {
        $now = Carbon::now();
        return self::where('activo', true)
            ->where('dia_semana', $now->isoWeekday())
            ->first();
    }
}
