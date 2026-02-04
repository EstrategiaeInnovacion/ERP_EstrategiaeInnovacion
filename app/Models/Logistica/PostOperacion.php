<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PostOperacion extends Model
{
    use HasFactory;

    protected $table = 'post_operaciones';

    protected $fillable = [
        'nombre',
        'descripcion', 
        'status',
        'operacion_logistica_id',
        'fecha_creacion',
        'fecha_completado',
        'no_pedimento', // Para asociar post-operaciones específicas por pedimento
        // Campo legacy
        'post_operacion',
    ];

    protected $casts = [
        'status' => 'string',
        'fecha_creacion' => 'datetime',
        'fecha_completado' => 'datetime',
    ];

    /**
     * Relación many-to-many con operaciones logísticas a través de tabla pivot
     */
    public function operacionesLogisticas()
    {
        return $this->belongsToMany(
            OperacionLogistica::class,
            'post_operacion_operacion',
            'post_operacion_id',
            'operacion_logistica_id'
        )->withPivot([
            'status',
            'fecha_asignacion', 
            'fecha_completado',
            'notas_especificas'
        ])->withTimestamps();
    }

    /**
     * Relación directa con las asignaciones (tabla pivot)
     */
    public function asignaciones()
    {
        return $this->hasMany(PostOperacionOperacion::class, 'post_operacion_id');
    }

    /**
     * Relación legacy - mantener por compatibilidad (DEPRECATED)
     */
    public function operacionLogistica()
    {
        return $this->belongsTo(OperacionLogistica::class, 'operacion_logistica_id');
    }

    /**
     * Relación legacy - mantener por compatibilidad (DEPRECATED)
     */
    public function operaciones()
    {
        return $this->hasMany(OperacionLogistica::class, 'post_operacion_id');
    }

    /**
     * Scope para post-operaciones pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('status', 'Pendiente');
    }

    /**
     * Scope para post-operaciones completadas
     */
    public function scopeCompletadas($query)
    {
        return $query->where('status', 'Completado');
    }
}
