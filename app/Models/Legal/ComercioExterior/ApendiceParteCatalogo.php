<?php

namespace App\Models\Legal\ComercioExterior;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApendiceParteCatalogo extends Model
{
    protected $table = 'apendice_partes_catalogo';

    protected $fillable = [
        'tabla',
        'tabla_codigo',
        'fraccion_arancelaria',
        'fraccion_inicio_norm',
        'fraccion_fin_norm',
        'fraccion_normalizada',
        'tiene_ex_prefix',
        'vcr_umbral_cn_pct',
        'vcr_umbral_vt_pct',
        'descripcion',
    ];

    protected $casts = [
        'tiene_ex_prefix'   => 'boolean',
        'vcr_umbral_cn_pct' => 'decimal:2',
        'vcr_umbral_vt_pct' => 'decimal:2',
    ];

    public function relaciones(): HasMany
    {
        return $this->hasMany(CatalogoRelacion::class, 'apendice_parte_id');
    }
}
