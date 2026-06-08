<?php

namespace App\Models\Sistemas_IT;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expediente extends Model
{
    protected $table = 'ti_expedientes';

    protected $fillable = [
        'equipo_asignado_id',
        'estado',
        'fecha_apertura',
        'fecha_cierre',
        'motivo_cierre',
        'observaciones',
        'created_by',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre'   => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function equipoAsignado(): BelongsTo
    {
        return $this->belongsTo(EquipoAsignado::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class)->orderBy('created_at', 'desc');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getEstadoBadgeAttribute(): string
    {
        return match($this->estado) {
            'activo'        => 'bg-green-100 text-green-800',
            'en_reparacion' => 'bg-yellow-100 text-yellow-800',
            'retirado'      => 'bg-red-100 text-red-800',
            'renovado'      => 'bg-blue-100 text-blue-800',
            default         => 'bg-slate-100 text-slate-600',
        };
    }

    public function getEstadoLabelAttribute(): string
    {
        return match($this->estado) {
            'activo'        => 'Activo',
            'en_reparacion' => 'En Reparación',
            'retirado'      => 'Retirado',
            'renovado'      => 'Renovado',
            default         => $this->estado,
        };
    }

    public function estaActivo(): bool
    {
        return in_array($this->estado, ['activo', 'en_reparacion']);
    }
}
