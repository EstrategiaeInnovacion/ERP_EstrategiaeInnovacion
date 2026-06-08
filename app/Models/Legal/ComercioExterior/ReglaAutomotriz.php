<?php

namespace App\Models\Legal\ComercioExterior;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReglaAutomotriz extends Model
{
    protected $table = 'reglas_automotrices';

    protected $fillable = [
        'fraccion_arancelaria',
        'fraccion_inicio_norm',
        'fraccion_fin_norm',
        'tipo_vehiculo_pt',
        'requiere_cc',
        'nivel_cc',
        'cc_excepcion_desde',
        'vcr_metodo',
        'vcr_umbral_pct',
        'tabla_partes_ref',
        'articulo_apendice',
        'regla_texto',
        'referencia_nota',
    ];

    protected $casts = [
        'requiere_cc'    => 'boolean',
        'vcr_umbral_pct' => 'decimal:2',
    ];

    public function relaciones(): HasMany
    {
        return $this->hasMany(CatalogoRelacion::class, 'regla_automotriz_id');
    }
}
