<?php

namespace App\Models\Logistica;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CampoPersonalizado extends Model
{
    protected $table = 'logistica_campos_personalizados';

    protected $fillable = ['cliente_id', 'nombre', 'tipo', 'es_obligatorio', 'orden', 'created_by'];

    protected $casts = ['es_obligatorio' => 'boolean'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function valores()
    {
        return $this->hasMany(CampoValor::class, 'campo_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
