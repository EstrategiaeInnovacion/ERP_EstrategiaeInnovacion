<?php

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Model;

class LegalPagina extends Model
{
    protected $table = 'legal_paginas';

    protected $fillable = [
        'nombre',
        'url',
    ];
}
