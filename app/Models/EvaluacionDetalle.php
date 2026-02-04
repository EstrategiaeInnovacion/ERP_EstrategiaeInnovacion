<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionDetalle extends Model
{
    use HasFactory;

    protected $table = 'evaluacion_detalles';

    protected $fillable = [
        'evaluacion_id',
        'criterio_id',
        'calificacion',
        'observaciones',
    ];
    
    public function criterio()
    {
        return $this->belongsTo(CriterioEvaluacion::class, 'criterio_id');
    }
}