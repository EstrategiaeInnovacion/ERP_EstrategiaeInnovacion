<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class CampoValor extends Model
{
    protected $table = 'logistica_campo_valores';

    protected $fillable = ['campo_id', 'matriz_seguimiento_id', 'valor'];

    public function campo()
    {
        return $this->belongsTo(CampoPersonalizado::class, 'campo_id');
    }

    public function seguimiento()
    {
        return $this->belongsTo(MatrizSeguimiento::class, 'matriz_seguimiento_id');
    }
}
