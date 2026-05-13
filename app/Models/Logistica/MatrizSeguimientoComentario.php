<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MatrizSeguimientoComentario extends Model
{
    protected $table = 'matriz_seguimiento_comentarios';

    protected $fillable = ['matriz_seguimiento_id', 'user_id', 'comentario'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seguimiento()
    {
        return $this->belongsTo(MatrizSeguimiento::class, 'matriz_seguimiento_id');
    }
}
