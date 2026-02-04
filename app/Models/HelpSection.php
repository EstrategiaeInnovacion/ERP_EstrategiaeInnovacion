<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpSection extends Model
{
    use HasFactory;

    protected $table = 'help_sections';

    protected $fillable = [
        'title',
        'content',
        'section_order',
        'is_active',
        'images',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'images' => 'array', // Importante para manejar el JSON de imágenes
    ];
}