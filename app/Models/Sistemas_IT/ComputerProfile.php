<?php

namespace App\Models\Sistemas_IT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'is_loaned',
        'loaned_to_name',
        'loaned_to_email',
        'last_ticket_id',
    ];

    protected $casts = [
        'replacement_components' => 'array',
        'last_maintenance_at' => 'datetime',
        'is_loaned' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'last_ticket_id');
    }
}
