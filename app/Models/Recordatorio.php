<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Recordatorio extends Model
{
    use HasFactory;

    protected $table = 'recordatorios';

    const TIPO_CUMPLEAÑOS = 'cumpleaños';
    const TIPO_ANIVERSARIO = 'aniversario_laboral';
    const TIPO_DOCUMENTO_VENCER = 'documento_por_vencer';
    const TIPO_DOCUMENTO_VENCIDO = 'documento_vencido';
    const TIPO_CONTRATO_VENCER = 'contrato_por_vencer';
    const TIPO_EVALUACION_PENDIENTE = 'evaluacion_pendiente';

    const TIPOS = [
        self::TIPO_CUMPLEAÑOS => 'Cumpleaños',
        self::TIPO_ANIVERSARIO => 'Aniversario Laboral',
        self::TIPO_DOCUMENTO_VENCER => 'Documento por Vencer',
        self::TIPO_DOCUMENTO_VENCIDO => 'Documento Vencido',
        self::TIPO_CONTRATO_VENCER => 'Fin de Contrato',
        self::TIPO_EVALUACION_PENDIENTE => 'Evaluación Pendiente',
    ];

    protected $fillable = [
        'tipo',
        'titulo',
        'descripcion',
        'fecha_evento',
        'dias_anticipacion',
        'tabla_relacionada',
        'registro_id',
        'empleado_id',
        'creado_por',
        'leido',
        'leido_at',
        'activo',
    ];

    protected $casts = [
        'fecha_evento' => 'date',
        'leido_at' => 'datetime',
        'leido' => 'boolean',
        'activo' => 'boolean',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function scopeProximos($query, int $dias = 30)
    {
        return $query->where('activo', true)
            ->whereDate('fecha_evento', '>=', Carbon::today())
            ->whereDate('fecha_evento', '<=', Carbon::today()->addDays($dias));
    }

    public function scopeNoLeidos($query)
    {
        return $query->where('leido', false)->where('activo', true);
    }

    public function scopeVencidos($query)
    {
        return $query->where('activo', true)
            ->whereDate('fecha_evento', '<', Carbon::today());
    }

    public function scopeDelTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeDeEmpleado($query, int $empleadoId)
    {
        return $query->where('empleado_id', $empleadoId);
    }

    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->fecha_evento) {
            return null;
        }
        return Carbon::today()->diffInDays($this->fecha_evento, false);
    }

    public function getUrgenciaAttribute(): string
    {
        $dias = $this->dias_restantes;

        if ($dias === null) {
            return 'sin_fecha';
        }

        if ($dias < 0) {
            return 'vencido';
        }

        if ($dias <= 3) {
            return 'critico';
        }

        if ($dias <= 7) {
            return 'alerta';
        }

        if ($dias <= 15) {
            return 'pronto';
        }

        return 'normal';
    }

    public function getColorUrgenciaAttribute(): array
    {
        $colores = [
            'vencido' => [
                'bg' => 'bg-red-100',
                'text' => 'text-red-700',
                'border' => 'border-red-200',
                'dot' => 'bg-red-500',
                'badge' => 'bg-red-100 text-red-700',
            ],
            'critico' => [
                'bg' => 'bg-orange-100',
                'text' => 'text-orange-700',
                'border' => 'border-orange-200',
                'dot' => 'bg-orange-500',
                'badge' => 'bg-orange-100 text-orange-700',
            ],
            'alerta' => [
                'bg' => 'bg-yellow-50',
                'text' => 'text-yellow-700',
                'border' => 'border-yellow-200',
                'dot' => 'bg-yellow-500',
                'badge' => 'bg-yellow-100 text-yellow-700',
            ],
            'pronto' => [
                'bg' => 'bg-blue-50',
                'text' => 'text-blue-700',
                'border' => 'border-blue-200',
                'dot' => 'bg-blue-500',
                'badge' => 'bg-blue-100 text-blue-700',
            ],
            'normal' => [
                'bg' => 'bg-emerald-50',
                'text' => 'text-emerald-700',
                'border' => 'border-emerald-200',
                'dot' => 'bg-emerald-500',
                'badge' => 'bg-emerald-100 text-emerald-700',
            ],
            'sin_fecha' => [
                'bg' => 'bg-slate-50',
                'text' => 'text-slate-700',
                'border' => 'border-slate-200',
                'dot' => 'bg-slate-400',
                'badge' => 'bg-slate-100 text-slate-700',
            ],
        ];

        return $colores[$this->urgencia] ?? $colores['normal'];
    }

    public function getIconoTipoAttribute(): string
    {
        $iconos = [
            self::TIPO_CUMPLEAÑOS => '🎂',
            self::TIPO_ANIVERSARIO => '📅',
            self::TIPO_DOCUMENTO_VENCER => '📄',
            self::TIPO_DOCUMENTO_VENCIDO => '⚠️',
            self::TIPO_CONTRATO_VENCER => '📋',
            self::TIPO_EVALUACION_PENDIENTE => '📊',
        ];

        return $iconos[$this->tipo] ?? '📌';
    }

    public function marcarLeido(): void
    {
        $this->update([
            'leido' => true,
            'leido_at' => Carbon::now(),
        ]);
    }

    public static function generarCumpleaños(Empleado $empleado, ?User $creadoPor = null): ?self
    {
        if (!$empleado->fecha_nacimiento) {
            return null;
        }

        $proximoCumple = Carbon::parse($empleado->fecha_nacimiento)
            ->year(Carbon::now()->year);

        if ($proximoCumple->lt(Carbon::today())) {
            $proximoCumple->addYear();
        }

        if ($proximoCumple->gt(Carbon::today()->addDays(30))) {
            return null;
        }

        $existe = self::where('empleado_id', $empleado->id)
            ->where('tipo', self::TIPO_CUMPLEAÑOS)
            ->whereYear('fecha_evento', $proximoCumple->year)
            ->first();

        if ($existe) {
            return $existe;
        }

        return self::create([
            'tipo' => self::TIPO_CUMPLEAÑOS,
            'titulo' => "Cumpleaños de {$empleado->nombre}",
            'descripcion' => "{$empleado->nombre} cumple años el {$proximoCumple->format('d \d\e F')}",
            'fecha_evento' => $proximoCumple,
            'dias_anticipacion' => 7,
            'tabla_relacionada' => 'empleados',
            'registro_id' => $empleado->id,
            'empleado_id' => $empleado->id,
            'creado_por' => $creadoPor?->id,
            'activo' => true,
        ]);
    }

    public static function generarAniversario(Empleado $empleado, ?User $creadoPor = null): ?self
    {
        if (!$empleado->fecha_ingreso) {
            return null;
        }

        $aniversario = Carbon::parse($empleado->fecha_ingreso);

        if ($aniversario->isFuture()) {
            return null;
        }

        $anios = $aniversario->age;
        $proximoAniversario = $aniversario->copy()->addYears($anios + 1);

        if ($proximoAniversario->lt(Carbon::today())) {
            return null;
        }

        // Solo generar si está dentro de 30 días
        if ($proximoAniversario->gt(Carbon::today()->addDays(30))) {
            return null;
        }

        $existe = self::where('empleado_id', $empleado->id)
            ->where('tipo', self::TIPO_ANIVERSARIO)
            ->whereYear('fecha_evento', $proximoAniversario->year)
            ->first();

        if ($existe) {
            return $existe;
        }

        return self::create([
            'tipo' => self::TIPO_ANIVERSARIO,
            'titulo' => ($anios + 1) . " años - {$empleado->nombre}",
            'descripcion' => "{$empleado->nombre} cumple " . ($anios + 1) . " años en la empresa el " . $proximoAniversario->format('d \d\e F'),
            'fecha_evento' => $proximoAniversario,
            'dias_anticipacion' => 7,
            'tabla_relacionada' => 'empleados',
            'registro_id' => $empleado->id,
            'empleado_id' => $empleado->id,
            'creado_por' => $creadoPor?->id,
            'activo' => true,
        ]);
    }

    public static function generarRecordatorioDocumento(EmpleadoDocumento $documento, ?User $creadoPor = null): ?self
    {
        if (!$documento->fecha_vencimiento || !$documento->empleado) {
            return null;
        }

        $tipo = $documento->fecha_vencimiento->isPast()
            ? self::TIPO_DOCUMENTO_VENCIDO
            : self::TIPO_DOCUMENTO_VENCER;

        if (stripos($documento->nombre, 'contrato') !== false) {
            $tipo = self::TIPO_CONTRATO_VENCER;
        }

        $existe = self::where('tabla_relacionada', 'empleado_documentos')
            ->where('registro_id', $documento->id)
            ->whereYear('fecha_evento', $documento->fecha_vencimiento->year)
            ->whereMonth('fecha_evento', $documento->fecha_vencimiento->month)
            ->first();

        if ($existe) {
            if ($tipo === self::TIPO_DOCUMENTO_VENCIDO) {
                $existe->update([
                    'tipo' => self::TIPO_DOCUMENTO_VENCIDO,
                    'titulo' => "Documento Vencido: {$documento->nombre}",
                    'descripcion' => "El documento '{$documento->nombre}' de {$documento->empleado->nombre} venció el {$documento->fecha_vencimiento->format('d/m/Y')}",
                    'activo' => true,
                ]);
            }
            return $existe;
        }

        $diasAnticipacion = 30;
        if ($documento->fecha_vencimiento->diffInDays(Carbon::today()) <= 7) {
            $diasAnticipacion = 7;
        }

        return self::create([
            'tipo' => $tipo,
            'titulo' => ($tipo === self::TIPO_CONTRATO_VENCER ? "Fin de Contrato: " : "Documento por Vencer: ") . $documento->nombre,
            'descripcion' => "El documento '{$documento->nombre}' de {$documento->empleado->nombre} vence el {$documento->fecha_vencimiento->format('d/m/Y')}",
            'fecha_evento' => $documento->fecha_vencimiento,
            'dias_anticipacion' => $diasAnticipacion,
            'tabla_relacionada' => 'empleado_documentos',
            'registro_id' => $documento->id,
            'empleado_id' => $documento->empleado_id,
            'creado_por' => $creadoPor?->id,
            'activo' => true,
        ]);
    }

    public static function generarRecordatorioContrato(Empleado $empleado, ?User $creadoPor = null): ?self
    {
        if (!$empleado->fecha_fin_contrato) {
            return null;
        }

        if ($empleado->tipo_contrato === 'Indeterminado') {
            return null;
        }

        if ($empleado->fecha_fin_contrato->lt(Carbon::today())) {
            $tipo = self::TIPO_DOCUMENTO_VENCIDO;
        } else {
            $tipo = self::TIPO_CONTRATO_VENCER;
        }

        $existe = self::where('tabla_relacionada', 'empleados_contrato')
            ->where('registro_id', $empleado->id)
            ->whereYear('fecha_evento', $empleado->fecha_fin_contrato->year)
            ->whereMonth('fecha_evento', $empleado->fecha_fin_contrato->month)
            ->first();

        if ($existe) {
            $existe->update([
                'tipo' => $tipo,
                'titulo' => "Fin de Contrato: {$empleado->nombre}",
                'descripcion' => "El contrato de {$empleado->nombre} (" . ($empleado->tipo_contrato ?? 'Determinado') . ") " . ($tipo === self::TIPO_DOCUMENTO_VENCIDO ? 'venció' : 'vence') . " el " . $empleado->fecha_fin_contrato->format('d/m/Y'),
                'activo' => true,
            ]);
            return $existe;
        }

        $diasAnticipacion = 30;
        if ($empleado->fecha_fin_contrato->diffInDays(Carbon::today()) <= 7) {
            $diasAnticipacion = 7;
        }

        return self::create([
            'tipo' => $tipo,
            'titulo' => "Fin de Contrato: {$empleado->nombre}",
            'descripcion' => "El contrato de {$empleado->nombre} (" . ($empleado->tipo_contrato ?? 'Determinado') . ") " . ($tipo === self::TIPO_DOCUMENTO_VENCIDO ? 'venció' : 'vence') . " el " . $empleado->fecha_fin_contrato->format('d/m/Y'),
            'fecha_evento' => $empleado->fecha_fin_contrato,
            'dias_anticipacion' => $diasAnticipacion,
            'tabla_relacionada' => 'empleados_contrato',
            'registro_id' => $empleado->id,
            'empleado_id' => $empleado->id,
            'creado_por' => $creadoPor?->id,
            'activo' => true,
        ]);
    }
}
