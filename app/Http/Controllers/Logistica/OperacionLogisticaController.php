<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

// Paquetes Spatie (Asegúrate de haber instalado: composer require spatie/laravel-query-builder)
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

// Modelos
use App\Models\Logistica\OperacionLogistica;
use App\Models\Logistica\Cliente;
use App\Models\Logistica\AgenteAduanal;
use App\Models\Logistica\Transporte;
use App\Models\Logistica\Aduana;
use App\Models\Logistica\Pedimento;
use App\Models\Logistica\CampoPersonalizadoMatriz;
use App\Models\Logistica\ColumnaVisibleEjecutivo;
use App\Models\Empleado;

// Requests
use App\Http\Requests\Logistica\StoreOperacionRequest;
use App\Http\Requests\Logistica\UpdateOperacionRequest;

class OperacionLogisticaController extends Controller
{
    public function __construct()
    {
        // Eliminamos la dependencia de OperacionFilterService
        $this->middleware('auth')->except(['consultaPublica', 'buscarOperacionPublica']);
    }

    /**
     * Muestra la Matriz de Seguimiento (Index)
     * Implementación optimizada con Spatie Query Builder.
     */
    public function index(Request $request)
    {
        // 1. Lógica de verificación de permisos
        $usuarioActual = auth()->user();
        $empleadoActual = null;
        $esAdmin = false;
        $modoPreview = false;
        $empleadoPreview = null;

        if ($usuarioActual) {
            $empleadoActual = Empleado::where('correo', $usuarioActual->email)
                ->orWhere('nombre', 'like', '%' . $usuarioActual->name . '%')->first();
            $esAdmin = $usuarioActual->hasRole('admin');
        }

        if ($esAdmin && $request->has('preview_as')) {
            $empleadoPreview = Empleado::find($request->get('preview_as'));
            if ($empleadoPreview) $modoPreview = true;
        }

        // 2. Construcción del Query con Spatie Query Builder
        $operaciones = QueryBuilder::for(OperacionLogistica::class)
            // Cargamos relaciones para evitar N+1 (optimización)
            ->with(['ejecutivo', 'postOperaciones', 'valoresCamposPersonalizados.campo', 'clienteRelacion'])
            ->allowedFilters([
                // Filtro exacto para Cliente (usa el select)
                AllowedFilter::exact('cliente', 'cliente_id')->ignore('todos'), // Ajusta 'cliente_id' si tu columna es esa, o 'cliente' si guardas el nombre

                // Filtro parcial para Ejecutivo
                AllowedFilter::partial('ejecutivo')->ignore('todos'),

                // Filtro personalizado para Status (Manual o Calculado)
                AllowedFilter::callback('status', function (Builder $query, $value) {
                    if ($value === 'todos') return;
                    $query->where(function($q) use ($value) {
                        $q->where('status_manual', $value)
                          ->orWhere('status_calculado', $value);
                    });
                }),

                // Filtros de Fechas (Callbacks simples)
                AllowedFilter::callback('fecha_creacion_desde', fn ($q, $v) => $q->whereDate('created_at', '>=', $v)),
                AllowedFilter::callback('fecha_creacion_hasta', fn ($q, $v) => $q->whereDate('created_at', '<=', $v)),

                // Búsqueda General (Search box)
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where(function($q) use ($value) {
                        $q->where('operacion', 'like', "%{$value}%")
                          ->orWhere('no_pedimento', 'like', "%{$value}%")
                          ->orWhere('referencia_cliente', 'like', "%{$value}%");
                    });
                }),
            ])
            // Seguridad: Si no es admin, solo ve sus operaciones
            ->when(!$esAdmin && $empleadoActual, function($q) use ($empleadoActual) {
                $q->where('ejecutivo', 'LIKE', '%' . $empleadoActual->nombre . '%');
            })
            ->when(!$esAdmin && !$empleadoActual, function($q) {
                $q->where('id', 0); // Bloquear si no hay empleado asociado
            })
            ->allowedSorts(['created_at', 'fecha_arribo', 'cliente', 'operacion'])
            ->defaultSort('-created_at')
            ->paginate(10)
            ->appends($request->query()); // Mantiene los filtros al paginar

        // 3. Cargar catálogos para los filtros de la vista
        $datosVista = $this->cargarDatosVista($esAdmin, $empleadoActual, $modoPreview, $empleadoPreview);

        // Mapeamos 'empleados' a 'ejecutivos' para que coincida con la vista que te pasé
        $datosVista['ejecutivos'] = $datosVista['empleados'];

        return view('Logistica.matriz-seguimiento', array_merge(
            compact('operaciones', 'empleadoActual', 'esAdmin', 'modoPreview', 'empleadoPreview'),
            $datosVista,
            $request->all()
        ));
    }

    // ==========================================
    // MÉTODOS DE CREACIÓN Y EDICIÓN (CRUD)
    // ==========================================

    public function create()
    {
        return response()->json([
            'clientes' => Cliente::orderBy('cliente')->get(['id', 'cliente']),
            'agentesAduanales' => AgenteAduanal::orderBy('agente_aduanal')->get(['id', 'agente_aduanal']),
            'ejecutivos' => Empleado::where('area', 'LIKE', '%Logist%')->orderBy('nombre')->get(['id', 'nombre']),
            'tipos_operacion' => ['Aerea', 'Terrestre', 'Maritima', 'Ferrocarril'],
            'operaciones' => ['Exportacion', 'Importacion'],
            'status_options' => ['In Process', 'Done', 'Out of Metric']
        ]);
    }

    public function store(StoreOperacionRequest $request)
    {
        try {
            $data = $request->validated();
            if (empty($data['status_manual'])) {
                $data['status_manual'] = 'In Process';
            }

            $operacion = OperacionLogistica::create($data);
            $this->generarHistorialInicial($operacion);

            return redirect()->route('logistica.matriz-seguimiento')
                ->with('success', 'Operación creada exitosamente. Folio: ' . $operacion->id);

        } catch (\Exception $e) {
            Log::error('Error creando operación: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    public function update(UpdateOperacionRequest $request, $id)
    {
        $operacion = OperacionLogistica::findOrFail($id);

        try {
            $data = $request->validated();
            $operacion->update($data);

            // Forzar recálculo tras edición
            if (method_exists($operacion, 'actualizarStatusAutomaticamente')) {
                $operacion->actualizarStatusAutomaticamente(true); 
            }

            return redirect()->route('logistica.matriz-seguimiento')
                ->with('success', 'Operación actualizada correctamente.');

        } catch (\Exception $e) {
            Log::error('Error actualizando: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $operacion = OperacionLogistica::findOrFail($id);
            $operacion->historicoMatrizSgm()->delete();
            $operacion->delete();

            return response()->json(['success' => true, 'message' => 'Operación eliminada.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // MÉTODOS DE API Y AJAX
    // ==========================================

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Done']);
        $operacion = OperacionLogistica::findOrFail($id);

        try {
            $operacion->status_manual = 'Done';
            $operacion->fecha_status_manual = now();
            
            if (method_exists($operacion, 'calcularStatusPorDias')) {
                $resultado = $operacion->calcularStatusPorDias();
                $operacion->generarHistorialCambioStatus($resultado, true, 'Marcada completada manualmente');
            }
            $operacion->save();

            return response()->json(['success' => true, 'message' => 'Status actualizado.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function recalcularStatus()
    {
        try {
            $operaciones = OperacionLogistica::where('status_manual', '!=', 'Done')->get();
            $count = 0;
            
            foreach ($operaciones as $op) {
                if (method_exists($op, 'actualizarStatusAutomaticamente')) {
                    $res = $op->actualizarStatusAutomaticamente(true);
                    if ($res['cambio']) $count++;
                }
            }

            return response()->json(['success' => true, 'message' => "Se actualizaron $count operaciones."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function obtenerHistorial($id)
    {
        $operacion = OperacionLogistica::with(['comentariosCronologicos', 'postOperaciones'])->findOrFail($id);
        
        $historial = $operacion->historicoMatrizSgm()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($h) {
                return [
                    'fecha_registro' => $h->fecha_registro ? \Carbon\Carbon::parse($h->fecha_registro)->format('d/m/Y H:i') : '-',
                    'operacion_status' => $h->operacion_status,
                    'observaciones' => $h->observaciones,
                    'dias_transcurridos' => max(0, (int)$h->dias_transcurridos),
                    'created_at' => $h->created_at->format('d/m/Y H:i')
                ];
            });
        
        return response()->json([
            'success' => true, 
            'historial' => $historial,
            'operacion' => $operacion 
        ]);
    }

    // ==========================================
    // SECCIÓN PÚBLICA
    // ==========================================

    public function consultaPublica()
    {
        return view('Logistica.consulta-publica');
    }

    public function buscarOperacionPublica(Request $request)
    {
        $request->validate([
            'tipo_busqueda' => 'required|in:pedimento,factura',
            'valor' => 'required|string'
        ]);

        $columna = $request->tipo_busqueda === 'pedimento' ? 'no_pedimento' : 'no_factura';
        $operacion = OperacionLogistica::where($columna, $request->valor)
            ->with(['postOperaciones.postOperacion', 'historicoMatrizSgm'])
            ->first();

        if (!$operacion) {
            return response()->json(['success' => false, 'message' => 'No se encontró la operación.']);
        }

        return response()->json([
            'success' => true,
            'operacion' => [
                'referencia' => $operacion->referencia_cliente,
                'status' => $operacion->status_calculado,
                'fecha_arribo' => $operacion->fecha_arribo_planta ? $operacion->fecha_arribo_planta->format('d/m/Y') : 'Pendiente',
                'historial_publico' => $operacion->historicoMatrizSgm->map(function($h) {
                    return ['fecha' => $h->created_at->format('d/m/Y'), 'estado' => $h->operacion_status];
                })
            ]
        ]);
    }

    // ==========================================
    // MÉTODOS PRIVADOS Y AUXILIARES
    // ==========================================

    private function generarHistorialInicial($operacion)
    {
        \App\Models\Logistica\HistoricoMatrizSgm::create([
            'operacion_logistica_id' => $operacion->id,
            'fecha_registro' => now(),
            'dias_transcurridos' => 0,
            'operacion_status' => 'In Process',
            'observaciones' => 'Operación creada - Estado inicial'
        ]);
    }

    private function cargarDatosVista($esAdmin, $empleadoActual, $modoPreview, $empleadoPreview)
    {
        $empleadoConfig = $modoPreview ? $empleadoPreview : $empleadoActual;
        $idEmpleadoConfig = $empleadoConfig ? $empleadoConfig->id : 0; 

        $columnasVisibles = ColumnaVisibleEjecutivo::getColumnasVisiblesParaEjecutivo($idEmpleadoConfig);
        $idioma = ColumnaVisibleEjecutivo::getIdiomaEjecutivo($idEmpleadoConfig);
        $nombresColumnas = ColumnaVisibleEjecutivo::getTodasLasColumnasConNombres($idioma);

        $empleados = Empleado::where(function($query) {
            $query->where('posicion', 'like', '%LOGISTICA%')
                  ->orWhere('posicion', 'like', '%Logistica%')
                  ->orWhere('area', 'Logistica'); 
        })->orderBy('nombre')->get();

        return [
            'clientes' => $esAdmin 
                ? Cliente::orderBy('cliente')->get() 
                : ($empleadoActual ? Cliente::where('ejecutivo_asignado_id', $empleadoActual->id)->get() : []),
            'agentesAduanales' => AgenteAduanal::orderBy('agente_aduanal')->get(),
            'transportes' => Transporte::orderBy('transporte')->get(),
            'aduanas' => Aduana::orderBy('aduana')->get(),
            'pedimentos' => Pedimento::orderBy('clave')->get(),
            'camposPersonalizados' => CampoPersonalizadoMatriz::where('activo', true)->orderBy('orden')->get(),
            'columnasOpcionalesVisibles' => $columnasVisibles,
            'idiomaColumnas' => $idioma,
            'nombresColumnas' => $nombresColumnas,
            'empleados' => $empleados, 
            'ejecutivosUnicos' => $esAdmin ? OperacionLogistica::distinct()->pluck('ejecutivo') : [],
            'clientesUnicos' => $esAdmin 
                ? OperacionLogistica::distinct()->pluck('cliente') 
                : OperacionLogistica::where('ejecutivo', $empleadoActual?->nombre)->distinct()->pluck('cliente')
        ];
    }
}