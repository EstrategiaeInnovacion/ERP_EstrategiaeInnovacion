<?php

namespace App\Models\Sistemas_IT;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Mantenimiento extends Model
{
    use SoftDeletes;

    protected $table = 'ti_mantenimientos';

    protected $fillable = [
        'expediente_id',
        'ticket_id',
        'folio',
        'tipo',
        'estado',
        'prioridad',
        'fecha_inicio',
        'fecha_fin',
        'tecnico_id',
        'usuario_al_momento',
        'area_al_momento',
        'descripcion_problema',
        'actividades',
        'hallazgos',
        'observaciones',
        'proximo_mantenimiento',
        'frecuencia_siguiente',
        'firma_tecnico',
        'firma_usuario',
        'nombre_firma_usuario',
        'created_by',
    ];

    protected $casts = [
        'fecha_inicio'          => 'datetime',
        'fecha_fin'             => 'datetime',
        'proximo_mantenimiento' => 'date',
        'actividades'           => 'array',
        'hallazgos'             => 'array',
    ];

    // ── Boot ─────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $m) {
            if (empty($m->folio)) {
                $m->folio = static::generarFolio();
            }
        });
    }

    public static function generarFolio(): string
    {
        $year = now()->year;
        $last = static::whereYear('created_at', $year)->orderByDesc('id')->first();
        $n    = $last ? ((int) substr($last->folio, -4)) + 1 : 1;
        return sprintf('MNT-%d-%04d', $year, $n);
    }

    // ── Relaciones ────────────────────────────────────────────────────────

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(MantenimientoArchivo::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'preventivo' => 'Preventivo',
            'correctivo' => 'Correctivo',
            'emergente'  => 'Emergente',
            default      => $this->tipo,
        };
    }

    public function getTipoBadgeAttribute(): string
    {
        return match($this->tipo) {
            'preventivo' => 'bg-blue-100 text-blue-800',
            'correctivo' => 'bg-orange-100 text-orange-800',
            'emergente'  => 'bg-red-100 text-red-800',
            default      => 'bg-slate-100 text-slate-600',
        };
    }

    public function getEstadoLabelAttribute(): string
    {
        return match($this->estado) {
            'pendiente'   => 'Pendiente',
            'en_proceso'  => 'En Proceso',
            'completado'  => 'Completado',
            'cancelado'   => 'Cancelado',
            default       => $this->estado,
        };
    }

    public function getEstadoBadgeAttribute(): string
    {
        return match($this->estado) {
            'pendiente'  => 'bg-slate-100 text-slate-700',
            'en_proceso' => 'bg-yellow-100 text-yellow-800',
            'completado' => 'bg-green-100 text-green-800',
            'cancelado'  => 'bg-red-100 text-red-800',
            default      => 'bg-slate-100 text-slate-600',
        };
    }

    public function getPrioridadBadgeAttribute(): string
    {
        return match($this->prioridad) {
            'baja'    => 'bg-slate-100 text-slate-600',
            'media'   => 'bg-blue-100 text-blue-700',
            'alta'    => 'bg-orange-100 text-orange-700',
            'critica' => 'bg-red-600 text-white',
            default   => 'bg-slate-100 text-slate-600',
        };
    }

    public function getDuracionAttribute(): ?string
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) return null;
        $mins = $this->fecha_inicio->diffInMinutes($this->fecha_fin);
        if ($mins < 60) return "{$mins} min";
        $hrs  = intdiv($mins, 60);
        $rest = $mins % 60;
        return $rest > 0 ? "{$hrs}h {$rest}min" : "{$hrs}h";
    }

    public static function checklistTemplate(string $tipo): array
    {
        $base = [
            ['categoria' => 'Hardware',          'actividad' => 'Limpieza interna',                 'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Hardware',          'actividad' => 'Limpieza externa',                 'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Hardware',          'actividad' => 'Limpieza de ventiladores',         'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Hardware',          'actividad' => 'Revisión de conexiones',           'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Hardware',          'actividad' => 'Verificación de temperaturas',     'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Hardware',          'actividad' => 'Verificación de batería',          'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Sistema Operativo', 'actividad' => 'Actualización del sistema',        'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Sistema Operativo', 'actividad' => 'Actualización de controladores',  'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Sistema Operativo', 'actividad' => 'Limpieza de archivos temporales', 'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Sistema Operativo', 'actividad' => 'Revisión de espacio libre',       'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Seguridad',         'actividad' => 'Actualización de antivirus',      'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Seguridad',         'actividad' => 'Escaneo de malware',              'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Seguridad',         'actividad' => 'Verificación de respaldos',       'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Red',               'actividad' => 'Verificación de conectividad',    'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Red',               'actividad' => 'Prueba de recursos compartidos',  'estado' => 'pendiente', 'observaciones' => ''],
            ['categoria' => 'Red',               'actividad' => 'Validación de impresoras',        'estado' => 'pendiente', 'observaciones' => ''],
        ];

        if ($tipo === 'correctivo') {
            array_unshift($base, ...[
                ['categoria' => 'Diagnóstico', 'actividad' => 'Identificación del problema',       'estado' => 'pendiente', 'observaciones' => ''],
                ['categoria' => 'Diagnóstico', 'actividad' => 'Revisión de logs del sistema',      'estado' => 'pendiente', 'observaciones' => ''],
                ['categoria' => 'Diagnóstico', 'actividad' => 'Prueba de hardware involucrado',    'estado' => 'pendiente', 'observaciones' => ''],
            ]);
        }

        if ($tipo === 'emergente') {
            array_unshift($base, ...[
                ['categoria' => 'Emergencia', 'actividad' => 'Evaluación inicial del incidente',   'estado' => 'pendiente', 'observaciones' => ''],
                ['categoria' => 'Emergencia', 'actividad' => 'Contención del problema',            'estado' => 'pendiente', 'observaciones' => ''],
            ]);
        }

        return $base;
    }
}
