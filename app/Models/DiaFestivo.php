<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiaFestivo extends Model
{
    use HasFactory;

    protected $table = 'dias_festivos';

    protected $fillable = [
        'nombre',
        'fecha',
        'tipo',
        'es_anual',
        'descripcion',
        'activo',
        'notificacion_enviada',
        'notificacion_enviada_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'es_anual' => 'boolean',
        'activo' => 'boolean',
        'notificacion_enviada' => 'boolean',
        'notificacion_enviada_at' => 'datetime',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDelTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeProximos($query, int $dias = 7)
    {
        $hoy = Carbon::today();
        $fin = $hoy->copy()->addDays($dias);

        return $query->where('activo', true)
            ->where(function ($q) use ($hoy, $fin) {
                $q->whereBetween('fecha', [$hoy, $fin])
                    ->orWhere(function ($q2) use ($hoy) {
                        $q2->where('es_anual', true)
                            ->whereRaw("DATE_FORMAT(fecha, '%m-%d') >= ?", [$hoy->format('m-d')])
                            ->whereRaw("DATE_FORMAT(fecha, '%m-%d') <= ?", [$hoy->copy()->addDays(7)->format('m-d')]);
                    });
            })
            ->orderBy('fecha');
    }

    public function scopeParaFecha($query, $fecha)
    {
        $fechaCarbon = Carbon::parse($fecha);
        $mesDia = $fechaCarbon->format('m-d');

        return $query->where('activo', true)
            ->where(function ($q) use ($fechaCarbon, $mesDia) {
                $q->whereDate('fecha', $fechaCarbon)
                    ->orWhere(function ($q2) use ($mesDia) {
                        $q2->where('es_anual', true)
                            ->whereRaw("DATE_FORMAT(fecha, '%m-%d') = ?", [$mesDia]);
                    });
            });
    }

    public function scopeSinNotificacion($query)
    {
        return $query->where('notificacion_enviada', false);
    }

    public function esHoy(): bool
    {
        $hoy = Carbon::today();

        return $this->esParaFecha($hoy);
    }

    public function esManana(): bool
    {
        $manana = Carbon::tomorrow();

        return $this->esParaFecha($manana);
    }

    public function esProximo(int $dias = 7): bool
    {
        $hoy = Carbon::today();
        $fin = $hoy->copy()->addDays($dias);
        $fechaCheck = Carbon::parse($this->fecha);

        if ($this->es_anual) {
            $fechaCheck->year($hoy->year);
            if ($fechaCheck->lt($hoy)) {
                $fechaCheck->addYear();
            }
        }

        return $fechaCheck->gte($hoy) && $fechaCheck->lte($fin);
    }

    public function esParaFecha(Carbon $fecha): bool
    {
        $mesDia = $fecha->format('m-d');

        if ($this->es_anual) {
            $fechaFestivo = Carbon::parse($this->fecha)->format('m-d');

            return $mesDia === $fechaFestivo;
        }

        return $this->fecha->isSameDay($fecha);
    }

    public function getFechaFormateadaAttribute(): string
    {
        $fecha = $this->fecha;
        if ($this->es_anual) {
            $fecha = Carbon::parse($this->fecha)->locale('es')->translatedFormat('j \d\e F');
        } else {
            $fecha = $this->fecha->locale('es')->translatedFormat('j \d\e F \d\e Y');
        }

        return $fecha;
    }

    public function getTipoLabelAttribute(): string
    {
        return $this->tipo === 'festivo' ? 'Día Festivo' : 'Día Inhábil';
    }

    public function getFechaEfectiva(): Carbon
    {
        if ($this->es_anual) {
            $fecha = Carbon::parse($this->fecha)->year(Carbon::now()->year);
            if ($fecha->lt(Carbon::today())) {
                $fecha->addYear();
            }

            return $fecha;
        }

        return Carbon::parse($this->fecha);
    }

    public static function obtenerProximoFestivo(?int $dias = 7): ?self
    {
        return static::proximos($dias)->first();
    }

    public static function esDiaFestivo(Carbon $fecha): bool
    {
        return static::paraFecha($fecha)->exists();
    }

    public static function esDiaInhabil(Carbon $fecha): bool
    {
        return static::paraFecha($fecha)->delTipo('inhabil')->exists();
    }

    public function crearRecordatorio(?User $creadoPor = null): Recordatorio
    {
        return Recordatorio::create([
            'tipo' => $this->tipo === 'festivo' ? Recordatorio::TIPO_EVENTO_PERSONAL : 'documento_por_vencer',
            'titulo' => "📅 {$this->nombre}",
            'descripcion' => $this->descripcion ?? "Día {$this->tipo}: {$this->nombre}",
            'fecha_evento' => $this->getFechaEfectiva(),
            'dias_anticipacion' => 1,
            'tabla_relacionada' => 'dias_festivos',
            'registro_id' => $this->id,
            'empleado_id' => null,
            'creado_por' => $creadoPor?->id,
            'leido' => false,
            'activo' => true,
            'es_manual' => false,
            'color_evento' => $this->tipo === 'festivo' ? '#EF4444' : '#F97316',
        ]);
    }

    public function enviarNotificaciones(): int
    {
        $empleados = Empleado::where('es_activo', true)
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        $enviados = 0;

        foreach ($empleados as $empleado) {
            if (! $empleado->user) {
                continue;
            }

            try {
                $empleado->user->notify(new \App\Notifications\FestivoNotification($this, true));
                $enviados++;
            } catch (\Exception $e) {
                \Log::error("Error al enviar notificación a {$empleado->nombre}: ".$e->getMessage());
            }
        }

        $this->update([
            'notificacion_enviada' => true,
            'notificacion_enviada_at' => now(),
        ]);

        return $enviados;
    }
}
