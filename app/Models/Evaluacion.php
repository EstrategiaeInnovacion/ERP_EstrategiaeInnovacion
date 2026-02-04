<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones';

    protected $fillable = [
        'empleado_id',
        'evaluador_id',
        'periodo',
        'promedio_final',
        'comentarios_generales',
        'edit_count',
        'fecha_firma_empleado', // <--- NUEVO CAMPO AGREGADO
    ];

    protected $casts = [
        'fecha_firma_empleado' => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(EvaluacionDetalle::class);
    }
    
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function evaluador()
    {
        return $this->belongsTo(User::class, 'evaluador_id');
    }
}