<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgenteAduanal extends Model
{
    use HasFactory;

    protected $table = 'agentes_aduanales';

    protected $fillable = [
        'agente_aduanal',
    ];

    public function operaciones()
    {
        // La tabla operaciones_logisticas usa el campo 'agente_aduanal' (texto) no 'agente_aduanal_id' (FK)
        return $this->hasMany(OperacionLogistica::class, 'agente_aduanal', 'agente_aduanal');
    }
}
