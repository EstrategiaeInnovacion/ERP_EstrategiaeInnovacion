<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
// Requests (Validación separada)
use App\Http\Requests\Logistica\StoreOperacionRequest;
use App\Http\Requests\Logistica\UpdateOperacionRequest;
// Servicios
use App\Services\Logistica\OperacionFilterService;

class OperacionLogisticaController extends Controller
{
    protected $filterService;

    public function __construct(OperacionFilterService $filterService)
    {
        $this->filterService = $filterService;
        $this->middleware('auth')->except(['consultaPublica', 'buscarOperacionPublica']);
    }

    /**
     * Muestra la Matriz de Seguimiento (Index)
     * Ahora utiliza el FilterService para limpiar la lógica de búsqueda.
     */
    public function index(Request $request)
    {
        // 1. Lógica de negocio crítica: Verificar status automáticos al cargar

        $usuarioActual = auth()->user();
        $empleadoActual = null;
        $esAdmin = false;
        $modoPreview = false;
        $empleadoPreview = null;

        // Determinar usuario y rol
        if ($usuarioActual) {
            $empleadoActual = Empleado::where('correo', $usuarioActual->email)
                ->orWhere('nombre', 'like', '%' . $usuarioActual->name . '%')->first();
            $esAdmin = $usuarioActual->hasRole('admin');
        }

        // Modo Preview para Admins
        if ($esAdmin && $request->has('preview_as')) {
            $empleadoPreview = Empleado::find($request->get('preview_as'));
            if ($empleadoPreview) $modoPreview = true;
        }

        // 2. Construcción del Query usando el Servicio de Filtros
        // Optimizamos cargando solo lo necesario con 'with'
        $query = OperacionLogistica::with(['ejecutivo', 'postOperaciones', 'valoresCamposPersonalizados.campo']);

        // Aplicar filtros de seguridad (quién ve qué)
        if (!$esAdmin && $empleadoActual) {
            $query->where('ejecutivo', 'LIKE', '%' . $empleadoActual->nombre . '%');
        } elseif (!$esAdmin && !$empleadoActual) {
            $query->where('id', 0); // No mostrar nada si no se identifica empleado
        }

        // APLICAR FILTROS (Aquí nos ahorramos 100 líneas de ifs)
        $this->filterService->apply($query, $request);

        // Paginación
        $operaciones = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // 3. Cargar datos para la vista (Selects y Configuración)
        // NOTA: Moví la lógica pesada de cargar catálogos a métodos privados o cacheados si fuera necesario
        $datosVista = $this->cargarDatosVista($esAdmin, $empleadoActual, $modoPreview, $empleadoPreview);

        return view('Logistica.matriz-seguimiento', array_merge(
            compact('operaciones', 'empleadoActual', 'esAdmin', 'modoPreview', 'empleadoPreview'),
            $datosVista,
            $request->all() // Pasar filtros a la vista
        ));
    }

    // ==========================================
    // MÉTODOS PRIVADOS Y AUXILIARES
    // ==========================================

    /**
     * Verificar y actualizar automáticamente el status de operaciones al consultar.
     * Solo actualiza operaciones que han cambiado desde la última verificación.
     */
    private function verificarYActualizarStatusOperaciones()
    {
        try {
            // Verificar operaciones activas (no Done)
            // Usamos chunk para procesar en bloques y no saturar memoria
            OperacionLogistica::where('status_manual', '!=', 'Done')
                ->where(function($query) {
                    $query->whereNull('fecha_ultimo_calculo')
                          ->orWhere('fecha_ultimo_calculo', '<', now()->startOfDay());
                })
                ->chunk(100, function($operaciones) {
                    foreach ($operaciones as $operacion) {
                        // Llamamos al método del modelo que calcula días y status
                        // Asegúrate de haber pegado el método en el Modelo también
                        if (method_exists($operacion, 'actualizarStatusAutomaticamente')) {
                            $operacion->actualizarStatusAutomaticamente(true);
                        }
                    }
                });
        } catch (\Exception $e) {
            // Silenciamos el error para no detener la carga de la vista, pero lo logueamos
            \Log::error('Error en verificación automática de status: ' . $e->getMessage());
        }
    }

    /**
     * Generar un historial inicial cuando se crea la operación
     */
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

    /**
     * Carga todos los catálogos necesarios para la vista index
     */
    private function cargarDatosVista($esAdmin, $empleadoActual, $modoPreview, $empleadoPreview)
    {
        // Determinar qué empleado usar para configuración de columnas
        $empleadoConfig = $modoPreview ? $empleadoPreview : $empleadoActual;
        $idEmpleadoConfig = $empleadoConfig ? $empleadoConfig->id : 0; 

        // Lógica de columnas visibles
        $columnasVisibles = ColumnaVisibleEjecutivo::getColumnasVisiblesParaEjecutivo($idEmpleadoConfig);
        $idioma = ColumnaVisibleEjecutivo::getIdiomaEjecutivo($idEmpleadoConfig);
        $nombresColumnas = ColumnaVisibleEjecutivo::getTodasLasColumnasConNombres($idioma);

        // Lista de empleados para los selects
        $empleados = Empleado::where(function($query) {
            $query->where('posicion', 'like', '%LOGISTICA%')
                  ->orWhere('posicion', 'like', '%Logistica%')
                  ->orWhere('area', 'Logistica'); 
        })->orderBy('nombre')->get();

        // Catálogos
        return [
            'clientes' => $esAdmin 
                ? Cliente::orderBy('cliente')->get() 
                : ($empleadoActual ? Cliente::where('ejecutivo_asignado_id', $empleadoActual->id)->get() : []),
            'agentesAduanales' => AgenteAduanal::orderBy('agente_aduanal')->get(),
            'transportes' => Transporte::orderBy('transporte')->get(),
            'aduanas' => Aduana::orderBy('aduana')->get(),
            'pedimentos' => Pedimento::orderBy('clave')->get(),
            'camposPersonalizados' => CampoPersonalizadoMatriz::where('activo', true)->orderBy('orden')->get(),
            
            // Variables de configuración de UI
            'columnasOpcionalesVisibles' => $columnasVisibles,
            'idiomaColumnas' => $idioma,
            'nombresColumnas' => $nombresColumnas,
            'empleados' => $empleados, 
            
            // Listas para filtros únicos
            'ejecutivosUnicos' => $esAdmin ? OperacionLogistica::distinct()->pluck('ejecutivo') : [],
            'clientesUnicos' => $esAdmin 
                ? OperacionLogistica::distinct()->pluck('cliente') 
                : OperacionLogistica::where('ejecutivo', $empleadoActual?->nombre)->distinct()->pluck('cliente')
        ];
    }

    /**
     * Devuelve datos para crear una nueva operación (JSON para el modal)
     */
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

    /**
     * Guardar nueva operación (STORE)
     * VALIDACIÓN AUTOMÁTICA gracias a StoreOperacionRequest
     */
    public function store(StoreOperacionRequest $request)
    {
        try {
            // $request->validated() devuelve solo los datos limpios y validados
            $data = $request->validated();

            // Lógica extra: Asignar status inicial si no viene
            if (empty($data['status_manual'])) {
                $data['status_manual'] = 'In Process'; // O el default que uses
            }

            $operacion = OperacionLogistica::create($data);

            // Generar historial inicial
            $this->generarHistorialInicial($operacion);

            return redirect()->route('logistica.matriz-seguimiento')
                ->with('success', 'Operación creada exitosamente. Folio: ' . $operacion->id);

        } catch (\Exception $e) {
            Log::error('Error creando operación: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }


    /**
     * Eliminar una operación (DESTROY)
     */
    public function destroy($id)
    {
        try {
            $operacion = OperacionLogistica::findOrFail($id);
            // Eliminar historial por integridad (o usar cascade en BD)
            $operacion->historicoMatrizSgm()->delete();
            $operacion->delete();

            return response()->json(['success' => true, 'message' => 'Operación eliminada.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar SOLO el status manual (Kanban o Acción rápida)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Done']);
        $operacion = OperacionLogistica::findOrFail($id);

        try {
            $operacion->status_manual = 'Done';
            $operacion->fecha_status_manual = now();
            
            // Recalcular y guardar historial
            $resultado = $operacion->calcularStatusPorDias();
            $operacion->generarHistorialCambioStatus($resultado, true, 'Marcada completada manualmente');
            $operacion->save();

            return response()->json(['success' => true, 'message' => 'Status actualizado.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Recalcular status masivo (Mantenimiento)
     */
    public function recalcularStatus()
    {
        try {
            $operaciones = OperacionLogistica::where('status_manual', '!=', 'Done')->get();
            $count = 0;
            
            foreach ($operaciones as $op) {
                $res = $op->actualizarStatusAutomaticamente(true);
                if ($res['cambio']) $count++;
            }

            return response()->json(['success' => true, 'message' => "Se actualizaron $count operaciones."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener Historial y Datos Completos para Edición
     */
    public function obtenerHistorial($id)
    {
        // CORRECCIÓN: Cargamos todos los datos, no solo 'only(...)'
        $operacion = OperacionLogistica::with(['comentariosCronologicos', 'postOperaciones'])->findOrFail($id);
        
        $historial = $operacion->historicoMatrizSgm()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($h) {
                // Formatear fechas y asegurar que los días sean números
                return [
                    'fecha_registro' => $h->fecha_registro ? \Carbon\Carbon::parse($h->fecha_registro)->format('d/m/Y H:i') : '-',
                    'operacion_status' => $h->operacion_status,
                    'observaciones' => $h->observaciones,
                    // Si días es null o negativo raro, poner 0
                    'dias_transcurridos' => max(0, (int)$h->dias_transcurridos),
                    'created_at' => $h->created_at->format('d/m/Y H:i')
                ];
            });
        
        return response()->json([
            'success' => true, 
            'historial' => $historial,
            'operacion' => $operacion // Enviar objeto completo para llenar el formulario
        ]);
    }

    /**
     * Actualizar operación (Update) con recálculo inmediato
     */
    public function update(UpdateOperacionRequest $request, $id)
    {
        $operacion = OperacionLogistica::findOrFail($id);

        try {
            $data = $request->validated();
            
            // 1. Actualizar datos
            $operacion->update($data);

            // 2. CORRECCIÓN: Forzar recálculo de días y status tras editar fechas
            // Esto asegura que el historial refleje los días reales basados en las nuevas fechas
            $operacion->actualizarStatusAutomaticamente(true); 

            return redirect()->route('logistica.matriz-seguimiento')
                ->with('success', 'Operación actualizada correctamente.');

        } catch (\Exception $e) {
            Log::error('Error actualizando: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // ==========================================
    // SECCIÓN PÚBLICA (CONSULTA SIN LOGIN)
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
        $operacion = OperacionLogistica::where($columna, $request->valor)->with(['postOperaciones.postOperacion', 'historicoMatrizSgm'])->first();

        if (!$operacion) {
            return response()->json(['success' => false, 'message' => 'No se encontró la operación.']);
        }

        // Formatear respuesta segura para público
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

    /**
     * Calcula status y días transcurridos, y guarda historial si hay cambios.
     */
    public function actualizarStatusAutomaticamente($forzarHistorial = false)
    {
        // 1. Calcular Días Transcurridos
        // Si no hay fecha de embarque, no han pasado días (0).
        if (!$this->fecha_embarque) {
            $dias = 0;
        } else {
            // Si ya llegó a planta, los días son fijos (Embarque -> Planta)
            // Si no ha llegado, son dinámicos (Embarque -> Hoy)
            $fechaFin = $this->fecha_arribo_planta ?? now();
            $dias = $this->fecha_embarque->diffInDays($fechaFin); // diffInDays devuelve absoluto por defecto, cuidado
            
            // Asegurar que si la fecha fin es antes que inicio (error de captura), no de positivo falso
            if ($fechaFin < $this->fecha_embarque) $dias = 0;
        }

        // 2. Determinar Status Calculado
        $nuevoStatus = 'In Process';
        $color = 'green';
        $target = $this->target ?? 30; // Target default

        if ($this->fecha_arribo_planta) {
            $nuevoStatus = 'Done'; // Ya llegó
        } elseif ($dias > $target) {
            $nuevoStatus = 'Out of Metric';
            $color = 'red';
        } elseif ($dias >= ($target * 0.8)) {
            $color = 'yellow'; // Warning
        }

        // 3. Guardar cambios en la Operación
        $cambio = false;
        if ($this->dias_transcurridos_calculados != $dias || $this->status_calculado != $nuevoStatus) {
            $this->dias_transcurridos_calculados = $dias;
            $this->status_calculado = $nuevoStatus;
            $this->color_status = $color;
            $this->save();
            $cambio = true;
        }

        // 4. Generar Historial (Solo si hubo cambio o se fuerza)
        if ($cambio || $forzarHistorial) {
            \App\Models\Logistica\HistoricoMatrizSgm::create([
                'operacion_logistica_id' => $this->id,
                'fecha_registro' => now(),
                'dias_transcurridos' => $dias,
                'target_dias' => $target,
                'color_status' => $color,
                'operacion_status' => $this->status_manual ?: $nuevoStatus,
                'observaciones' => $forzarHistorial ? 'Actualización manual/edición' : 'Actualización automática de días'
            ]);
        }

        return ['cambio' => $cambio, 'status' => $nuevoStatus, 'dias' => $dias];
    }
}