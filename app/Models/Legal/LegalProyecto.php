<?php

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Model;

class LegalProyecto extends Model
{
    protected $table = 'legal_proyectos';

    protected $fillable = ['empresa', 'categoria_id', 'consulta', 'resultado'];

    public function categoria()
    {
        return $this->belongsTo(LegalCategoria::class, 'categoria_id');
    }

    public function archivos()
    {
        return $this->hasMany(LegalArchivo::class, 'proyecto_id');
    }
}
