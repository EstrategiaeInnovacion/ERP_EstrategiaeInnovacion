<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticaCorreoCC extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'logistica_correos_cc';

    /**
     * Atributos que se pueden asignar de forma masiva
     */
    protected $fillable = [
        'nombre',
        'email',
        'tipo',
        'descripcion',
        'activo'
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'activo' => 'boolean'
    ];

    /**
     * Scope para obtener solo correos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para obtener correos por tipo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Obtener correos de administradores activos
     */
    public static function administradoresActivos()
    {
        return self::activos()->porTipo('administrador')->get();
    }

    /**
     * Obtener todos los correos CC activos
     */
    public static function todosActivos()
    {
        return self::activos()->orderBy('tipo')->orderBy('nombre')->get();
    }
}