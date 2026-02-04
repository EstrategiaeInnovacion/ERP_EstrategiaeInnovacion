<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpleadoBaja extends Model
{
    protected $table = 'empleados_baja';
    
    protected $fillable = [
        'empleado_id',
        'user_id',
        'nombre',
        'correo',
        'motivo_baja',
        'fecha_baja',
        'observaciones',
    ];

    protected $casts = [
        'fecha_baja' => 'date',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
