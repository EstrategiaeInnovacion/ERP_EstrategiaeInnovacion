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


}
