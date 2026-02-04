<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Empleado;
use App\Models\Logistica\Cliente;       // <--- Importante: Agregado
use App\Models\Logistica\AgenteAduanal; // <--- Importante: Agregado
use App\Models\Logistica\PedimentoOperacion;
use App\Models\Logistica\PostOperacion;
use App\Models\Logistica\PostOperacionOperacion;
use App\Models\Logistica\ValorCampoPersonalizado;
use App\Models\Logistica\OperacionComentario;
use App\Models\Logistica\HistoricoMatrizSgm;

class OperacionLogistica extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\OperacionLogisticaFactory::new();
    }

    protected $table = 'operaciones_logisticas';

    protected $fillable = [
        'folio',
        'ejecutivo',
        'cliente',
        'agente_aduanal',
        'transporte',
        'operacion',
        'proveedor_o_cliente',
        'fecha_embarque',
        'no_factura',
        'tipo_carga',
        'tipo_incoterm',
        'tipo_operacion_enum',
        'clave',
        'referencia_interna',
        'aduana',
        'referencia_aa',
        'no_pedimento',
        'fecha_arribo_aduana',
        'guia_bl',
        'puerto_salida',
        'fecha_modulacion',
        'fecha_arribo_planta',
        'resultado',
        'target',
        'dias_transito',
        'post_operacion_id',
        'status_calculado',
        'status_manual',
        'fecha_status_manual',
        'color_status',
        'dias_transcurridos_calculados',
        'fecha_ultimo_calculo',
        'comentarios',
        // Campos Excel
        'in_charge',
        'proveedor',
        'tipo_previo',
        'fecha_etd',
        'fecha_zarpe',
        'pedimento_en_carpeta',
        'referencia_cliente',
        'mail_subject',
    ];

    protected $casts = [
        'fecha_embarque' => 'date',
        'fecha_arribo_aduana' => 'date',
        'fecha_modulacion' => 'date',
        'fecha_arribo_planta' => 'date',
        'fecha_etd' => 'date',
        'fecha_zarpe' => 'date',
        'pedimento_en_carpeta' => 'boolean',
        'fecha_status_manual' => 'datetime',
        'resultado' => 'integer',
        'target' => 'integer',
        'dias_transito' => 'integer',
        'dias_transcurridos_calculados' => 'integer',
        'fecha_ultimo_calculo' => 'datetime',
        'procesado' => 'boolean'
    ];

    // ==========================================
    // RELACIONES (AQUÍ ESTABA EL ERROR)
    // ==========================================

    /**
     * Relación para obtener el objeto Cliente completo.
     * Se usa 'cliente' (nombre o ID en tu tabla) para buscar en la tabla 'clientes'.
     * NOTA: Si tu columna 'cliente' guarda TEXTO (ej: "Empresa X"), esto intentará buscar
     * un ID con ese texto y fallará. Si guardas IDs, funcionará perfecto.
     */
    public function clienteRelacion()
    {
        // Intenta relacionar la columna 'cliente' de esta tabla con el 'id' de la tabla clientes
        return $this->belongsTo(Cliente::class, 'cliente', 'id')
                    ->withDefault(['razon_social' => 'Cliente no encontrado']);
    }

    /**
     * Relación opcional para Agente Aduanal (por si la necesitas luego)
     */
    public function agenteAduanalRelacion()
    {
        return $this->belongsTo(AgenteAduanal::class, 'agente_aduanal', 'id');
    }

    public function ejecutivo()
    {
        return $this->belongsTo(Empleado::class, 'ejecutivo_empleado_id');
    }

    public function pedimentoStatus()
    {
        return $this->hasOne(PedimentoOperacion::class, 'operacion_logistica_id', 'id');
    }

    public function comentarios()
    {
        return $this->hasMany(OperacionComentario::class, 'operacion_logistica_id');
    }

    public function comentariosCronologicos()
    {
        return $this->hasMany(OperacionComentario::class, 'operacion_logistica_id')->cronologico();
    }

    public function valoresCamposPersonalizados()
    {
        return $this->hasMany(ValorCampoPersonalizado::class, 'operacion_logistica_id');
    }

    public function postOperaciones()
    {
        return $this->belongsToMany(
            PostOperacion::class,
            'post_operacion_operacion',
            'operacion_logistica_id',
            'post_operacion_id'
        )->withPivot([
            'status',
            'fecha_asignacion',
            'fecha_completado',
            'notas_especificas'
        ])->withTimestamps();
    }

    public function asignacionesPostOperaciones()
    {
        return $this->hasMany(PostOperacionOperacion::class, 'operacion_logistica_id');
    }

    public function historicoMatrizSgm()
    {
        return $this->hasMany(HistoricoMatrizSgm::class);
    }

    public function pedimentoOperacion()
    {
        return $this->hasOne(PedimentoOperacion::class, 'operacion_logistica_id');
    }

    // ==========================================
    // ACCESSORS Y MUTATORS
    // ==========================================

    public function getStatusActualAttribute()
    {
        if (!empty($this->status_manual)) {
            return $this->status_manual;
        }
        return $this->status_calculado;
    }

    public function setTransporteAttribute($value)
    {
        $this->attributes['transporte'] = $value ? strtoupper(trim($value)) : $value;
    }

    public function getStatusColorAttribute()
    {
        return match($this->color_status) {
            'verde' => 'bg-green-100 text-green-800',
            'amarillo' => 'bg-yellow-100 text-yellow-800',
            'rojo' => 'bg-red-100 text-red-800',
            'sin_fecha' => 'bg-gray-100 text-gray-800',
            default => 'bg-slate-100 text-slate-800',
        };
    }

    public function getStatusTextoAttribute()
    {
        return match($this->color_status) {
            'verde' => 'Completado',
            'amarillo' => 'En Proceso',
            'rojo' => 'Fuera de Métrica',
            'sin_fecha' => 'Sin Fecha',
            default => 'Desconocido',
        };
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeConEjecutivoLogistica($query)
    {
        return $query->whereHas('ejecutivo', function ($q) {
            $q->where('area', 'Logística')->orWhere('area', 'Logistica');
        });
    }

    public function scopePorStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeEnTransito($query)
    {
        return $query->whereIn('status', ['En Tránsito', 'En Aduana', 'Pendiente']);
    }

    // ==========================================
    // MÉTODOS DE CÁLCULO Y LÓGICA
    // ==========================================

    public function crearComentario($comentario, $tipoAccion = 'comentario', $usuario = null)
    {
        return $this->comentarios()->create([
            'comentario' => $comentario,
            'status_en_momento' => $this->status_actual,
            'tipo_accion' => $tipoAccion,
            'usuario_nombre' => $usuario['nombre'] ?? auth()->user()->name ?? 'Sistema',
            'usuario_id' => $usuario['id'] ?? auth()->id() ?? null,
            'contexto_operacion' => [
                'status_calculado' => $this->status_calculado,
                'status_manual' => $this->status_manual,
                'color_status' => $this->color_status,
                'target' => $this->target,
                'resultado' => $this->resultado,
                'dias_transcurridos' => $this->dias_transcurridos_calculados ?? 0,
                'fecha_arribo_aduana' => $this->fecha_arribo_aduana?->format('Y-m-d'),
                'fecha_arribo_planta' => $this->fecha_arribo_planta?->format('Y-m-d'),
            ]
        ]);
    }

    public function crearComentarioInicialOperacion($comentarioInicial = null)
    {
        $comentario = $comentarioInicial ?: ($this->comentarios_campo ?: 'Operación registrada en el sistema');
        return $this->crearComentario($comentario, 'creacion', ['nombre' => 'Sistema', 'id' => null]);
    }

    public function calcularDiasTransito()
    {
        if ($this->fecha_arribo_aduana && $this->fecha_modulacion) {
            $this->resultado = $this->fecha_arribo_aduana->diffInDays($this->fecha_modulacion);
        } else {
            $this->resultado = null;
        }

        if ($this->fecha_embarque && $this->fecha_arribo_planta) {
            $this->dias_transito = $this->fecha_embarque->diffInDays($this->fecha_arribo_planta);
        } else {
            $this->dias_transito = null;
        }

        if (!$this->fecha_embarque) {
            $this->dias_transcurridos_calculados = 0;
            $this->status_calculado = 'In Process';
            $this->color_status = 'sin_fecha';
            return null;
        }

        $fechaInicio = $this->fecha_embarque;
        $fechaFin = $this->fecha_arribo_planta ?? now();
        $diasTranscurridos = $fechaInicio->diffInDays($fechaFin);

        $this->dias_transcurridos_calculados = $diasTranscurridos;

        return $diasTranscurridos;
    }

    public function estaRetrasada()
    {
        if ($this->target && $this->dias_transcurridos_calculados) {
            return $this->dias_transcurridos_calculados > $this->target;
        }
        return false;
    }

    public function calcularTargetAutomatico()
    {
        $tipoOperacion = $this->tipo_operacion_enum ?? $this->tipo_operacion;

        if (empty($tipoOperacion)) {
            return null;
        }

        return match($tipoOperacion) {
            'Terrestre', 'Aerea', 'Ferrocarril' => 3,
            'Maritima' => 7,
            default => 3,
        };
    }

    public function calcularStatusPorDias()
    {
        $statusAnterior = $this->status_calculado;
        $colorAnterior = $this->color_status;
        $target = $this->target ?? $this->calcularTargetAutomatico() ?? 3;

        if ($this->fecha_arribo_aduana) {
            $fechaAduana = \Carbon\Carbon::parse($this->fecha_arribo_aduana);
            $fechaActual = now();
            $diasTranscurridos = $fechaAduana->diffInDays($fechaActual);

            if ($diasTranscurridos > $target) {
                $nuevoColor = 'rojo';
                $nuevoStatus = 'Out of Metric';
            } else {
                $nuevoColor = 'amarillo';
                $nuevoStatus = 'In Process';
            }
        } else {
            $fechaRegistro = $this->created_at ?? now();
            $fechaActual = now();
            $diasTranscurridos = $fechaRegistro->diffInDays($fechaActual);

            $nuevoStatus = 'In Process';
            $nuevoColor = 'amarillo';
        }

        if ($this->status_manual === 'Done') {
            $nuevoStatus = 'Done';
            $nuevoColor = 'verde';
        }

        $this->dias_transcurridos_calculados = $diasTranscurridos ?? 0;
        $this->status_calculado = $nuevoStatus;
        $this->color_status = $nuevoColor;
        $this->fecha_ultimo_calculo = now();

        $huboCambio = ($statusAnterior !== $nuevoStatus) || ($colorAnterior !== $nuevoColor);

        return [
            'status' => $nuevoStatus,
            'color' => $nuevoColor,
            'dias_transcurridos' => $diasTranscurridos ?? 0,
            'target' => $target,
            'cambio' => $huboCambio,
            'status_anterior' => $statusAnterior,
            'color_anterior' => $colorAnterior
        ];
    }

    public function generarHistorialCambioStatus($resultado, $esManual = false, $accionManual = null)
    {
        if (str_contains($accionManual ?? '', 'Creación') || str_contains($accionManual ?? '', 'Registro inicial')) {
            return null;
        }

        if (!$esManual && !str_contains($accionManual ?? '', 'Actualización')) {
            if (!$resultado['cambio'] && $this->historicoMatrizSgm()->exists()) {
                return null;
            }
        }

        $descripcion = $accionManual ?: "Status actualizado automáticamente. Status: {$resultado['status']}";
        $descripcion .= ". Días: {$resultado['dias_transcurridos']}, Target: {$resultado['target']}";

        $observaciones = !empty($this->comentarios) ? $this->comentarios : $descripcion;

        if ($this->exists) {
            $historial = $this->historicoMatrizSgm()->create([
                'fecha_registro' => $this->created_at ?? now(),
                'fecha_arribo_aduana' => $this->fecha_arribo_aduana,
                'dias_transcurridos' => $resultado['dias_transcurridos'],
                'target_dias' => $resultado['target'],
                'color_status' => $resultado['color'],
                'operacion_status' => $resultado['status'],
                'observaciones' => $observaciones
            ]);

            $this->crearComentario($descripcion, $esManual ? 'cambio_manual_status' : 'actualizacion_automatica');
            return $historial;
        }
        return null;
    }

    public function actualizarStatusAutomaticamente($guardarCambios = true)
    {
        $resultado = $this->calcularStatusPorDias();
        $this->generarHistorialCambioStatus($resultado);
        
        if ($guardarCambios) {
            $this->saveQuietly();
        }
        return $resultado;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($operacion) {
            if ($operacion->isDirty(['fecha_arribo_aduana', 'target', 'fecha_arribo_planta']) || !$operacion->exists) {
                $operacion->calcularStatusPorDias();
            }
        });

        static::created(function ($operacion) {
            $operacion->refresh();
            $resultado = $operacion->calcularStatusPorDias();
            $operacion->generarHistorialCambioStatus($resultado);
            $operacion->saveQuietly();
        });
    }
}