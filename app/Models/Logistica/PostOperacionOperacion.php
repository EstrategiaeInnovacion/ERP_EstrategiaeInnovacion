<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PostOperacionOperacion extends Model
{
    use HasFactory;

    protected $table = 'post_operacion_operacion';

    protected $fillable = [
        'post_operacion_id',
        'operacion_logistica_id',
        'status',
        'fecha_asignacion',
        'fecha_completado',
        'notas_especificas'
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'fecha_completado' => 'datetime',
        'status' => 'string'
    ];

    /**
     * Relación con la post-operación global
     */
    public function postOperacion()
    {
        return $this->belongsTo(PostOperacion::class, 'post_operacion_id');
    }

    /**
     * Relación con la operación logística
     */
    public function operacionLogistica()
    {
        return $this->belongsTo(OperacionLogistica::class, 'operacion_logistica_id');
    }

    /**
     * Scopes para filtrar por estado
     */
    public function scopePendientes($query)
    {
        return $query->where('status', 'Pendiente');
    }

    public function scopeCompletadas($query)
    {
        return $query->where('status', 'Completado');
    }

    public function scopeNoAplica($query)
    {
        return $query->where('status', 'No Aplica');
    }
}
