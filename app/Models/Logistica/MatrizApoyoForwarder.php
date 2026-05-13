<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class MatrizApoyoForwarder extends Model
{
    protected $table = 'matriz_apoyo_forwarders';

    protected $fillable = [
        'cliente',
        'aduana',
        'razon_social',
        'calificacion',
        'responsabilidad',
        'nombre',
        'correo_electronico',
        'telefono',
        'comentarios',
    ];

    public const RESPONSABILIDADES = [
        'Cotización fletes',
        'Contacto puerto origen',
        'Contacto puerto destino',
    ];
}
