<?php

namespace App\Models\Sistemas_IT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_slot_id',
        'ticket_id',
        'additional_details',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSlot::class, 'maintenance_slot_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
