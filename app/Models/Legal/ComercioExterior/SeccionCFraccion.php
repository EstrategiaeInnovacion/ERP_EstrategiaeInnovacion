<?php

namespace App\Models\Legal\ComercioExterior;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeccionCFraccion extends Model
{
    protected $table = 'seccion_c_fracciones';

    protected $fillable = [
        'fraccion_tmec',
        'fraccion_tmec_norm',
        'fraccion_canada',
        'fraccion_eeuu',
        'fraccion_mexico',
        'descripcion',
    ];

    public function relaciones(): HasMany
    {
        return $this->hasMany(CatalogoRelacion::class, 'seccion_c_fraccion_id');
    }
}
