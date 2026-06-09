<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapacitacionAdjunto extends Model
{
    protected $fillable = ['capacitacion_id', 'titulo', 'archivo_path', 'archivo_contenido', 'archivo_mime_type'];

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('sin_contenido', fn ($q) => $q->select([
            'id', 'capacitacion_id', 'titulo', 'archivo_path', 'archivo_mime_type',
            'created_at', 'updated_at',
        ]));
    }

    public function capacitacion()
    {
        return $this->belongsTo(Capacitacion::class);
    }
}