<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class Incoterm extends Model
{
    protected $table = 'incoterms';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'grupo',
        'aplicable_importacion',
        'aplicable_exportacion',
        'activo',
        'orden'
    ];

    protected $casts = [
        'aplicable_importacion' => 'boolean',
        'aplicable_exportacion' => 'boolean',
        'activo' => 'boolean'
    ];

    /**
     * Scope para obtener solo incoterms activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar por campo orden
     */
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }

    /**
     * Scope para obtener incoterms aplicables a importación
     */
    public function scopeParaImportacion($query)
    {
        return $query->where('aplicable_importacion', true);
    }

    /**
     * Scope para obtener incoterms aplicables a exportación
     */
    public function scopeParaExportacion($query)
    {
        return $query->where('aplicable_exportacion', true);
    }

    /**
     * Scope para filtrar por grupo
     */
    public function scopeGrupo($query, $grupo)
    {
        return $query->where('grupo', $grupo);
    }
}
