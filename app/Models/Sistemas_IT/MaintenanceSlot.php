<?php

namespace App\Models\Sistemas_IT;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'capacity',
        'booked_count',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'is_active' => 'boolean',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(MaintenanceBooking::class);
    }

    public function getStartDateTimeAttribute(): Carbon
    {
        $start = $this->start_time instanceof Carbon ? $this->start_time->format('H:i:s') : $this->start_time;

        return Carbon::parse(
            $this->date->format('Y-m-d') . ' ' . $start,
            'America/Mexico_City'
        )->setTimezone('America/Mexico_City');
    }

    public function getEndDateTimeAttribute(): Carbon
    {
        $end = $this->end_time instanceof Carbon ? $this->end_time->format('H:i:s') : $this->end_time;

        return Carbon::parse(
            $this->date->format('Y-m-d') . ' ' . $end,
            'America/Mexico_City'
        )->setTimezone('America/Mexico_City');
    }

    public function getAvailableCapacityAttribute(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
