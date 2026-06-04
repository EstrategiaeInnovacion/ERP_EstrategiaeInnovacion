<?php

namespace App\Models\Sistemas_IT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipoCorreo extends Model
{
    use HasFactory;

    protected $table = 'it_equipos_correos';

    protected $fillable = [
        'equipo_asignado_id',
        'correo',
    ];

    public function equipo()
    {
        return $this->belongsTo(EquipoAsignado::class, 'equipo_asignado_id');
    }
}
