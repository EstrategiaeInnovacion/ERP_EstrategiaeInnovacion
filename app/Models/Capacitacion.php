<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capacitacion extends Model
{
    use HasFactory;

    protected $table = 'capacitaciones';

    protected $fillable = [
        'titulo',
        'descripcion',
        'archivo_path',
        'thumbnail_path',
        'subido_por',
        'activo',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function adjuntos()
    {
        return $this->hasMany(CapacitacionAdjunto::class);
    }
}