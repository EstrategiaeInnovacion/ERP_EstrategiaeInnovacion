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
    private function esCoordinador($user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $empleado = $user->empleado;
        if (!$empleado) {
            return false;
        }

        // 1. Si explícitamente está marcado como coordinador
        if ($empleado->es_coordinador) {
            return true;
        }

        // 2. Si tiene empleados a su cargo (es decir, actúa como supervisor en la estructura)
        $esSupervisor = \App\Models\Empleado::where('supervisor_id', $empleado->id)->exists();
        if ($esSupervisor) {
            return true;
        }

        // 3. Si el nombre de su puesto contiene 'supervisor', 'coordinador' o 'jefe'
        $posicion = mb_strtolower($empleado->posicion ?? '');
        if (str_contains($posicion, 'supervisor') || str_contains($posicion, 'coordinador') || str_contains($posicion, 'jefe')) {
            return true;
        }

        return false;
    }
 
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
 
        // Totales para las cards superiores del listado
        $totalProyectos = $proyectos->count();
        $proyectosEnProceso = $proyectos->where('estatus_general', 'en proceso')->count();
        $proyectosRetrasados = $proyectos->where('estatus_general', 'retrasado')->count();
        $proyectosCerrados = $proyectos->where('estatus_general', 'cerrado')->count();
 
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
 
        // 8 fases por defecto
        $fasesDefecto = [
            '1. Planeación e Inicio',
            '2. Requerimiento de Información',
            '3. Recepción de Expedientes',
            '4. Revisión y Análisis',
            '5. Detección de Incidencias',
            '6. Borrador de Hallazgos',
            '7. Discusión y Ajustes',
            '8. Cierre y Entrega Final'
        ];
 
        $data['coordinador_id'] = $user->id;
        $data['estatus_general'] = 'pendiente';
        $data['fase_actual'] = 1;
        $data['fases_config'] = $fasesDefecto;
        $data['token_publico'] = Str::random(40);
        $data['mostrar_detalle_cliente'] = false;
 
        $proyecto = ProyectoAuditoria::create($data);
 
        // Crear procesos base por defecto para que la matriz no empiece totalmente vacía
        $procesosBase = [
            'Revisión de Pedimentos e Impuestos',
            'Análisis de Regulaciones y Restricciones No Arancelarias',
            'Validación de Expedientes Físicos vs Digitales',
            'Cruce de Inventarios de Activo Fijo'
        ];
 
        foreach ($procesosBase as $index => $procesoNombre) {
            ActividadAuditoria::create([
                'proyecto_id' => $proyecto->id,
                'padre_id' => null,
                'orden' => $index,
                'actividad' => $procesoNombre,
                'responsable' => 'E&I',
                'plazo' => $proyecto->fecha_entrega_estimada,
                'estatus_oficial' => 'pendiente',
                'porcentaje_oficial' => 0,
                'es_proceso_principal' => true,
            ]);
        }
 
        BitacoraAuditoria::registrar($proyecto->id, 'crear_proyecto', null, null, null, null, 'Proyecto creado con procesos base.');
 
        return redirect()->route('auditoria.proyectos.show', $proyecto->id)->with('success', 'Proyecto creado correctamente.');
    }
 
    // Vista detalle del proyecto (Matriz oficial e interactiva)
    public function show(Request $request, $id)
    {
        $user = auth()->user();
        $proyecto = ProyectoAuditoria::with(['cliente', 'analista', 'coordinador', 'publicador'])->findOrFail($id);
 
        $esCoordinador = $this->esCoordinador($user);
        $esResponsable = $proyecto->analista_id === $user->id || $proyecto->coordinador_id === $user->id;
 
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
            'estatus_general' => 'required|in:pendiente,en proceso,retrasado,cerrado',
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
 
    // Cambiar fase actual (coordinador)
    public function updateFase(Request $request, $id)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
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
 
    // Eliminar proyecto (coordinador)
    public function destroy($id)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
            abort(403, 'Solo el coordinador puede eliminar proyectos.');
        }
 
        $proyecto = ProyectoAuditoria::findOrFail($id);
        $proyecto->delete();
 
        return redirect()->route('auditoria.index')->with('success', 'Proyecto de auditoría eliminado.');
    }
}
