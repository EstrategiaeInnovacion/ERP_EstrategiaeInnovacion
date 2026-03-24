<?php

namespace App\Models\Sistemas_IT;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CredencialEquipo extends Model
{
    use HasFactory;

    protected $table = 'it_credenciales_equipos';

    protected $fillable = [
        'user_id',
        'nombre_usuario_sistema',
        'contrasena',
        'equipo_asignado',
        'tipo_equipo',
        'numero_serie',
        'sistema_operativo',
        'observaciones',
    ];

    protected $hidden = ['contrasena'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setContrasenaAttribute(string $value): void
    {
        $this->attributes['contrasena'] = Crypt::encryptString($value);
    }

    public function getContrasenaDescifradaAttribute(): string
    {
        return Crypt::decryptString($this->attributes['contrasena']);
    }

    public static function tiposEquipo(): array
    {
        return ['Laptop', 'Desktop', 'Tablet', 'Servidor', 'Otro'];
    }
}
