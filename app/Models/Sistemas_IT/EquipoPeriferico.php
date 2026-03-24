<?php

namespace App\Models\Sistemas_IT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipoPeriferico extends Model
{
    use HasFactory;

    protected $table = 'it_equipos_perifericos';

    protected $fillable = [
        'equipo_asignado_id',
        'uuid_activos',
        'nombre',
        'tipo',
        'numero_serie',
    ];

    public function equipo()
    {
        return $this->belongsTo(EquipoAsignado::class, 'equipo_asignado_id');
    }
}
