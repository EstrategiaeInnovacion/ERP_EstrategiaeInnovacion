<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvisoAsistencia extends Model
{
    protected $table = 'aviso_asistencias';

    protected $fillable = [
        'empleado_id',
        'enviado_por',
        'tipo',
        'mensaje',
        'periodo',
        'cantidad_incidencias',
        'leido',
        'leido_at',
    ];

    protected $casts = [
        'leido' => 'boolean',
        'leido_at' => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function enviadoPor()
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }
}
