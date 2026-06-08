<?php

namespace App\Models\Sistemas_IT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MantenimientoArchivo extends Model
{
    protected $table = 'ti_mantenimiento_archivos';

    protected $fillable = [
        'mantenimiento_id',
        'momento',
        'ruta',
        'nombre_original',
        'tipo_mime',
        'tamanio_bytes',
    ];

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function getEsImagenAttribute(): bool
    {
        return str_starts_with($this->tipo_mime ?? '', 'image/');
    }

    public function getEsPdfAttribute(): bool
    {
        return $this->tipo_mime === 'application/pdf';
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->ruta);
    }

    public function getMomentoLabelAttribute(): string
    {
        return match($this->momento) {
            'antes'     => 'Antes',
            'despues'   => 'Después',
            'documento' => 'Documento',
            default     => $this->momento,
        };
    }
}
