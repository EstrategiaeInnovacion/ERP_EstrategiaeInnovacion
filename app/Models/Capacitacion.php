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
        'categoria',
        'puestos_permitidos',
        'usuarios_permitidos', // <-- NUEVO
        'archivo_path',
        'thumbnail_path',
        'subido_por',
        'activo',
        'youtube_url',
    ];

    protected $casts = [
        'puestos_permitidos' => 'array',
        'usuarios_permitidos' => 'array',
    ];

    /**
     * Determina si el video es visible para el usuario dado.
     */
    public function isVisibleFor(User $user)
    {
        // Si es admin, ve todo
        if ($user->isAdmin()) {
            return true;
        }

        // Si no hay restricciones (ni puestos ni usuarios), es público para todos
        $tieneRestriccionPuestos = !empty($this->puestos_permitidos);
        $tieneRestriccionUsuarios = !empty($this->usuarios_permitidos);

        if (!$tieneRestriccionPuestos && !$tieneRestriccionUsuarios) {
            return true;
        }

        // Verificar restricciones por usuarios específicos
        if ($tieneRestriccionUsuarios) {
            if (in_array($user->id, $this->usuarios_permitidos)) {
                return true;
            }
        }

        // Verificar restricciones por puestos
        if ($tieneRestriccionPuestos) {
            $posicionEmpleado = $user->empleado->posicion ?? '';

            if (!empty($posicionEmpleado)) {
                $posicionEmpleado = mb_strtolower($posicionEmpleado, 'UTF-8');

                foreach ($this->puestos_permitidos as $puestoPermitido) {
                    $puestoPermitido = mb_strtolower(trim($puestoPermitido), 'UTF-8');
                    if (str_contains($posicionEmpleado, $puestoPermitido)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function isYoutube()
    {
        return !empty($this->youtube_url);
    }

    public function getYoutubeId()
    {
        if (!$this->youtube_url)
            return null;
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        if (preg_match($pattern, $this->youtube_url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function uploader()
    {
        return $this->belongsTo(User::class , 'subido_por');
    }

    public function adjuntos()
    {
        return $this->hasMany(CapacitacionAdjunto::class);
    }
}