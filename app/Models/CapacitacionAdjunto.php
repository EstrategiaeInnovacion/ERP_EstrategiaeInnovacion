<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapacitacionAdjunto extends Model
{
    protected $fillable = ['capacitacion_id', 'titulo', 'archivo_path'];

    public function capacitacion()
    {
        return $this->belongsTo(Capacitacion::class);
    }
}