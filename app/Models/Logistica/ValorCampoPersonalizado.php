<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class ValorCampoPersonalizado extends Model
{
    protected $table = 'valores_campos_personalizados';

    protected $fillable = [
        'operacion_logistica_id',
        'campo_personalizado_id',
        'valor',
    ];

    /**
     * La operación a la que pertenece este valor
     */
    public function operacion()
    {
        return $this->belongsTo(OperacionLogistica::class, 'operacion_logistica_id');
    }

    /**
     * El campo personalizado al que pertenece este valor
     */
    public function campo()
    {
        return $this->belongsTo(CampoPersonalizadoMatriz::class, 'campo_personalizado_id');
    }
}
