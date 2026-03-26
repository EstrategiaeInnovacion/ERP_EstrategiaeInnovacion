<?php

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Model;

class LegalArchivo extends Model
{
    protected $table = 'legal_archivos';

    protected $fillable = ['proyecto_id', 'nombre', 'tipo', 'ruta', 'es_url', 'mime_type'];

    protected $casts = [
        'es_url' => 'boolean',
    ];

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
        return asset('storage/' . $this->ruta);
    }
}
