<?php

namespace App\Models\Sistemas_IT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ComputerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'brand',
        'model',
        'disk_type',
        'ram_capacity',
        'battery_status',
        'aesthetic_observations',
        'replacement_components',
        'last_maintenance_at',
        'next_maintenance_at',
        'maintenance_reminder_sent_at',
        'is_loaned',
        'loaned_to_name',
        'loaned_to_email',
        'last_ticket_id',
        'equipo_asignado_id',
    ];

    protected $casts = [
        'replacement_components' => 'array',
        'last_maintenance_at' => 'datetime',
        'next_maintenance_at' => 'datetime',
        'maintenance_reminder_sent_at' => 'date',
        'is_loaned' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (self $profile) {
            Expediente::firstOrCreate(
                ['equipo_asignado_id' => $profile->equipo_asignado_id],
                [
                    'estado'         => 'activo',
                    'fecha_apertura' => now()->toDateString(),
                    'created_by'     => auth()->id() ?? 1,
                ]
            );
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'last_ticket_id');
    }

    public function equipoAsignado(): BelongsTo
    {
        return $this->belongsTo(EquipoAsignado::class, 'equipo_asignado_id');
    }

    public function expediente(): HasOne
    {
        return $this->hasOne(Expediente::class, 'equipo_asignado_id', 'equipo_asignado_id');
    }
}
