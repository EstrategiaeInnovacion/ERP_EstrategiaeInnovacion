<?php

namespace App\Models\Sistemas_IT;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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
        'contrasena_equipo',
        'notas',
        'es_principal',
    ];

    protected $hidden = ['contrasena_equipo'];

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

    public function setContrasenaEquipoAttribute(string $value): void
    {
        $this->attributes['contrasena_equipo'] = Crypt::encryptString($value);
    }

    public function getContrasenaDescifradaAttribute(): string
    {
        try {
            return Crypt::decryptString($this->attributes['contrasena_equipo']);
        } catch (\Exception $e) {
            return '';
        }
    }
}
