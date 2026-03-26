<?php

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Model;

class LegalCategoria extends Model
{
    protected $table = 'legal_categorias';

    protected $fillable = ['nombre', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(LegalCategoria::class, 'parent_id');
    }

    public function subcategorias()
    {
        return $this->hasMany(LegalCategoria::class, 'parent_id');
    }

    public function proyectos()
    {
        return $this->hasMany(LegalProyecto::class, 'categoria_id');
    }
}
