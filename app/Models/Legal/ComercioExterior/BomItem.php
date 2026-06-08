<?php

namespace App\Models\Legal\ComercioExterior;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomItem extends Model
{
    protected $fillable = [
        'bom_id',
        'numero_de_parte',
        'fraccion_arancelaria_fg',
        'descripcion_fg',
        'precio_final_usd',
        'nivel',
        'no_parte_insumo',
        'descripcion_rm',
        'cantidad_incorporada',
        'precio_unitario',
        'unidad_de_medida',
        'costo_total_usd',
        'costo_total_pesos',
        'fraccion_arancelaria_rm',
        'pais_de_origen',
        'nombre_proveedor',
        'presenta_cambio_fraccion',
        'cumple_demas_requisitos',
        'califica_originario',
        'regla_de_origen',
        'criterio_de_origen',
        'regla_origen_id',
        'analisis_detalle',
        'analisis_en',
    ];

    protected $casts = [
        'analisis_detalle' => 'array',
        'analisis_en'      => 'datetime',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function reglaOrigen(): BelongsTo
    {
        return $this->belongsTo(ReglaOrigen::class);
    }
}
