<?php

namespace App\Models\Legal\ComercioExterior;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReglaOrigen extends Model
{
    protected $table = 'reglas_origen';

    protected $fillable = [
        'fraccion_arancelaria',
        'fraccion_inicio_norm',
        'fraccion_fin_norm',
        'descripcion',
        'criterio',
        'regla_texto',
        'capitulo',
        'requiere_apendice',
        'nota_apendice',
        'referencia_apendice_texto',
        'vcr_porcentaje',
        'metodo_vcr',
        'requiere_cambio_fraccion',
        'nivel_cambio',
    ];

    protected $casts = [
        'requiere_apendice'        => 'boolean',
        'requiere_cambio_fraccion' => 'boolean',
        'vcr_porcentaje'           => 'decimal:2',
        'capitulo'                 => 'integer',
    ];

    public function bomItems(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }
}
