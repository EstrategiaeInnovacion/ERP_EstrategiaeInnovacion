<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    // Define the table if it's not the plural of the model name (optional if table is 'inventory_items')
    protected $table = 'inventory_items';

    // Constants for the 'estado' enum used in your Seeder
    const ESTADO_DISPONIBLE = 'disponible';
    const ESTADO_PRESTADO = 'prestado';
    const ESTADO_MANTENIMIENTO = 'mantenimiento';
    const ESTADO_RESERVADO = 'reservado';
    const ESTADO_DANADO = 'dañado';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo_producto',
        'identificador',
        'nombre',
        'categoria',
        'marca',
        'modelo',
        'numero_serie',
        'estado',
        'es_funcional',
        'ubicacion',
        'descripcion_general',
        'notas',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'es_funcional' => 'boolean',
    ];
}