<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedimentoOperacion extends Model
{
    protected $table = 'pedimentos_operaciones';

    protected $fillable = [
        'no_pedimento',
        'clave',
        'operacion_logistica_id',
        'estado_pago',
        'fecha_pago',
        'monto',
        'moneda',
        'observaciones'
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2'
    ];

    /**
     * Relación con OperacionLogistica
     */
    public function operacionLogistica(): BelongsTo
    {
        return $this->belongsTo(OperacionLogistica::class, 'operacion_logistica_id');
    }

    /**
     * Scope para filtrar por estado de pago
     */
    public function scopePorPagar($query)
    {
        return $query->where('estado_pago', 'pendiente');
    }

    /**
     * Scope para filtrar por pagados
     */
    public function scopePagados($query)
    {
        return $query->where('estado_pago', 'pagado');
    }

    /**
     * Scope para filtrar por clave
     */
    public function scopePorClave($query, $clave)
    {
        return $query->where('clave', $clave);
    }
}
