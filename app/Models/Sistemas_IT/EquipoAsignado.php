<?php

namespace App\Models\Sistemas_IT;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipoAsignado extends Model
{
    use HasFactory;

    protected $table = 'it_equipos_asignados';

    protected $fillable = [
        'user_id',
        'uuid_activos',
        'nombre_equipo',
        'modelo',
        'numero_serie',
        'photo_id',
        'nombre_usuario_pc',
        'notas',
        'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function correos()
    {
        return $this->hasMany(EquipoCorreo::class, 'equipo_asignado_id');
    }

    public function perifericos()
    {
        return $this->hasMany(EquipoPeriferico::class, 'equipo_asignado_id');
    }

}
