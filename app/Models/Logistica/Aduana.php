<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Aduana extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'aduanas';

    /**
     * Atributos que se pueden asignar de forma masiva
     */
    protected $fillable = [
        'aduana',
        'seccion',
        'denominacion',
        'patente',
        'pais'
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'aduana' => 'string',
        'seccion' => 'string',
        'denominacion' => 'string',
        'patente' => 'string',
        'pais' => 'string'
    ];

    /**
     * Scope para buscar por código de aduana
     */
    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('aduana', $codigo);
    }

    /**
     * Scope para buscar por país
     */
    public function scopePorPais($query, $pais)
    {
        return $query->where('pais', $pais);
    }

    /**
     * Obtener el nombre completo de la aduana
     */
    public function getNombreCompletoAttribute()
    {
        return "{$this->aduana}{$this->seccion} - {$this->denominacion}";
    }
}
