<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SolicitudPermiso extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_permiso';

    protected $fillable = [
        'empleado_id',
        'tipo_permiso',
        'motivo_detalle',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'reposicion_tipo',
        'comprobante_path',
        'estado',
        'supervisor_id',
        'aprobado_supervisor_at',
        'comentarios_supervisor',
        'rh_aprobador_id',
        'aprobado_rh_at',
        'comentarios_rh',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'hora_inicio' => 'datetime',
        'hora_fin' => 'datetime',
        'aprobado_supervisor_at' => 'datetime',
        'aprobado_rh_at' => 'datetime',
    ];

    // Relaciones
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(Empleado::class, 'supervisor_id');
    }

    public function rhAprobador()
    {
        return $this->belongsTo(User::class, 'rh_aprobador_id');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAprobadosSupervisor($query)
    {
        return $query->where('estado', 'aprobado_supervisor');
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado', 'aprobado');
    }

    public function scopeDondeFecha($query, $fecha)
    {
        return $query->where('fecha_inicio', '<=', $fecha)
                     ->where('fecha_fin', '>=', $fecha);
    }
}
