<?php

namespace App\Models\Sistemas_IT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EquipoCorreo extends Model
{
    use HasFactory;

    protected $table = 'it_equipos_correos';

    protected $fillable = [
        'equipo_asignado_id',
        'correo',
        'contrasena_correo',
    ];

    protected $hidden = ['contrasena_correo'];

    public function equipo()
    {
        return $this->belongsTo(EquipoAsignado::class, 'equipo_asignado_id');
    }

    public function setContrasenaCorreoAttribute(?string $value): void
    {
        $this->attributes['contrasena_correo'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getContrasenaDescifradaAttribute(): string
    {
        if (!$this->attributes['contrasena_correo']) {
            return '';
        }
        try {
            return Crypt::decryptString($this->attributes['contrasena_correo']);
        } catch (\Exception $e) {
            return '';
        }
    }
}
