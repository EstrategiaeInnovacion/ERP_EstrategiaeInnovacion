<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperacionComentario extends Model
{
    use HasFactory;

    protected $table = 'operacion_comentarios';

    protected $fillable = [
        'operacion_logistica_id',
        'comentario',
        'status_en_momento',
        'tipo_accion',
        'usuario_nombre',
        'usuario_id',
        'contexto_operacion',
    ];

    protected $casts = [
        'contexto_operacion' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con la operación logística
     */
    public function operacionLogistica()
    {
        return $this->belongsTo(OperacionLogistica::class, 'operacion_logistica_id');
    }

    /**
     * Scope para ordenar cronológicamente
     */
    public function scopeCronologico($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope para filtrar por tipo de acción
     */
    public function scopePorTipoAccion($query, $tipo)
    {
        return $query->where('tipo_accion', $tipo);
    }

    /**
     * Accessor para formato de fecha legible
     */
    public function getFechaFormateadaAttribute()
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    /**
     * Accessor para el icono según tipo de acción
     */
    public function getIconoAccionAttribute()
    {
        $iconos = [
            'creacion' => '🆕',
            'status_change' => '🔄',
            'comentario' => '💬',
            'edicion' => '✏️',
            'edicion_comentario' => '📝',
            'cambio_manual_status' => '👤',
            'actualizacion_automatica' => '🔄'
        ];

        return $iconos[$this->tipo_accion] ?? '📝';
    }
}