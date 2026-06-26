<?php
 
namespace App\Http\Controllers\Auditoria;
 
use App\Http\Controllers\Controller;
use App\Models\Auditoria\ProyectoAuditoria;
use App\Models\Auditoria\ActividadAuditoria;
use App\Models\Auditoria\CambioPropuesto;
use App\Models\Auditoria\BitacoraAuditoria;
use App\Models\Auditoria\HistorialPublicacionAuditoria;
use App\Models\Administracion\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
 
class AuditoriaProyectoController extends Controller
{
    use \App\Http\Controllers\Auditoria\AuditoriaCoordinadorTrait;

    // Listado de proyectos por cliente
    public function index(Request $request)
    {
        $user = auth()->user();
        $esCoordinador = $this->esCoordinador($user);
 
        // Clientes únicos para el filtro del encabezado
        $clientesConProyectos = ProyectoAuditoria::select('cliente_nombre')
            ->whereNotNull('cliente_nombre')
            ->distinct()
            ->orderBy('cliente_nombre')
            ->pluck('cliente_nombre');
        
        $clientesCatalog = collect(); // Ya no se requiere catálogo para crear proyectos
 
        // Buscar analistas disponibles para asignación (solo posición 'Auditoria')
        $analistas = User::whereHas('empleado', function ($q) {
            $q->whereRaw("LOWER(posicion) = 'auditoria'");
        })->get();
 
        // Query de proyectos
        $query = ProyectoAuditoria::with(['analista', 'coordinador']);
 
        if (!$esCoordinador) {
            // El analista solo ve los proyectos que tiene asignados
            $query->where(function ($q) use ($user) {
                $q->where('analista_id', $user->id)
                  ->orWhere('coordinador_id', $user->id);
            });
        }
 
        // Filtro por cliente (compara el string seleccionado)
        if ($request->filled('cliente_id')) {
            $query->where('cliente_nombre', $request->cliente_id);
        }
 
        $proyectos = $query->latest()->get();
 
        // Totales para las cards superiores del listado (calculados dinámicamente)
        $totalProyectos = $proyectos->count();
        $proyectosCerrados = $proyectos->where('porcentaje_general_aprobado', '>=', 100)->count();
        $proyectosRetrasados = $proyectos->where('porcentaje_general_aprobado', '<', 100)->filter(fn($p) => $p->fecha_entrega_estimada && $p->fecha_entrega_estimada->isPast())->count();
        $proyectosEnProceso = $totalProyectos - $proyectosCerrados - $proyectosRetrasados;
 
        return view('Auditoria.dashboard', compact(
            'proyectos',
            'clientesConProyectos',
            'clientesCatalog',
            'analistas',
            'esCoordinador',
            'totalProyectos',
            'proyectosEnProceso',
            'proyectosRetrasados',
            'proyectosCerrados'
        ));
    }
 
    // Guardar nuevo proyecto de auditoría (solo coordinador)
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
            abort(403, 'Solo el coordinador puede crear proyectos.');
        }
 
        $data = $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            'periodo_fiscal' => 'required|string|max:100',
            'analista_id' => 'required|exists:users,id',
            'cantidad_expedientes' => 'required|integer|min:1',
            'fecha_inicio' => 'required|date',
            'fecha_entrega_estimada' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        try {
            // Intentar asociar con un cliente existente si coincide el nombre o empresa
            $clienteExistente = Cliente::where('nombre', $data['cliente_nombre'])
                ->orWhere('empresa', $data['cliente_nombre'])
                ->first();
            if ($clienteExistente) {
                $data['cliente_id'] = $clienteExistente->id;
            }

            // 8 fases por defecto
            $fasesDefecto = [
                '1. Reunión Preoperativa',
                '2. Requerimiento de Información',
                '3. Revisión de Datos Fiscales',
                '4. Revisión Documental',
                '5. Revisión Transaccional',
                '6. Reporte Final',
                '7. Envío de Archivos a Cliente para VoBo',
                '8. Cierre de Proyecto Auditoria Documental/Transaccional'
            ];

            $data['coordinador_id'] = $user->id;
            $data['estatus_general'] = 'pendiente';
            $data['fase_actual'] = 1;
            $data['fases_config'] = $fasesDefecto;
            $data['token_publico'] = Str::random(40);
            $data['mostrar_detalle_cliente'] = false;

            $proyecto = ProyectoAuditoria::create($data);

            BitacoraAuditoria::registrar($proyecto->id, 'crear_proyecto', null, null, null, null, 'Proyecto creado.');

            return redirect()->route('auditoria.proyectos.show', $proyecto->id)->with('success', 'Proyecto creado correctamente.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('auditoria.dashboard')
                ->withInput()
                ->with('error', 'No se pudo crear el proyecto. Intenta de nuevo o contacta a Sistemas si el problema persiste.');
        }
    }
 
    // Vista detalle del proyecto (Matriz oficial e interactiva)
    public function show(Request $request, $id)
    {
        $user = auth()->user();
        $proyecto = ProyectoAuditoria::with(['cliente', 'analista', 'coordinador', 'publicador'])->findOrFail($id);
 
        $esCoordinador = $this->esCoordinador($user);
        $esResponsable = $proyecto->analista_id === $user->id || $proyecto->coordinador_id === $user->id;

        \Log::info("AuditoriaProyectoController@show: User ID=" . $user->id . ", Name=" . $user->name . ", esCoordinador=" . ($esCoordinador ? 'TRUE' : 'FALSE') . ", esResponsable=" . ($esResponsable ? 'TRUE' : 'FALSE'));
 
        if (!$esCoordinador && !$esResponsable) {
            abort(403, 'No tienes permisos para ver este proyecto.');
        }
 
        // Lista de analistas y coordinadores para reasignar
        $analistas = User::whereHas('empleado', function ($q) {
            $q->whereRaw("LOWER(area) LIKE '%auditor%' OR LOWER(posicion) LIKE '%auditor%'");
        })->orWhere('role', 'admin')->get();
 
        // Obtener actividades jerárquicas
        $actividades = ActividadAuditoria::with(['subprocesos', 'comentariosList.autor'])
            ->where('proyecto_id', $proyecto->id)
            ->whereNull('padre_id')
            ->orderBy('orden')
            ->get();
 
        // Cargar propuestas nuevas de creación de procesos y subprocesos
        $nuevasPropuestas = CambioPropuesto::with(['padre', 'proponente'])
            ->where('proyecto_id', $proyecto->id)
            ->whereIn('estatus_revision', ['borrador', 'pendiente', 'ajuste_solicitado'])
            ->whereIn('tipo_cambio', ['create_process', 'create_subprocess'])
            ->get();
 
        foreach ($nuevasPropuestas as $propuesta) {
            if ($propuesta->tipo_cambio === 'create_subprocess' && $propuesta->padre_id) {
                // Buscar proceso padre
                $parent = $actividades->firstWhere('id', $propuesta->padre_id);
                if ($parent) {
                    $dummy = new ActividadAuditoria([
                        'id' => 'prop-' . $propuesta->id,
                        'proyecto_id' => $proyecto->id,
                        'padre_id' => $propuesta->padre_id,
                        'actividad' => $propuesta->actividad_nombre_propuesto,
                        'responsable' => $propuesta->responsable_propuesto ?? 'E&I',
                        'plazo' => $proyecto->fecha_entrega_estimada,
                        'estatus_oficial' => $propuesta->estatus_propuesto ?? 'pendiente',
                        'porcentaje_oficial' => $propuesta->porcentaje_propuesto,
                        'es_proceso_principal' => false,
                    ]);
                    $dummy->es_propuesta = true;
                    $dummy->propuesta_cambio = $propuesta;
                    $dummy->setRelation('comentariosList', collect());
                    $parent->subprocesos->push($dummy);
                }
            } elseif ($propuesta->tipo_cambio === 'create_process') {
                $dummy = new ActividadAuditoria([
                    'id' => 'prop-' . $propuesta->id,
                    'proyecto_id' => $proyecto->id,
                    'padre_id' => null,
                    'actividad' => $propuesta->actividad_nombre_propuesto,
                    'responsable' => $propuesta->responsable_propuesto ?? 'E&I',
                    'plazo' => $proyecto->fecha_entrega_estimada,
                    'estatus_oficial' => $propuesta->estatus_propuesto ?? 'pendiente',
                    'porcentaje_oficial' => $propuesta->porcentaje_propuesto,
                    'es_proceso_principal' => true,
                ]);
                $dummy->es_propuesta = true;
                $dummy->propuesta_cambio = $propuesta;
                $dummy->setRelation('subprocesos', collect());
                $dummy->setRelation('comentariosList', collect());
                $actividades->push($dummy);
            }
        }
 
        // Contar cambios pendientes
        $cambiosPendientesCount = CambioPropuesto::where('proyecto_id', $proyecto->id)
            ->where('estatus_revision', 'pendiente')
            ->count();
 
        // Obtener la bandeja de cambios para el modal del coordinador
        $cambiosPendientes = CambioPropuesto::with(['actividad', 'padre', 'proponente'])
            ->where('proyecto_id', $proyecto->id)
            ->where('estatus_revision', 'pendiente')
            ->get();
 
        // Obtener todos los borradores del analista logueado
        $misBorradoresTodos = CambioPropuesto::where('proyecto_id', $proyecto->id)
            ->where('user_id', $user->id)
            ->where('estatus_revision', 'borrador')
            ->get();
 
        // Filtrar los borradores de avance sobre actividades existentes
        $misBorradores = $misBorradoresTodos->whereNotNull('actividad_id')->keyBy('actividad_id');
 
        // Si el analista tiene cambios enviados que están en revisión
        $misCambiosEnRevision = CambioPropuesto::where('proyecto_id', $proyecto->id)
            ->where('user_id', $user->id)
            ->where('estatus_revision', 'pendiente')
            ->get()
            ->keyBy('actividad_id');
 
        // Historial de la bitácora
        $bitacora = BitacoraAuditoria::with('usuario')
            ->where('proyecto_id', $proyecto->id)
            ->latest()
            ->take(50)
            ->get();
 
        // Avisos de cambios internos pendientes para mostrar en la interfaz
        $tieneCambiosInternosSinAprobar = $cambiosPendientesCount > 0;
 
        return view('Auditoria.proyectos.show', compact(
            'proyecto',
            'actividades',
            'esCoordinador',
            'analistas',
            'cambiosPendientesCount',
            'cambiosPendientes',
            'misBorradores',
            'misBorradoresTodos',
            'misCambiosEnRevision',
            'tieneCambiosInternosSinAprobar',
            'bitacora'
        ));
    }
 
    // Actualizar datos generales (coordinador)
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
            abort(403, 'Solo el coordinador puede modificar los datos generales.');
        }
 
        $proyecto = ProyectoAuditoria::findOrFail($id);
 
        $data = $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            'periodo_fiscal' => 'required|string|max:100',
            'analista_id' => 'required|exists:users,id',
            'cantidad_expedientes' => 'required|integer|min:1',
            'fecha_inicio' => 'required|date',
            'fecha_entrega_estimada' => 'required|date|after_or_equal:fecha_inicio',
            'publico_password' => 'nullable|string|max:100',
            'publico_expira_at' => 'nullable|date',
            'mostrar_detalle_cliente' => 'nullable|boolean',
        ]);
 
        $data['mostrar_detalle_cliente'] = $request->has('mostrar_detalle_cliente');
 
        // Encriptar password si se ingresa
        if ($request->filled('publico_password')) {
            $data['publico_password'] = bcrypt($request->publico_password);
        } else if ($request->has('remove_password')) {
            $data['publico_password'] = null;
        } else {
            unset($data['publico_password']);
        }
 
        $proyecto->update($data);
 
        BitacoraAuditoria::registrar($proyecto->id, 'modificar_generales', null, 'datos_generales', null, null, 'Datos generales actualizados.');
 
        return redirect()->back()->with('success', 'Datos generales del proyecto actualizados correctamente.');
    }
 
    public function updateFase(Request $request, $id)
    {
        $user = auth()->user();
        $esCoordinador = $this->esCoordinador($user);
        \Log::info("AuditoriaProyectoController@updateFase: User ID=" . $user->id . ", Name=" . $user->name . ", esCoordinador=" . ($esCoordinador ? 'TRUE' : 'FALSE') . ", Project ID=" . $id);
        
        if (!$esCoordinador) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }
 
        $proyecto = ProyectoAuditoria::findOrFail($id);
        $request->validate(['fase' => 'required|integer|between:1,8']);
 
        $faseAnterior = $proyecto->fase_actual;
        $proyecto->update(['fase_actual' => $request->fase]);
 
        BitacoraAuditoria::registrar($proyecto->id, 'modificar_fase', null, 'fase_actual', $faseAnterior, $request->fase, 'Cambio de fase del proyecto.');
 
        return response()->json(['success' => true]);
    }
 
    // Publicar avance al cliente
    public function publicarAvance(Request $request, $id)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
            abort(403, 'Solo el coordinador puede publicar el avance al cliente.');
        }
 
        $proyecto = ProyectoAuditoria::findOrFail($id);
 
        // 1. Snapshot de las actividades para registrar en el historial
        $actividades = ActividadAuditoria::where('proyecto_id', $proyecto->id)->get();
        
        DB::transaction(function () use ($proyecto, $actividades, $user) {
            // 2. Copiar porcentaje oficial y estatus a campos publicados
            foreach ($actividades as $actividad) {
                $actividad->update([
                    'estatus_publicado' => $actividad->estatus_oficial,
                    'porcentaje_published' => $actividad->porcentaje_oficial
                ]);
            }
 
            // 3. Registrar en Historial
            HistorialPublicacionAuditoria::create([
                'proyecto_id' => $proyecto->id,
                'user_id' => $user->id,
                'avance_publicado' => $proyecto->porcentaje_general_aprobado,
                'fase_publicada' => $proyecto->fase_actual,
                'detalles' => $actividades->map(fn($a) => [
                    'actividad_id' => $a->id,
                    'actividad' => $a->actividad,
                    'porcentaje' => $a->porcentaje_oficial,
                    'estatus' => $a->estatus_oficial
                ])->toArray(),
            ]);
 
            // 4. Actualizar campos en el proyecto
            $proyecto->update([
                'porcentaje_general_publicado' => $proyecto->porcentaje_general_aprobado,
                'ultima_publicacion_at' => now(),
                'ultima_publicacion_user_id' => $user->id,
            ]);
 
            BitacoraAuditoria::registrar($proyecto->id, 'publicar_cliente', null, 'porcentaje_general_publicado', null, $proyecto->porcentaje_general_aprobado, 'Avance publicado al cliente.');
        });
 
        return redirect()->back()->with('success', 'Avance publicado al cliente exitosamente.');
    }
 
    // Agregar proceso principal o subproceso directamente (Coordinador) o sugerir (Analista)
    public function storeActividad(Request $request, $id)
    {
        $user = auth()->user();
        $proyecto = ProyectoAuditoria::findOrFail($id);
        $esCoordinador = $this->esCoordinador($user);
 
        $request->validate([
            'actividad' => 'required|string|max:1000',
            'padre_id' => 'nullable|exists:auditoria_actividades,id',
            'plazo' => 'nullable|date',
            'responsable' => 'required|string|max:255',
        ]);
 
        if ($esCoordinador) {
            // Coordinador inserta directamente en la matriz oficial
            $ultimoOrden = ActividadAuditoria::where('proyecto_id', $proyecto->id)
                ->where('padre_id', $request->padre_id)
                ->max('orden') ?? -1;
 
            $actividad = ActividadAuditoria::create([
                'proyecto_id' => $proyecto->id,
                'padre_id' => $request->padre_id,
                'orden' => $ultimoOrden + 1,
                'actividad' => $request->actividad,
                'responsable' => $request->responsable,
                'plazo' => $request->plazo ?? $proyecto->fecha_entrega_estimada,
                'estatus_oficial' => 'pendiente',
                'porcentaje_oficial' => 0,
                'es_proceso_principal' => is_null($request->padre_id),
            ]);
 
            BitacoraAuditoria::registrar($proyecto->id, 'crear_actividad', $actividad->id, 'actividad', null, $request->actividad, 'Actividad creada directamente.');
            $proyecto->recalcularPorcentajes();
 
            return redirect()->back()->with('success', 'Actividad agregada exitosamente.');
        } else {
            // Analista sugiere un subproceso/proceso nuevo -> Crea propuesta de cambio
            // Validar que el analista esté asignado al proyecto
            if ($proyecto->analista_id !== $user->id) {
                abort(403, 'No estás asignado a este proyecto para sugerir actividades.');
            }
 
            $propuesta = CambioPropuesto::create([
                'actividad_id' => null,
                'proyecto_id' => $proyecto->id,
                'padre_id' => $request->padre_id,
                'user_id' => $user->id,
                'tipo_cambio' => is_null($request->padre_id) ? 'create_process' : 'create_subprocess',
                'actividad_nombre_propuesto' => $request->actividad,
                'responsable_propuesto' => $request->responsable,
                'estatus_propuesto' => 'pendiente',
                'porcentaje_propuesto' => 0,
                'comentario_propuesto' => $request->comentario ?? (is_null($request->padre_id) ? 'Sugerencia de nuevo proceso principal' : 'Sugerencia de nuevo subproceso'),
                'estatus_revision' => 'borrador', // Guardar como borrador primero
            ]);
 
            BitacoraAuditoria::registrar($proyecto->id, 'proponer_cambio', null, 'actividad_nombre_propuesto', null, $request->actividad, 'Propuesta de actividad guardada en borradores.');
 
            return redirect()->back()->with('success', 'Propuesta de actividad guardada en tus borradores.');
        }
    }
 
    // Reordenar actividades (coordinador)
    public function updateActividadOrden(Request $request, $id)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }
 
        $request->validate([
            'orden' => 'required|array',
            'orden.*.id' => 'required|exists:auditoria_actividades,id',
            'orden.*.orden' => 'required|integer',
        ]);
 
        DB::transaction(function () use ($request) {
            foreach ($request->orden as $item) {
                ActividadAuditoria::where('id', $item['id'])->update(['orden' => $item['orden']]);
            }
        });
 
        return response()->json(['success' => true]);
    }
 
    // Eliminar actividad (coordinador)
    public function destroyActividad($proyectoId, $actividadId)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
            abort(403, 'Solo el coordinador puede eliminar actividades.');
        }
 
        $actividad = ActividadAuditoria::where('proyecto_id', $proyectoId)->findOrFail($actividadId);
        
        $nombre = $actividad->actividad;
        $actividad->delete(); // Soft delete cascading handled by DB or model relations
 
        $proyecto = ProyectoAuditoria::findOrFail($proyectoId);
        $proyecto->recalcularPorcentajes();
 
        BitacoraAuditoria::registrar($proyectoId, 'eliminar_actividad', $actividadId, 'actividad', $nombre, null, 'Actividad eliminada.');
 
        return redirect()->back()->with('success', 'Actividad eliminada exitosamente.');
    }

    // Editar actividad / proceso / subproceso (coordinador)
    public function updateActividad(Request $request, $proyectoId, $actividadId)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
            abort(403, 'Solo el coordinador puede editar procesos o subprocesos.');
        }

        $request->validate([
            'actividad' => 'required|string|max:1000',
            'responsable' => 'required|string|max:255',
            'plazo' => 'nullable|date',
        ]);

        $actividad = ActividadAuditoria::where('proyecto_id', $proyectoId)->findOrFail($actividadId);
        $valorAnterior = $actividad->actividad;

        $actividad->update([
            'actividad' => $request->actividad,
            'responsable' => $request->responsable,
            'plazo' => $request->plazo,
        ]);

        BitacoraAuditoria::registrar($proyectoId, 'editar_actividad', $actividadId, 'actividad', $valorAnterior, $request->actividad, 'Actividad editada.');

        return redirect()->back()->with('success', 'Proceso o subproceso actualizado exitosamente.');
    }
 
    // Cargar procesos base (coordinador)
    public function cargarProcesosBase(Request $request, $id)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
            abort(403, 'Solo el coordinador puede cargar los procesos base.');
        }

        $proyecto = ProyectoAuditoria::findOrFail($id);

        $procesosBase = [
            [
                'actividad' => '1. Revisión de datos fiscales Importador Vs CSF vigente.',
                'subprocesos' => [
                    '1.1 Entrega de datos fiscales Proveedor Vs W-9 / Certificado de Tax-Id.'
                ]
            ],
            ['actividad' => '2. Iniciar revisión Documental.', 'subprocesos' => []],
            ['actividad' => '3. Iniciar revisión Transaccional.', 'subprocesos' => []],
            ['actividad' => '4. Revisión archivos/complementar observaciones.', 'subprocesos' => []],
            ['actividad' => '5. Registrar multas correspondientes (Documental/Transaccional).', 'subprocesos' => []],
            ['actividad' => '6. Preparar reporte final.', 'subprocesos' => []],
            ['actividad' => '7. Envío de información.', 'subprocesos' => []],
            ['actividad' => '8. Recepción y revisión cliente.', 'subprocesos' => []],
            ['actividad' => '9. Concretar reunión para explicar observaciones.', 'subprocesos' => []],
            ['actividad' => '10. Cierre de proyecto Auditoría Documental/Transaccional.', 'subprocesos' => []],
        ];

        DB::transaction(function () use ($proyecto, $procesosBase) {
            $ultimoOrden = ActividadAuditoria::where('proyecto_id', $proyecto->id)
                ->whereNull('padre_id')
                ->max('orden') ?? -1;

            foreach ($procesosBase as $index => $pb) {
                $ordenProceso = $ultimoOrden + 1 + $index;
                $proceso = ActividadAuditoria::create([
                    'proyecto_id' => $proyecto->id,
                    'padre_id' => null,
                    'orden' => $ordenProceso,
                    'actividad' => $pb['actividad'],
                    'responsable' => 'E&I',
                    'plazo' => $proyecto->fecha_entrega_estimada,
                    'estatus_oficial' => 'pendiente',
                    'porcentaje_oficial' => 0,
                    'es_proceso_principal' => true,
                ]);

                foreach ($pb['subprocesos'] as $subIndex => $subText) {
                    ActividadAuditoria::create([
                        'proyecto_id' => $proyecto->id,
                        'padre_id' => $proceso->id,
                        'orden' => $subIndex,
                        'actividad' => $subText,
                        'responsable' => 'E&I',
                        'plazo' => $proyecto->fecha_entrega_estimada,
                        'estatus_oficial' => 'pendiente',
                        'porcentaje_oficial' => 0,
                        'es_proceso_principal' => false,
                    ]);
                }
            }

            BitacoraAuditoria::registrar($proyecto->id, 'cargar_procesos_base', null, null, null, null, 'Procesos base cargados en la matriz.');
            $proyecto->recalcularPorcentajes();
        });

        return redirect()->back()->with('success', 'Los procesos base se han cargado exitosamente en el proyecto.');
    }

    // Eliminar proyecto (coordinador)
    public function destroy($id)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
            abort(403, 'Solo el coordinador puede eliminar proyectos.');
        }
 
        $proyecto = ProyectoAuditoria::findOrFail($id);
        $proyecto->delete();
 
        return redirect()->route('auditoria.dashboard')->with('success', 'Proyecto de auditoría eliminado.');
    }
}
