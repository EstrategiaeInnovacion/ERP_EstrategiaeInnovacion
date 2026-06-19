<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudVacacion extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_vacaciones';

    protected $fillable = [
        'empleado_id',
        'fecha_inicio',
        'fecha_fin',
        'dias_solicitados',
        'motivo',
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
        'aprobado_supervisor_at' => 'datetime',
        'aprobado_rh_at' => 'datetime',
    ];

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
}
