<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capacitacion extends Model
{
    use HasFactory;

    protected $table = 'capacitaciones';

    protected $fillable = [
        'titulo',
        'descripcion',
        'archivo_path',
        'thumbnail_path',
        'subido_por',
        'activo',
        'youtube_url', // <-- AGREGADO
    ];

    public function isYoutube()
    {
        return !empty($this->youtube_url);
    }

    public function getYoutubeId()
    {
        if (!$this->youtube_url) return null;
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        if (preg_match($pattern, $this->youtube_url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function adjuntos()
    {
        return $this->hasMany(CapacitacionAdjunto::class);
    }
}