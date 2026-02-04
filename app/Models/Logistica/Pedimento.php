<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pedimento extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'pedimentos';

    /**
     * Atributos que se pueden asignar de forma masiva
     */
    protected $fillable = [
        'categoria',
        'clave',
        'descripcion'
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'categoria' => 'string',
        'clave' => 'string',
        'descripcion' => 'string'
    ];

    /**
     * Scope para buscar por clave
     */
    public function scopePorClave($query, $clave)
    {
        return $query->where('clave', 'like', "%{$clave}%");
    }

    /**
     * Scope para buscar por descripción
     */
    public function scopePorDescripcion($query, $descripcion)
    {
        return $query->where('descripcion', 'like', "%{$descripcion}%");
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }



    /**
     * Obtener todas las categorías únicas
     */
    public static function getCategorias()
    {
        return self::whereNotNull('categoria')
            ->distinct()
            ->pluck('categoria')
            ->filter()
            ->sort()
            ->values();
    }



    /**
     * Scope para filtrar por estado de pago
     */
    public function scopePorEstadoPago($query, $estado)
    {
        return $query->where('estado_pago', $estado);
    }

    /**
     * Scope para pedimentos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado_pago', 'pendiente');
    }

    /**
     * Scope para pedimentos pagados
     */
    public function scopePagados($query)
    {
        return $query->where('estado_pago', 'pagado');
    }

    /**
     * Scope para pedimentos vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('estado_pago', 'vencido');
    }

    /**
     * Verificar si el pedimento está vencido
     */
    public function estaVencido()
    {
        if (!$this->fecha_vencimiento || $this->estado_pago === 'pagado') {
            return false;
        }
        
        return $this->fecha_vencimiento->isPast();
    }

    /**
     * Obtener el color del estado para la UI
     */
    public function getColorEstado()
    {
        switch ($this->estado_pago) {
            case 'pagado':
                return 'green';
            case 'vencido':
                return 'red';
            case 'pendiente':
            default:
                return $this->estaVencido() ? 'red' : 'yellow';
        }
    }

    /**
     * Obtener el texto del estado para mostrar
     */
    public function getTextoEstado()
    {
        switch ($this->estado_pago) {
            case 'pagado':
                return '✅ Pagado';
            case 'vencido':
                return '❌ Vencido';
            case 'pendiente':
            default:
                return $this->estaVencido() ? '⚠️ Vencido' : '⏳ Pendiente';
        }
    }

    /**
     * Relación con operaciones logísticas
     */
    public function operaciones()
    {
        return $this->hasMany(OperacionLogistica::class, 'no_pedimento', 'clave');
    }

    /**
     * Relación singular con operación logística (primera operación que coincide)
     */
    public function operacion()
    {
        return $this->hasOne(OperacionLogistica::class, 'no_pedimento', 'clave');
    }
}