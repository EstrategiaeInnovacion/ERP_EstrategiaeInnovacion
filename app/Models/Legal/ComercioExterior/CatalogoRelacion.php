<?php

namespace App\Models\Legal\ComercioExterior;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogoRelacion extends Model
{
    protected $table = 'catalogo_relaciones';

    protected $fillable = [
        'relation_type',
        'relation_key',
        'regla_origen_id',
        'regla_automotriz_id',
        'seccion_c_fraccion_id',
        'apendice_parte_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function reglaOrigen(): BelongsTo
    {
        return $this->belongsTo(ReglaOrigen::class);
    }

    public function reglaAutomotriz(): BelongsTo
    {
        return $this->belongsTo(ReglaAutomotriz::class);
    }

    public function seccionC(): BelongsTo
    {
        return $this->belongsTo(SeccionCFraccion::class, 'seccion_c_fraccion_id');
    }

    public function apendiceParte(): BelongsTo
    {
        return $this->belongsTo(ApendiceParteCatalogo::class, 'apendice_parte_id');
    }
}
