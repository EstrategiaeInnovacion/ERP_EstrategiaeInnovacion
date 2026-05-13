<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class MatrizApoyoArrastre extends Model
{
    protected $table = 'matriz_apoyo_arrastres';

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
        'Programación de unidad',
        'Finanzas',
    ];
}
