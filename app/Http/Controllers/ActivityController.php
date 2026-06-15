<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityHistory;
use App\Models\Empleado;
use App\Models\PlaneacionVentana;
use App\Models\Proyecto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ActivityController extends Controller
{
    private function esDireccion($user)
    {
        return $user && $user->empleado && Str::contains(mb_strtolower($user->empleado->posicion ?? '', 'UTF-8'), 'direcc');
    }

    private function esSupervisor($user)
    {
        return $user && $user->empleado && Empleado::where('supervisor_id', $user->empleado->id)->exists();
    }

    private function esSupervisorDirectoDe($supervisor, $colaborador)
    {
        if (!$supervisor || !$supervisor->empleado || !$colaborador || !$colaborador->empleado) {
            return false;
        }
        return $colaborador->empleado->supervisor_id === $supervisor->empleado->id;
    }

    private function puedeAsignarAOtros($user)
    {
        if (!$user || !$user->empleado) {
            return false;
        }
        return (bool) $user->empleado->es_coordinador || $this->esDireccion($user) || $this->esSupervisor($user);
    }

    private function determinarEstatusInicial($targetUserId, $currentUser)
    {
        if ($targetUserId == $currentUser->id) {
            return 'En proceso';
        }

        if ($this->esDireccion($currentUser)) {
            return 'Planeado';
        }

        $targetUser = User::with('empleado')->find($targetUserId);
        if ($targetUser && $this->esSupervisorDirectoDe($currentUser, $targetUser)) {
            return 'Planeado';
        }

        return 'Por Aprobar';
    }

    private function parsearFechas(Request $request)
    {
        $rangeType = $request->input('range', 'week');
        if ($request->filled('date_start') && $request->filled('date_end')) {
            $startDate = Carbon::parse($request->date_start)->startOfDay();
            $endDate = Carbon::parse($request->date_end)->endOfDay();
            $rangeType = 'custom';
            $periodLabel = 'Rango: ' . $startDate->format('d/m') . ' - ' . $endDate->format('d/m');
            $daysDiff = $startDate->diffInDays($endDate) + 1;
            $prevDateRef = $startDate->copy()->subDays($daysDiff)->format('Y-m-d');
            $nextDateRef = $endDate->copy()->addDay()->format('Y-m-d');
        } else {
            $refDate = $request->has('ref_date') ? Carbon::parse($request->ref_date) : now();
            switch ($rangeType) {
                case 'month':
                    $startDate = $refDate->copy()->startOfMonth();
                    $endDate = $refDate->copy()->endOfMonth();
                    $periodLabel = Str::ucfirst($startDate->translatedFormat('F Y'));
                    $prevDateRef = $startDate->copy()->subMonth()->format('Y-m-d');
                    $nextDateRef = $startDate->copy()->addMonth()->format('Y-m-d');
                    break;
                case 'quarter':
                    $startDate = $refDate->copy()->startOfQuarter();
                    $endDate = $refDate->copy()->endOfQuarter();
                    $periodLabel = 'Trimestre: ' . $startDate->format('M') . ' - ' . $endDate->format('M Y');
                    $prevDateRef = $startDate->copy()->subMonths(3)->format('Y-m-d');
                    $nextDateRef = $startDate->copy()->addMonths(3)->format('Y-m-d');
                    break;
                case 'week':
                default:
                    $startDate = $refDate->copy()->startOfWeek();
                    $endDate = $refDate->copy()->endOfWeek();
                    $periodLabel = 'Semana: ' . $startDate->format('d M') . ' - ' . $endDate->format('d M');
                    $prevDateRef = $startDate->copy()->subWeek()->format('Y-m-d');
                    $nextDateRef = $startDate->copy()->addWeek()->format('Y-m-d');
                    break;
            }
        }
        return [$startDate, $endDate, $periodLabel, $rangeType, $prevDateRef, $nextDateRef];
    }

    private function obtenerFiltroProyecto(Request $request, $user)
    {
        if (!$request->filled('proyecto_id')) {
            return null;
        }

        $proyectoIdParam = $request->proyecto_id;
        if ($proyectoIdParam === 'sin_proyecto') {
            return 'sin_proyecto';
        }

        if (!$user->isRh()) {
            $proyectoAccesible = Proyecto::where('archivado', false)
                ->where(function ($q) use ($user) {
                    $q->where('usuario_id', $user->id)
                        ->orWhereHas('usuarios', fn ($uq) => $uq->where('users.id', $user->id))
                        ->orWhereHas('responsablesTi', fn ($rq) => $rq->where('users.id', $user->id));
                })
                ->pluck('id')
                ->toArray();

            if (in_array((int) $proyectoIdParam, $proyectoAccesible)) {
                return (int) $proyectoIdParam;
            }
            return null;
        }

        return (int) $proyectoIdParam;
    }

    private function construirQueryActividades($user, $targetUserId, $filterOrigin, $filtroProyectoId, $verTodo, $isHistoryView, $startDate, $endDate, $search)
    {
        $query = Activity::query();

        if ($filterOrigin === 'delegadas') {
            $query->where('asignado_por', $user->id)->where('user_id', '!=', $user->id);
        } elseif ($filterOrigin === 'propias') {
            $query->where('user_id', $user->id)->where('asignado_por', $user->id);
        } elseif ($filterOrigin === 'recibidas') {
            $query->where('user_id', $user->id)->where('asignado_por', '!=', $user->id);
        } elseif ($filterOrigin === 'todos' || $filterOrigin === '') {
            if ($targetUserId != $user->id && ($this->esSupervisor($user) || $this->esDireccion($user))) {
                $query->where('user_id', $targetUserId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere(function ($qq) use ($user) {
                          $qq->where('asignado_por', $user->id)->where('user_id', '!=', $user->id);
                      });
                });
            }
        } elseif ($targetUserId != $user->id && ($this->esSupervisor($user) || $this->esDireccion($user))) {
            $query->where('user_id', $targetUserId);
        } else {
            $query->where('user_id', $user->id);
        }

        if ($filtroProyectoId) {
            if ($filtroProyectoId === 'sin_proyecto') {
                $query->whereNull('proyecto_id');
            } else {
                $query->where('proyecto_id', $filtroProyectoId);
            }
        }

        if (!$verTodo && !$isHistoryView) {
            $query->whereNotIn('estatus', ['Completado', 'Completado con retardo', 'Rechazado']);
        } elseif ($verTodo) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('fecha_compromiso', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('fecha_final', [$startDate->toDateString(), $endDate->toDateString()]);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre_actividad', 'like', "%{$search}%")
                    ->orWhere('cliente', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%");
            });
        }

        return $query->with(['user.empleado.supervisor.user'])
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$user->id])
            ->orderByRaw("CASE WHEN estatus IN ('Completado', 'Completado con retardo') THEN 2 ELSE 1 END")
            ->orderByRaw("CASE prioridad WHEN 'Alta' THEN 1 WHEN 'Media' THEN 2 ELSE 4 END")
            ->orderBy('fecha_compromiso', 'asc')
            ->orderBy('created_at', 'desc')
            ->orderBy('hora_inicio_programada')
            ->get();
    }

    private function obtenerActividadesBorradas($user, $targetUserId, $verEliminadas)
    {
        if (!$verEliminadas) {
            return collect();
        }

        $esDireccion = $this->esDireccion($user);
        $esSupervisor = $this->esSupervisor($user);
        $miEmpleado = $user->empleado;
        $esCoordinador = $miEmpleado ? (bool) $miEmpleado->es_coordinador : false;

        if ($esDireccion) {
            $coordinadorUserIds = Empleado::where('es_coordinador', true)
                ->whereNotNull('user_id')->pluck('user_id')->toArray();
            if (!empty($coordinadorUserIds)) {
                return Activity::onlyTrashed()
                    ->with(['user', 'deletedByUser'])
                    ->whereIn('deleted_by', $coordinadorUserIds)
                    ->orderBy('deleted_at', 'desc')
                    ->limit(100)
                    ->get();
            }
        } elseif (($esCoordinador || $esSupervisor) && $targetUserId !== $user->id) {
            return Activity::onlyTrashed()
                ->with(['user', 'deletedByUser'])
                ->where('user_id', $targetUserId)
                ->orderBy('deleted_at', 'desc')
                ->limit(50)
                ->get();
        }

        return collect();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $miEmpleado = $user->empleado;

        $areasSistema = Empleado::where('es_activo', true)
            ->whereNotNull('posicion')->where('posicion', '!=', '')
            ->distinct()->orderBy('posicion')->pluck('posicion');

        if ($areasSistema->isEmpty()) {
            $areasSistema = collect(['General', 'Operativo', 'Administrativo']);
        }

        $empleadosAsignables = User::whereHas('empleado', function ($q) {
            $q->where('es_activo', true);
        })->orderBy('name')->get();

        $esDireccion = $this->esDireccion($user);
        $esSupervisor = $this->esSupervisor($user);
        $esCoordinador = $miEmpleado ? (bool) $miEmpleado->es_coordinador : false;
        $esPuestoPlanificador = false;
        $esHorarioPermitido = PlaneacionVentana::estaAbierta();
        $puedePlanificar = false;
        $idsVisibles = [$user->id];

        if ($miEmpleado) {
            $posicionLower = mb_strtolower($miEmpleado->posicion, 'UTF-8');
            $esPuestoPlanificador = Str::contains($posicionLower, ['anexo 24', 'anexo24', 'post-operacion', 'post operacion', 'auditoria']);
            if ($esPuestoPlanificador && $esHorarioPermitido) {
                $puedePlanificar = true;
            }
            $subordinadosIds = Empleado::where('supervisor_id', $miEmpleado->id)->pluck('user_id')->filter()->toArray();
            if (count($subordinadosIds) > 0) {
                $idsVisibles = array_merge($idsVisibles, $subordinadosIds);
            }
        }

        $targetUserId = $user->id;
        if (($esSupervisor || $esDireccion) && $request->filled('user_id')) {
            if ($esDireccion || in_array($request->user_id, $idsVisibles)) {
                $targetUserId = $request->user_id;
            }
        }
        $targetUser = User::findOrFail($targetUserId);

        list($startDate, $endDate, $periodLabel, $rangeType, $prevDateRef, $nextDateRef) = $this->parsearFechas($request);

        $isHistoryView = $endDate->lt(now()->startOfWeek());
        $verTodo = $request->has('ver_historial') && $request->ver_historial == '1';
        $filterOrigin = $request->input('filter_origin', 'todos');

        $esRhPermiso = $user->isRh();
        $filtroProyectoId = $this->obtenerFiltroProyecto($request, $user);

        $mainActivities = $this->construirQueryActividades($user, $targetUserId, $filterOrigin, $filtroProyectoId, $verTodo, $isHistoryView, $startDate, $endDate, $request->search);

        $puedeAsignarAOtros = $this->puedeAsignarAOtros($user);
        $teamUsers = collect();
        if ($esDireccion) {
            $teamUsers = User::orderBy('name')->get();
        } elseif ($esSupervisor || $esCoordinador) {
            $teamUsers = User::whereIn('id', $idsVisibles)->orderBy('name')->get();
        }

        $globalPendingCount = 0;
        $usersWithPending = [];

        if (($esSupervisor || $esDireccion) && $miEmpleado) {
            $subordinadosActivosIds = Empleado::where('supervisor_id', $miEmpleado->id)
                ->where('es_activo', true)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->toArray();

            if (count($subordinadosActivosIds) > 0) {
                $alertQuery = Activity::whereIn('estatus', ['Por Aprobar', 'Por Validar'])
                    ->where(function ($q) use ($subordinadosActivosIds, $user) {
                        $q->whereIn('user_id', $subordinadosActivosIds)
                          ->orWhere(function ($qq) use ($user, $subordinadosActivosIds) {
                              $qq->where('asignado_por', $user->id)
                                 ->whereNotIn('user_id', $subordinadosActivosIds)
                                  ->where('user_id', '!=', $user->id);
                          });
                    });
                $globalPendingCount = $alertQuery->count();
                $usersWithPending = $alertQuery->pluck('user_id')->unique()->toArray();
            }
        }

        $misRechazos = Activity::where('user_id', $user->id)->where('estatus', 'Rechazado')->get();

        $kpis = [
            'total' => $mainActivities->count(),
            'completadas' => $mainActivities->whereIn('estatus', ['Completado', 'Completado con retardo'])->count(),
            'proceso' => $mainActivities->where('estatus', 'En proceso')->count(),
            'planeadas' => $mainActivities->where('estatus', 'Planeado')->count(),
            'retardos' => $mainActivities->where('estatus', 'Retardo')->count(),
        ];

        $startOfWeek = now()->startOfWeek();
        $puedeGestionarPlaneacion = $esCoordinador;

        $esRh = $user->isRh();
        $esRhCoordinador = $user->isRhCoordinador();
        $proyectosQuery = Proyecto::where('archivado', false);

        if (! $esRhCoordinador) {
            $proyectosQuery->where(function ($q) use ($user) {
                $q->where('usuario_id', $user->id)
                    ->orWhereHas('usuarios', fn ($uq) => $uq->where('users.id', $user->id))
                    ->orWhereHas('responsablesTi', fn ($rq) => $rq->where('users.id', $user->id));
            });
        }
        $proyectos = $proyectosQuery->orderBy('nombre')->get();

        $verEliminadas = $request->get('ver_eliminadas') == '1';
        $deletedActivities = $this->obtenerActividadesBorradas($user, $targetUserId, $verEliminadas);

        return view('activities.index', compact(
            'mainActivities', 'teamUsers', 'targetUser', 'kpis',
            'esDireccion', 'esSupervisor', 'esCoordinador', 'puedeAsignarAOtros',
            'puedePlanificar', 'esPuestoPlanificador', 'esHorarioPermitido',
            'globalPendingCount', 'misRechazos',
            'isHistoryView', 'verTodo',
            'areasSistema', 'empleadosAsignables',
            'usersWithPending', 'filterOrigin',
            'startDate', 'endDate', 'periodLabel', 'rangeType', 'prevDateRef', 'nextDateRef', 'startOfWeek',
            'puedeGestionarPlaneacion', 'proyectos', 'esRh', 'deletedActivities'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_actividad' => 'required|max:255',
            'fecha_compromiso' => 'required|date',
            'area' => 'required|string',
        ]);

        $formToken = $request->input('form_token');
        if ($formToken) {
            $cacheKey = 'act_submit_' . Auth::id() . '_' . $formToken;
            if (Cache::has($cacheKey)) {
                return redirect()->back()->with('success', 'Actividad registrada correctamente.');
            }
            Cache::put($cacheKey, true, now()->addMinutes(5));
        }

        $data = $request->safe()->all();
        $currentUser = Auth::user();

        $proyectoId = $request->filled('proyecto_id') ? $request->proyecto_id : null;
        $targetUserId = $request->filled('assigned_to') ? $request->assigned_to : $currentUser->id;

        $data['user_id'] = $targetUserId;
        $data['asignado_por'] = $currentUser->id;
        $data['fecha_inicio'] = now();
        $data['metrico'] = 1;
        $data['estatus'] = $this->determinarEstatusInicial($targetUserId, $currentUser);

        if ($proyectoId) {
            $data['proyecto_id'] = $proyectoId;
        }

        Activity::create($data);

        $msg = ($data['estatus'] == 'Por Aprobar')
            ? 'Tarea enviada a validación del supervisor.'
            : 'Actividad asignada correctamente.';

        return redirect()->back()->with('success', $msg);
    }

    public function storeBatch(Request $request)
    {
        if (! PlaneacionVentana::estaAbierta()) {
            return redirect()->back()->with('error', 'El periodo de planificación semanal ha cerrado.');
        }

        $request->validate(['semana_inicio' => 'required|date', 'plan' => 'array']);

        return DB::transaction(function () use ($request) {
            $fechaBase = Carbon::parse($request->semana_inicio);
            $count = 0;

            if (empty($request->plan)) {
                return redirect()->back()->with('warning', 'Sin datos.');
            }

            foreach ($request->plan as $diaIndex => $tareasDelDia) {
                $fechaReal = $fechaBase->copy()->addDays($diaIndex);
                if (! is_array($tareasDelDia)) {
                    continue;
                }

                foreach ($tareasDelDia as $tarea) {
                    $nombre = trim($tarea['actividad'] ?? '');
                    if (empty($nombre)) {
                        continue;
                    }

                    Activity::create([
                        'user_id' => Auth::id(),
                        'asignado_por' => Auth::id(),
                        'area' => $tarea['area'] ?? 'General',
                        'cliente' => $tarea['cliente'] ?? null,
                        'tipo_actividad' => $tarea['tipo'] ?? 'Operativo',
                        'nombre_actividad' => $nombre,
                        'hora_inicio_programada' => $tarea['start_time'] ?? null,
                        'hora_fin_programada' => $tarea['end_time'] ?? null,
                        'fecha_inicio' => now(),
                        'fecha_compromiso' => $fechaReal,
                        'prioridad' => 'Media',
                        'estatus' => 'Por Aprobar',
                        'metrico' => 1,
                    ]);
                    $count++;
                }
            }

            return redirect()->route('activities.index')->with('success', "Plan enviado: {$count} actividades.");
        });
    }

    // --- CORRECCIÓN: AGREGADO EL MÉTODO SHOW PARA EVITAR EL ERROR ---
    public function show($id)
    {
        // Como usamos modales, no hay vista individual. Redirigimos al tablero.
        return redirect()->route('activities.index');
    }

    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);
        $user = Auth::user();

        $esDireccion = $user->empleado && str_contains(strtolower($user->empleado->posicion), 'direcc');
        $esSupervisor = false;
        if ($user->empleado && $activity->user->empleado && $user->empleado->id === $activity->user->empleado->supervisor_id) {
            $esSupervisor = true;
        }
        $esDueno = ($activity->user_id === $user->id);

        // Permiso extendido: El creador también puede editar (importante para jefes que asignan)
        $esAsignador = ($activity->asignado_por === $user->id);

        $puedeEditarTodo = $esDireccion || $esSupervisor || $esAsignador;

        $original = $activity->toArray();

        if (in_array($activity->estatus, ['Completado', 'Completado con retardo', 'Rechazado'])) {
            $activity->comentarios = $request->comentarios;
            $activity->save();
            return redirect()->back()->with('success', 'Comentarios actualizados.');
        }

        if ($puedeEditarTodo) {
            $activity->fill($request->except(['evidencia']));
        } else {
            // El analista solo puede mover estatus y comentar
            $activity->comentarios = $request->comentarios;

            // --- LOGICA DE CIERRE CON VALIDACIÓN ---
            $estatusActual = $activity->estatus;
            $nuevoEstatus = $request->estatus;

            if ($nuevoEstatus === 'Completado') {
                if ($esDireccion || $esSupervisor) {
                    $activity->estatus = 'Completado'; // Jefes cierran directo
                } else {
                    $activity->estatus = 'Por Validar'; // Empleados piden validación
                }
            } elseif (in_array($estatusActual, ['Completado', 'Completado con retardo', 'Por Validar'])) {
                // No permitir que analistas modifiquen estatus de actividades completadas o en validación
                // El estatus se mantiene igual, solo se guardan comentarios
            } elseif ($nuevoEstatus === 'En proceso' && $estatusActual === 'Rechazado') {
                // Permitir que el analista reabra una actividad rechazada
                $activity->estatus = 'En proceso';
            } else {
                // Para actividades en proceso o planeadas, permitir cambios normales de estatus
                $activity->estatus = $nuevoEstatus;
            }
        }

        // LOG DE CAMBIOS
        $mapaCampos = [
            'nombre_actividad' => 'Actividad',
            'estatus' => 'Estatus',
            'prioridad' => 'Prioridad',
            'fecha_compromiso' => 'Fecha Compromiso',
            'hora_inicio_programada' => 'Hora Inicio',
            'hora_fin_programada' => 'Hora Fin',
            'comentarios' => 'Comentarios',
            'cliente' => 'Cliente',
            'area' => 'Área',
            'proyecto_id' => 'Proyecto',
            'user_id' => 'Responsable',
        ];

        foreach ($activity->getDirty() as $campo => $nuevoValor) {
            if (! array_key_exists($campo, $mapaCampos)) {
                continue;
            }

            $nombreLegible = $mapaCampos[$campo];
            $valorAnterior = $original[$campo] ?? '-';

            if (str_contains($campo, 'fecha') && $valorAnterior !== '-') {
                $valorAnterior = \Carbon\Carbon::parse($valorAnterior)->format('Y-m-d');
                $nuevoValor = \Carbon\Carbon::parse($nuevoValor)->format('Y-m-d');
            }
            if (str_contains($campo, 'hora') && $valorAnterior !== '-') {
                $valorAnterior = substr($valorAnterior, 0, 5);
                $nuevoValor = substr($nuevoValor, 0, 5);
            }

            if ($valorAnterior == $nuevoValor) {
                continue;
            }

            $mensaje = ($campo === 'comentarios')
                ? 'Actualizó comentarios / bitácora'
                : "Cambió $nombreLegible: '$valorAnterior' ➝ '$nuevoValor'";

            ActivityHistory::create([
                'activity_id' => $activity->id,
                'user_id' => Auth::id(),
                'action' => 'updated',
                'details' => $mensaje,
            ]);
        }

        // Lógica automática de fechas (aunque el modelo ya tiene observer, reforzamos aquí por si acaso)
        if (in_array($activity->estatus, ['Completado', 'Completado con retardo']) && !in_array($original['estatus'], ['Completado', 'Completado con retardo'])) {
            $activity->fecha_final = now();
        }
        if (in_array($original['estatus'], ['Completado', 'Completado con retardo']) && !in_array($activity->estatus, ['Completado', 'Completado con retardo'])) {
            $activity->fecha_final = null;
            $activity->resultado_dias = null;
            $activity->porcentaje = null;
        }

        if ($request->hasFile('evidencia')) {
            if ($activity->evidencia_path) {
                Storage::disk('public')->delete($activity->evidencia_path);
            }
            $activity->evidencia_path = $request->file('evidencia')->store('evidencias', 'public');
            ActivityHistory::create([
                'activity_id' => $activity->id, 'user_id' => Auth::id(),
                'action' => 'file', 'details' => 'Adjuntó evidencia',
            ]);
        }

        $activity->save();

        if ($activity->estatus === 'Por Validar') {
            return redirect()->back()->with('success', 'Actividad enviada a revisión del supervisor.');
        }

        return redirect()->back()->with('success', 'Actualizado.');
    }

    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);
        $user = Auth::user();
        $miEmpleado = $user->empleado;

        if (! $miEmpleado) {
            abort(403, 'No tienes permiso para eliminar esta actividad.');
        }

        // Reglas de eliminación:
        // 1. Puedes borrar tu propia tarea si NO fue delegada por alguien más (asignado_por es null o eres tú mismo)
        // 2. Puedes borrar cualquier tarea que tú hayas asignado a otro (eres el asignado_por)
        $esPropia = $activity->user_id === $user->id;
        $esSelfCreated = is_null($activity->asignado_por) || $activity->asignado_por === $user->id;
        $esAsignador = $activity->asignado_por === $user->id;

        $puedeEliminar = ($esPropia && $esSelfCreated) || $esAsignador;

        if (! $puedeEliminar) {
            abort(403, 'Solo el responsable original puede eliminar esta actividad.');
        }

        ActivityHistory::create([
            'activity_id' => $activity->id,
            'user_id'     => $user->id,
            'action'      => 'deleted',
            'details'     => 'Eliminó la actividad: ' . $activity->nombre_actividad,
        ]);

        // Registrar quién eliminó sin disparar el hook de saving
        DB::table('activities')->where('id', $activity->id)->update(['deleted_by' => $user->id]);

        $activity->delete();

        return redirect()->back()->with('success', 'Actividad eliminada.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $user        = Auth::user();
        $deleted     = 0;
        $skipped     = 0;

        DB::transaction(function () use ($request, $user, &$deleted, &$skipped) {
            foreach ($request->ids as $id) {
                $activity = Activity::find((int) $id);
                if (! $activity) { $skipped++; continue; }

                $esPropia       = $activity->user_id === $user->id;
                $esSelfCreated  = is_null($activity->asignado_por) || $activity->asignado_por === $user->id;
                $esAsignador    = $activity->asignado_por === $user->id;

                if (! (($esPropia && $esSelfCreated) || $esAsignador)) { $skipped++; continue; }

                ActivityHistory::create([
                    'activity_id' => $activity->id,
                    'user_id'     => $user->id,
                    'action'      => 'deleted',
                    'details'     => 'Eliminó la actividad (masivo): ' . $activity->nombre_actividad,
                ]);
                DB::table('activities')->where('id', $activity->id)->update(['deleted_by' => $user->id]);
                $activity->delete();
                $deleted++;
            }
        });

        $msg = $deleted . ' ' . ($deleted === 1 ? 'tarea eliminada.' : 'tareas eliminadas.');
        if ($skipped > 0) $msg .= " {$skipped} omitida(s) por permisos.";

        return response()->json(['success' => true, 'deleted' => $deleted, 'skipped' => $skipped, 'message' => $msg]);
    }

    public function approve(Request $request, $id)
    {
        $act = Activity::with(['user.empleado', 'asignador.empleado'])->findOrFail($id);
        $currentUser = Auth::user();

        $soyDireccion = $this->esDireccion($currentUser);
        $soySuJefeDirecto = $this->esSupervisorDirectoDe($currentUser, $act->user);

        $assigner = $act->asignador;
        $target = $act->user;

        $assignerIsSupervisor = $this->esSupervisor($assigner);
        $targetIsSupervisor = $this->esSupervisor($target);

        $esCasoSupervisorASupervisor = $assignerIsSupervisor && $targetIsSupervisor;

        if ($esCasoSupervisorASupervisor) {
            if (! $soyDireccion) {
                return back()->with('error', 'Acción denegada: Las tareas entre coordinadores requieren aprobación de Dirección.');
            }
        } else {
            if (! $soyDireccion && ! $soySuJefeDirecto) {
                return back()->with('error', 'Acción denegada: No eres el supervisor directo de este colaborador.');
            }
        }

        $act->estatus = 'Planeado';
        $act->motivo_rechazo = null;
        $act->save();
        ActivityHistory::create(['activity_id' => $id, 'user_id' => Auth::id(), 'action' => 'approved', 'details' => 'Aprobó la actividad']);

        return back()->with('success', 'Aprobada.');
    }

    public function reject(Request $request, $id)
    {
        $act = Activity::findOrFail($id);
        $user = Auth::user();

        $esDireccion = $this->esDireccion($user);
        $esSupervisor = $this->esSupervisorDirectoDe($user, $act->user);

        if (! $esDireccion && ! $esSupervisor) {
            abort(403, 'No tienes permiso para rechazar esta actividad.');
        }

        $act->estatus = 'Rechazado';
        $act->motivo_rechazo = $request->input('motivo', 'Revisión');
        $act->save();
        ActivityHistory::create(['activity_id' => $id, 'user_id' => Auth::id(), 'action' => 'rejected', 'details' => 'Rechazó: '.$request->motivo]);

        return back()->with('warning', 'Rechazada.');
    }

    public function start($id)
    {
        $act = Activity::findOrFail($id);

        if ($act->user_id !== Auth::id()) {
            abort(403, 'Solo el responsable puede iniciar esta actividad.');
        }

        $act->estatus = 'En proceso';
        $act->fecha_inicio = now();
        $act->save();
        ActivityHistory::create(['activity_id' => $id, 'user_id' => Auth::id(), 'action' => 'updated', 'details' => 'Inició ejecución']);

        return back()->with('success', 'Iniciada.');
    }

    public function validateCompletion(Request $request, $id)
    {
        $act = Activity::findOrFail($id);
        $user = Auth::user();

        $esDireccion = $this->esDireccion($user);
        $esSupervisor = $this->esSupervisorDirectoDe($user, $act->user);

        if ($esDireccion || $esSupervisor) {
            $act->estatus = 'Completado';
            $act->fecha_final = now();
            $act->save();

            ActivityHistory::create([
                'activity_id' => $id,
                'user_id' => Auth::id(),
                'action' => 'validated',
                'details' => 'Validó el cierre de la actividad',
            ]);

            return back()->with('success', 'Actividad validada y cerrada correctamente.');
        }

        return back()->with('error', 'No tienes permiso para validar esta actividad.');
    }

    public function generateClientReport(Request $request)
    {
        $request->validate([
            'cliente_reporte' => 'required|string',
            'mes_reporte' => 'required|date_format:Y-m',
        ]);

        $cliente = $request->cliente_reporte;
        $fecha = Carbon::createFromFormat('Y-m', $request->mes_reporte);

        $inicioMes = $fecha->copy()->startOfMonth();
        $finMes = $fecha->copy()->endOfMonth();

        $actividades = Activity::with(['user'])
            ->where('cliente', 'LIKE', "%{$cliente}%")
            ->where(function ($q) use ($inicioMes, $finMes) {
                $q->whereBetween('fecha_compromiso', [$inicioMes, $finMes])
                    ->orWhereBetween('fecha_final', [$inicioMes, $finMes]);
            })
            ->orderBy('fecha_compromiso', 'desc')
            ->get();

        $stats = [
            'total' => $actividades->count(),
            'completadas' => $actividades->whereIn('estatus', ['Completado', 'Completado con retardo'])->count(),
            'en_proceso' => $actividades->whereIn('estatus', ['En proceso', 'Planeado', 'Por Validar', 'Por Aprobar'])->count(),
            'efectividad' => 0,
        ];

        if ($stats['total'] > 0) {
            $stats['efectividad'] = round(($stats['completadas'] / $stats['total']) * 100, 1);
        }

        return view('activities.report_print', compact('actividades', 'cliente', 'fecha', 'stats'));
    }

    // -------------------------------------------------------
    // Gestión de Ventana de Planeación (solo Admin)
    // -------------------------------------------------------

    public function getPlaneacionVentanas()
    {
        $user = Auth::user();
        $me = $user->empleado;
        if (! $me || ! $me->es_coordinador) {
            abort(403, 'No tienes acceso a las ventanas de planeación.');
        }

        try {
            $ventanas = PlaneacionVentana::orderBy('dia_semana')->orderBy('hora_apertura')->get()
                ->map(function ($v) {
                    $v->dia_nombre = PlaneacionVentana::$diasNombres[$v->dia_semana] ?? "Día {$v->dia_semana}";

                    return $v;
                });

            return response()->json(['ventanas' => $ventanas]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'ventanas' => []], 500);
        }
    }

    public function savePlaneacionVentana(Request $request)
    {
        $user = Auth::user();
        $me = $user->empleado;
        if (! $me || ! $me->es_coordinador) {
            abort(403, 'No tienes permiso para guardar ventanas de planeación.');
        }

        $request->validate([
            'dia_semana' => 'required|integer|between:1,7',
            'hora_apertura' => 'required|date_format:H:i',
            'hora_cierre' => 'required|date_format:H:i|after:hora_apertura',
        ]);

        PlaneacionVentana::where('dia_semana', $request->dia_semana)
            ->where('activo', true)
            ->update(['activo' => false]);

        $ventana = PlaneacionVentana::create([
            'dia_semana' => $request->dia_semana,
            'hora_apertura' => $request->hora_apertura.':00',
            'hora_cierre' => $request->hora_cierre.':00',
            'activo' => true,
            'creado_por' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'ventana' => $ventana,
            'message' => 'Ventana de planeación guardada.',
        ]);
    }

    public function deletePlaneacionVentana($id)
    {
        $me = Auth::user()->empleado;
        if (! $me || ! $me->es_coordinador) {
            abort(403, 'No tienes permiso para eliminar ventanas de planeación.');
        }

        try {
            PlaneacionVentana::findOrFail($id)->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $miEmpleado = $user->empleado;

        $dateStart = $request->filled('date_start') ? Carbon::parse($request->date_start) : now()->startOfWeek();
        $dateEnd = $request->filled('date_end') ? Carbon::parse($request->date_end)->endOfDay() : now()->endOfWeek();

        $userIds = $request->input('user_ids', []);

        if (empty($userIds)) {
            $userIds = [$user->id];
        }

        $esDireccion = $miEmpleado && Str::contains(mb_strtolower($miEmpleado->posicion ?? '', 'UTF-8'), 'direcc');
        $subordinadosIds = $miEmpleado
            ? Empleado::where('supervisor_id', $miEmpleado->id)->pluck('user_id')->filter()->toArray()
            : [];
        $esSupervisor = count($subordinadosIds) > 0;

        if ($esDireccion) {
            // Dirección puede exportar cualquier usuario
        } elseif ($esSupervisor) {
            $userIds = array_filter($userIds, function ($id) use ($user, $subordinadosIds) {
                return $id == $user->id || in_array($id, $subordinadosIds);
            });
        } else {
            $userIds = [$user->id];
        }

        if (empty($userIds)) {
            $userIds = [$user->id];
        }

        $activities = Activity::with(['user', 'asignador'])
            ->whereIn('user_id', $userIds)
            ->where(function ($q) use ($dateStart, $dateEnd) {
                $q->whereBetween('fecha_compromiso', [$dateStart->toDateString(), $dateEnd->toDateString()])
                    ->orWhereBetween('fecha_final', [$dateStart->toDateString(), $dateEnd->toDateString()]);
            })
            ->orderBy('user_id')
            ->orderBy('created_at', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet;

        foreach ($userIds as $index => $userId) {
            $targetUser = User::find($userId);
            if (! $targetUser) {
                continue;
            }

            $userActivities = $activities->where('user_id', $userId)->values();

            if ($index === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            $sheetName = substr($targetUser->name, 0, 30);
            $sheet->setTitle($sheetName);

            $headers = ['#', 'Descripción', 'Prioridad', 'Cliente', 'Área', 'Responsable', 'Asignado Por', 'F. Asignación', 'F. Compromiso', 'H. Inicio', 'F. Final', 'Días', '%', 'Estatus', 'Comentarios'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col.'1', $header);
                $sheet->getStyle($col.'1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);
                $sheet->getColumnDimension($col)->setAutoSize(true);
                $col++;
            }
            $sheet->getRowDimension('1')->setRowHeight(25);

            $row = 2;
            foreach ($userActivities as $i => $act) {
                $sheet->setCellValue('A'.$row, $i + 1);
                $sheet->setCellValue('B'.$row, $act->nombre_actividad);
                $sheet->setCellValue('C'.$row, $act->prioridad);
                $sheet->setCellValue('D'.$row, $act->cliente ?? '-');
                $sheet->setCellValue('E'.$row, $act->area ?? '-');
                $sheet->setCellValue('F'.$row, $act->user->name ?? 'N/A');
                $sheet->setCellValue('G'.$row, $act->asignador->name ?? '-');
                $sheet->setCellValue('H'.$row, $act->created_at ? $act->created_at->format('d/m/Y H:i') : '-');
                $sheet->setCellValue('I'.$row, $act->fecha_compromiso ? $act->fecha_compromiso->format('d/m/Y') : '-');
                $sheet->setCellValue('J'.$row, $act->hora_inicio_programada ? substr($act->hora_inicio_programada, 0, 5) : '-');
                $sheet->setCellValue('K'.$row, $act->fecha_final ? $act->fecha_final->format('d/m/Y') : '-');
                $sheet->setCellValue('L'.$row, $act->resultado_dias ?? '-');
                $sheet->setCellValue('M'.$row, $act->porcentaje ? number_format($act->porcentaje, 0).'%' : '-');
                $sheet->setCellValue('N'.$row, $act->estatus);
                $sheet->setCellValue('O'.$row, $act->comentarios ?? '');

                $statusColor = match ($act->estatus) {
                    'Completado' => '10B981',
                    'Completado con retardo' => 'F59E0B',
                    'En proceso' => '3B82F6',
                    'Planeado' => '6366F1',
                    'Por Aprobar' => 'F97316',
                    'Por Validar' => 'A855F7',
                    'Retardo' => 'EF4444',
                    'Rechazado' => 'DC2626',
                    default => '6B7280',
                };

                $priorityColor = match ($act->prioridad) {
                    'Alta' => 'FEE2E2',
                    'Media' => 'FEF3C7',
                    'Baja' => 'DBEAFE',
                    default => 'FFFFFF',
                };

                $sheet->getStyle('C'.$row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $priorityColor]],
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                $sheet->getStyle('N'.$row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statusColor]],
                    'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                $sheet->getRowDimension($row)->setRowHeight(20);
                $row++;
            }

            $lastRow = $row - 1;
            if ($lastRow >= 1) {
                $sheet->getStyle('A1:O'.$lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                ]);
                $sheet->setAutoFilter('A1:O1');
            }
        }

        $fileName = 'Actividades_'.$dateStart->format('dmY').'_'.$dateEnd->format('dmY').'.xlsx';

        $response = response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    public function previewImport(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt']);

            $currentUser = Auth::user();
            $miEmpleado  = $currentUser->empleado;

            $file = $request->file('file');
            $path = $file->storeAs('temp', Str::uuid() . '.' . $file->getClientOriginalExtension());
            $fullPath = Storage::path($path);

            $inputFileType = IOFactory::identify($fullPath);
            $reader = IOFactory::createReader($inputFileType);
            if ($inputFileType === 'Csv') {
                $reader->setDelimiter(',');
                $reader->setEnclosure('"');
                $reader->setInputEncoding('UTF-8');
            }
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, false);

            Storage::delete($path);

            if (empty($rows) || count($rows) < 2) {
                return response()->json(['success' => false, 'message' => 'El archivo está vacío o solo tiene encabezados.'], 422);
            }

            $headers = array_shift($rows);
            $headerMap = [];
            foreach ($headers as $i => $h) {
                $normalized = mb_strtolower(trim((string) ($h ?? '')));
                $normalized = str_replace([' ', '-', '_'], '', $normalized);
                $headerMap[$normalized] = $i;
            }

            $campoNombre = $headerMap['nombreactividad'] ?? $headerMap['actividad'] ?? $headerMap['descripcion'] ?? $headerMap['tarea'] ?? null;
            if ($campoNombre === null) {
                return response()->json(['success' => false, 'message' => 'No se encontró columna de actividad. Use: nombre_actividad, actividad, descripcion o tarea.'], 422);
            }

            $campoFecha      = $headerMap['fechacompromiso'] ?? $headerMap['fecha'] ?? $headerMap['fechalimite'] ?? $headerMap['deadline'] ?? null;
            $campoArea       = $headerMap['area'] ?? null;
            $campoCliente    = $headerMap['cliente'] ?? null;
            $campoPrioridad  = $headerMap['prioridad'] ?? null;
            $campoTipo       = $headerMap['tipoactividad'] ?? $headerMap['tipo'] ?? null;
            $campoHoraInicio = $headerMap['horainicio'] ?? $headerMap['inicio'] ?? null;
            $campoHoraFin    = $headerMap['horafin'] ?? $headerMap['fin'] ?? null;
            $campoComent     = $headerMap['comentarios'] ?? $headerMap['comentario'] ?? $headerMap['notas'] ?? $headerMap['observaciones'] ?? null;

            $tasks = [];
            foreach ($rows as $row) {
                $nombre = trim((string) ($row[$campoNombre] ?? ''));
                if (empty($nombre)) continue;

                $fecha = null;
                if ($campoFecha !== null && !empty($row[$campoFecha])) {
                    $fecha = $this->parseDate($row[$campoFecha]);
                }

                $tasks[] = [
                    'nombre_actividad'    => $nombre,
                    'fecha_compromiso'    => $fecha ? $fecha->format('Y-m-d') : now()->format('Y-m-d'),
                    'area'                => ($campoArea !== null && !empty($row[$campoArea])) ? trim($row[$campoArea]) : 'General',
                    'cliente'             => ($campoCliente !== null && !empty($row[$campoCliente])) ? trim($row[$campoCliente]) : null,
                    'prioridad'           => $this->normalizePriority(($campoPrioridad !== null && !empty($row[$campoPrioridad])) ? $row[$campoPrioridad] : 'Media'),
                    'tipo_actividad'      => ($campoTipo !== null && !empty($row[$campoTipo])) ? trim($row[$campoTipo]) : 'Operativo',
                    'hora_inicio_programada' => ($campoHoraInicio !== null && !empty($row[$campoHoraInicio])) ? $this->parseTime($row[$campoHoraInicio]) : null,
                    'hora_fin_programada' => ($campoHoraFin !== null && !empty($row[$campoHoraFin])) ? $this->parseTime($row[$campoHoraFin]) : null,
                    'comentarios'         => ($campoComent !== null && !empty($row[$campoComent])) ? trim($row[$campoComent]) : null,
                ];
            }

            if (empty($tasks)) {
                return response()->json(['success' => false, 'message' => 'No se encontraron tareas válidas en el archivo.'], 422);
            }

            return response()->json(['success' => true, 'tasks' => $tasks, 'total' => count($tasks)]);
        } catch (\Throwable $e) {
            if (isset($path) && Storage::exists($path)) {
                Storage::delete($path);
            }
            Log::error('previewImport: ' . $e->getMessage(), [
                'file' => $request->file('file')?->getClientOriginalName(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Error al procesar el archivo: ' . $e->getMessage()], 422);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'tasks'      => 'required|array|min:1',
            'tasks.*.nombre_actividad' => 'required|string|max:500',
            'tasks.*.assigned_to'      => 'required|exists:users,id',
            'tasks.*.fecha_compromiso' => 'nullable|date',
            'proyecto_id' => 'nullable|exists:proyectos,id',
        ]);

        $currentUser = Auth::user();
        $miEmpleado  = $currentUser->empleado;

        $esDireccion     = $miEmpleado && Str::contains(mb_strtolower($miEmpleado->posicion ?? '', 'UTF-8'), 'direcc');
        $esCoordinadorImp = $miEmpleado ? (bool) $miEmpleado->es_coordinador : false;
        $esSupervisorImp  = $miEmpleado && Empleado::where('supervisor_id', $miEmpleado->id)->exists();
        $puedeAsignarImp  = $esDireccion || $esCoordinadorImp || $esSupervisorImp;

        $subordinadosIds = $miEmpleado
            ? Empleado::where('supervisor_id', $miEmpleado->id)->pluck('user_id')->filter()->values()->toArray()
            : [];

        $proyectoId = $request->filled('proyecto_id') ? $request->proyecto_id : null;
        $imported   = 0;

        DB::beginTransaction();
        try {
            foreach ($request->tasks as $taskData) {
                // Usuarios normales solo pueden asignarse a sí mismos
                $targetUserId = $puedeAsignarImp
                    ? (int) $taskData['assigned_to']
                    : $currentUser->id;

                // Determinar estatus según jerarquía
                if ($targetUserId === $currentUser->id) {
                    $estatus = 'En proceso';
                } elseif ($esDireccion) {
                    $estatus = 'Planeado';
                } elseif (in_array($targetUserId, $subordinadosIds)) {
                    $estatus = 'Planeado';
                } else {
                    $estatus = 'Por Aprobar';
                }

                $data = [
                    'user_id'                 => $targetUserId,
                    'asignado_por'            => $currentUser->id,
                    'nombre_actividad'        => trim($taskData['nombre_actividad']),
                    'fecha_inicio'            => now(),
                    'fecha_compromiso'        => !empty($taskData['fecha_compromiso']) ? $taskData['fecha_compromiso'] : now(),
                    'area'                    => !empty($taskData['area']) ? $taskData['area'] : 'General',
                    'cliente'                 => $taskData['cliente'] ?? null,
                    'prioridad'               => $this->normalizePriority($taskData['prioridad'] ?? 'Media'),
                    'tipo_actividad'          => !empty($taskData['tipo_actividad']) ? $taskData['tipo_actividad'] : 'Operativo',
                    'hora_inicio_programada'  => $taskData['hora_inicio_programada'] ?? null,
                    'hora_fin_programada'     => $taskData['hora_fin_programada'] ?? null,
                    'comentarios'             => $taskData['comentarios'] ?? null,
                    'estatus'                 => $estatus,
                    'metrico'                 => 1,
                ];

                if ($proyectoId) {
                    $data['proyecto_id'] = $proyectoId;
                }

                Activity::create($data);
                $imported++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('activities.import: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error durante la importación: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success'  => true,
            'message'  => "Importación completada: {$imported} " . ($imported === 1 ? 'tarea creada.' : 'tareas creadas.'),
            'imported' => $imported,
        ]);
    }

    public function downloadImportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['nombre_actividad', 'fecha_compromiso', 'area', 'cliente', 'prioridad', 'tipo_actividad', 'hora_inicio', 'hora_fin', 'comentarios'];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4F46E5');
            $sheet->getStyle($col . '1')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($col . '1')->getAlignment()->setHorizontal('center');
            $col++;
        }

        $sheet->setCellValue('A2', 'Ejemplo de actividad');
        $sheet->setCellValue('B2', now()->format('Y-m-d'));
        $sheet->setCellValue('C2', 'General');
        $sheet->setCellValue('D2', 'Cliente ejemplo');
        $sheet->setCellValue('E2', 'Media');
        $sheet->setCellValue('F2', 'Operativo');

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $response = response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="plantilla_importacion_actividades.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((int) $value);
            } catch (\Exception $e) {
                return null;
            }
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}$/', $value)) {
            $parts = explode('/', $value);
            if (strlen($parts[2]) === 2) {
                $parts[2] = '20' . $parts[2];
            }
            $value = $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        } elseif (preg_match('/^\d{1,2}-\d{1,2}-\d{2,4}$/', $value)) {
            $parts = explode('-', $value);
            if (strlen($parts[2]) === 2) {
                $parts[2] = '20' . $parts[2];
            }
            $value = $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseTime($value)
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                $excelDate = ExcelDate::excelToDateTimeObject($value);
                return $excelDate->format('H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        }

        $value = trim((string) $value);

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m)) {
            $h = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $i = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $s = isset($m[3]) ? str_pad($m[3], 2, '0', STR_PAD_LEFT) : '00';
            return "{$h}:{$i}:{$s}";
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizePriority($value)
    {
        $lower = mb_strtolower(trim((string) $value));
        return match ($lower) {
            'alta', 'high', 'alto', 'urgente', 'critica', 'critical' => 'Alta',
            'baja', 'low', 'bajo', 'opcional' => 'Baja',
            default => 'Media',
        };
    }
}
