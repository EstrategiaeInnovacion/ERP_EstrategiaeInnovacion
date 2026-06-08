<?php

namespace App\Models\Legal\ComercioExterior;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bom extends Model
{
    protected $fillable = [
        'clave',
        'nombre',
        'archivo_original',
        'created_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }

    public function originAnalyses(): HasMany
    {
        return $this->hasMany(OriginAnalysis::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
