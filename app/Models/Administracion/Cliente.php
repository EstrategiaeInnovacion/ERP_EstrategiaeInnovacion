<?php

namespace App\Models\Administracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'admin_clientes';

    protected $fillable = [
        'nombre',
        'contacto',
        'correo',
        'telefono',
        'empresa',
        'notas',
    ];

    public function perfil()
    {
        return $this->hasOne(PerfilCliente::class, 'cliente_id');
    }
}
