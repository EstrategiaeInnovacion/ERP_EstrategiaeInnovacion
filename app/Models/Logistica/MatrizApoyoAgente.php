<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class MatrizApoyoAgente extends Model
{
    protected $table = 'matriz_apoyo_agentes';

    protected $fillable = [
        'cliente',
        'aduana',
        'agente_aduanal',
        'razon_social',
        'patente',
        'calificacion',
        'responsabilidad',
        'nombre',
        'correo_electronico',
        'telefono',
        'comentarios',
    ];

    public const RESPONSABILIDADES = [
        'Gerente de operaciones',
        'Ejecutivo de operaciones - Tramitador Operativo',
        'Cita de Previa',
        'Clarificación de mercancías',
        'Cita de despacho',
        'Cita de vacío',
    ];
}
