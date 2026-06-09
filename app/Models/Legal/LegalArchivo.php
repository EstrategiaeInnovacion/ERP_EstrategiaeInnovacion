<?php

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Model;

class LegalArchivo extends Model
{
    protected $table = 'legal_archivos';

    protected $fillable = ['proyecto_id', 'nombre', 'tipo', 'ruta', 'es_url', 'mime_type', 'contenido'];

    protected $casts = [
        'es_url' => 'boolean',
    ];

    // Excluir el LONGBLOB de todas las consultas normales para no saturar memoria
    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('sin_contenido', fn ($q) => $q->select([
            'id', 'proyecto_id', 'nombre', 'tipo', 'ruta', 'es_url', 'mime_type', 'created_at', 'updated_at',
        ]));
    }

    public function proyecto()
    {
        return $this->belongsTo(LegalProyecto::class, 'proyecto_id');
    }

    /**
     * URL pública para descarga (solo aplica a archivos subidos, no URLs externas)
     */
    public function getUrlPublicaAttribute(): ?string
    {
        if ($this->es_url) {
            return $this->ruta;
        }
        return route('legal.matriz.archivo.download', $this->id);
    }
}
