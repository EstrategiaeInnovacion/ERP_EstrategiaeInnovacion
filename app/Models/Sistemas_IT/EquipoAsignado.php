<?php

namespace App\Models\Sistemas_IT;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

class EquipoAsignado extends Model
{
    use HasFactory;

    protected $table = 'it_equipos_asignados';

    protected $fillable = [
        'user_id',
        'uuid_activos',
        'nombre_equipo',
        'modelo',
        'numero_serie',
        'photo_id',
        'nombre_usuario_pc',
        'contrasena_equipo',
        'notas',
        'es_principal',
        'last_maintenance_at',
        'next_maintenance_at',
        'maintenance_reminder_sent_at',
    ];

    protected $hidden = ['contrasena_equipo'];

    protected $casts = [
        'es_principal'                 => 'boolean',
        'last_maintenance_at'          => 'datetime',
        'next_maintenance_at'          => 'datetime',
        'maintenance_reminder_sent_at' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (self $equipo) {
            Expediente::firstOrCreate(
                ['equipo_asignado_id' => $equipo->id],
                [
                    'estado'         => 'activo',
                    'fecha_apertura' => now()->toDateString(),
                    'created_by'     => auth()->id() ?? 1,
                ]
            );
        });
    }

    // ── Relaciones ────────────────────────────────────────────────────────

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function correos(): HasMany
    {
        return $this->hasMany(EquipoCorreo::class, 'equipo_asignado_id');
    }

    public function perifericos(): HasMany
    {
        return $this->hasMany(EquipoPeriferico::class, 'equipo_asignado_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function expediente(): HasOne
    {
        return $this->hasOne(Expediente::class);
    }

    // ── Accessors / mutators ──────────────────────────────────────────────

    public function setContrasenaEquipoAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['contrasena_equipo'] = null;
            return;
        }
        $this->attributes['contrasena_equipo'] = Crypt::encryptString($value);
    }

    public function getContrasenaDescifradaAttribute(): string
    {
        $raw = $this->attributes['contrasena_equipo'] ?? null;
        if ($raw === null) {
            return '';
        }
        try {
            return Crypt::decryptString($raw);
        } catch (\Exception $e) {
            return '';
        }
    }
}
