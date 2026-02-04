<?php

namespace App\Models\Sistemas_IT;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'folio',
        'user_id',
        'nombre_solicitante',
        'correo_solicitante',
        'nombre_programa',
        'descripcion_problema',
        'imagenes',
        'estado',
        'fecha_apertura',
        'fecha_cierre',
        'observaciones',
        'tipo_problema',
        'prioridad',
        'is_read',
        'notified_at',
        'read_at',
        'closed_by_user',
        'closed_by_user_at',
        'maintenance_slot_id',
        'maintenance_scheduled_at',
        'maintenance_details',
        'equipment_identifier',
        'equipment_brand',
        'equipment_model',
        'equipment_password',
        'disk_type',
        'ram_capacity',
        'battery_status',
        'aesthetic_observations',
        'replacement_components',
        'computer_profile_id',
        'imagenes_admin',
        'user_has_updates',
        'user_notified_at',
        'user_last_read_at',
        'user_notification_summary',
    ];

    protected $casts = [
        'imagenes' => 'array',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'notified_at' => 'datetime',
        'read_at' => 'datetime',
        'is_read' => 'boolean',
        'user_has_updates' => 'boolean',
        'user_notified_at' => 'datetime',
        'user_last_read_at' => 'datetime',
        'closed_by_user' => 'boolean',
        'closed_by_user_at' => 'datetime',
        'maintenance_scheduled_at' => 'datetime',
        'replacement_components' => 'array',
        'imagenes_admin' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->folio)) {
                $ticket->folio = static::generateFolio();
            }
        });
    }

    public static function generateFolio()
    {
        $year = date('Y');
        $month = date('m');
        $lastTicket = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastTicket ? (intval(substr($lastTicket->folio, -4)) + 1) : 1;

        return sprintf('TK%s%s%04d', $year, $month, $number);
    }

    public function scopeByEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeByTipo($query, $tipo)
    {
        return $query->where('tipo_problema', $tipo);
    }

    public function getEstadoBadgeAttribute()
    {
        $badges = [
            'abierto' => 'bg-green-100 text-green-800',
            'en_proceso' => 'bg-yellow-100 text-yellow-800',
            'cancelado' => 'bg-red-100 text-red-800',
            'cerrado' => 'bg-blue-100 text-blue-800'
        ];

        return $badges[$this->estado] ?? 'bg-gray-100 text-gray-800';
    }

    public function getPrioridadBadgeAttribute()
    {
        $badges = [
            'baja' => 'bg-blue-100 text-blue-800',
            'media' => 'bg-orange-100 text-orange-800',
            'alta' => 'bg-red-100 text-red-800',
            'critica' => 'bg-red-600 text-white'
        ];

        return $badges[$this->prioridad] ?? 'bg-gray-100 text-gray-800';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function maintenanceSlot(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSlot::class);
    }

    public function maintenanceBooking(): HasOne
    {
        return $this->hasOne(MaintenanceBooking::class);
    }

    public function computerProfile(): BelongsTo
    {
        return $this->belongsTo(ComputerProfile::class);
    }
}
