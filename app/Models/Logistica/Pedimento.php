<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class Pedimento extends Model
{
    protected $table = 'pedimentos';

    protected $fillable = [
        'categoria',
        'clave',
        'descripcion'
    ];

    public function scopePorClave($query, $clave)
    {
        return $query->where('clave', 'like', "%{$clave}%");
    }

    public function scopePorDescripcion($query, $descripcion)
    {
        return $query->where('descripcion', 'like', "%{$descripcion}%");
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public static function getCategorias()
    {
        return self::whereNotNull('categoria')
            ->distinct()
            ->pluck('categoria')
            ->filter()
            ->sort()
            ->values();
    }
}