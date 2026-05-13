<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class MatrizApoyoNaviera extends Model
{
    protected $table = 'matriz_apoyo_navieras';

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
        'Customer Service',
        'Finanzas',
        'Corte de Demoras',
    ];
}
