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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $miEmpleado = $user->empleado;

        // 1. CARGA DINÁMICA DE POSICIONES
        $areasSistema = Empleado::where('es_activo', true)
            ->whereNotNull('posicion')->where('posicion', '!=', '')
            ->distinct()->orderBy('posicion')->pluck('posicion');

        if ($areasSistema->isEmpty()) {
            $areasSistema = collect(['General', 'Operativo', 'Administrativo']);
        }

        // LISTA DE USUARIOS
        $empleadosAsignables = User::whereHas('empleado', function ($q) {
            $q->where('es_activo', true);
        })->orderBy('name')->get();

        // 2. PERMISOS
        $esDireccion = false;
        $esSupervisor = false;
        $esPuestoPlanificador = false;
        $esHorarioPermitido = false;
        $puedePlanificar = false;
        $idsVisibles = [$user->id];

        if ($miEmpleado) {
            $posicionLower = mb_strtolower($miEmpleado->posicion, 'UTF-8');
            $esPuestoPlanificador = Str::contains($posicionLower, ['anexo 24', 'anexo24', 'post-operacion', 'post operacion', 'auditoria']);

            // VALIDACIÓN HORARIA (configurable desde BD, fallback lunes 9-11)
            $esHorarioPermitido = PlaneacionVentana::estaAbierta();

            if ($esPuestoPlanificador && $esHorarioPermitido) {
                $puedePlanificar = true;
            }
            if (str_contains($posicionLower, 'direcc')) {
                $esDireccion = true;
            }

            $subordinadosIds = Empleado::where('supervisor_id', $miEmpleado->id)->pluck('user_id')->filter()->toArray();
            if (count($subordinadosIds) > 0) {
                $esSupervisor = true;
                $idsVisibles = array_merge($idsVisibles, $subordinadosIds);
            }
        }

        // 3. CONTEXTO
        $targetUserId = $user->id;
        if (($esSupervisor || $esDireccion) && $request->filled('user_id')) {
            if ($esDireccion || in_array($request->user_id, $idsVisibles)) {
                $targetUserId = $request->user_id;
            }
        }
        $targetUser = User::findOrFail($targetUserId);

        // 4. LÓGICA DE FECHAS FLEXIBLE
        $rangeType = $request->input('range', 'week');

        if ($request->filled('date_start') && $request->filled('date_end')) {
            $startDate = Carbon::parse($request->date_start)->startOfDay();
            $endDate = Carbon::parse($request->date_end)->endOfDay();
            $rangeType = 'custom';
            $periodLabel = 'Rango: '.$startDate->format('d/m').' - '.$endDate->format('d/m');

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
                    $periodLabel = 'Trimestre: '.$startDate->format('M').' - '.$endDate->format('M Y');
                    $prevDateRef = $startDate->copy()->subMonths(3)->format('Y-m-d');
                    $nextDateRef = $startDate->copy()->addMonths(3)->format('Y-m-d');
                    break;

                case 'week':
                default:
                    $startDate = $refDate->copy()->startOfWeek();
                    $endDate = $refDate->copy()->endOfWeek();
                    $periodLabel = 'Semana: '.$startDate->format('d M').' - '.$endDate->format('d M');
                    $prevDateRef = $startDate->copy()->subWeek()->format('Y-m-d');
                    $nextDateRef = $startDate->copy()->addWeek()->format('Y-m-d');
                    break;
            }
        }

        $isHistoryView = $endDate->lt(now()->startOfWeek());
        $verTodo = $request->has('ver_historial') && $request->ver_historial == '1';
        $filterOrigin = $request->input('filter_origin', 'todos');

        // FILTRO DE PROYECTO - verificar permisos
        $esRhPermiso = $user->isRh();
        $filtroProyectoId = null;

        if ($request->filled('proyecto_id')) {
            $proyectoIdParam = $request->proyecto_id;

            // Verificar que el proyecto pertenece al usuario (solo si no es RH)
            if (! $esRhPermiso) {
                $proyectoAccesible = Proyecto::where('archivado', false)
                    ->where(function ($q) use ($user) {
                        $q->where('usuario_id', $user->id)
                            ->orWhereHas('usuarios', fn ($uq) => $uq->where('users.id', $user->id))
                            ->orWhereHas('responsablesTi', fn ($rq) => $rq->where('users.id', $user->id));
                    })
                    ->pluck('id')
                    ->toArray();

                if ($proyectoIdParam === 'sin_proyecto') {
                    $filtroProyectoId = 'sin_proyecto';
                } elseif (in_array((int) $proyectoIdParam, $proyectoAccesible)) {
                    $filtroProyectoId = (int) $proyectoIdParam;
                }
            } else {
                if ($proyectoIdParam === 'sin_proyecto') {
                    $filtroProyectoId = 'sin_proyecto';
                } else {
                    $filtroProyectoId = (int) $proyectoIdParam;
                }
            }
        }

        // Inicializar query base
        $query = Activity::query();

        // Filtrar por usuario
        if ($esDireccion) {
            // Dirección ve todo sin restricción de usuario
        } elseif ($request->filled('user_id')) {
            // Supervisor/dirección seleccionó un usuario específico
            $query->where('user_id', $targetUserId);
        } else {
            // Supervisor sin seleccionar = solo sus propias actividades
            $query->where('user_id', $user->id);
        }

        // Aplicar filtro de proyecto
        if ($filtroProyectoId) {
            if ($filtroProyectoId === 'sin_proyecto') {
                $query->whereNull('proyecto_id');
            } else {
                $query->where('proyecto_id', $filtroProyectoId);
            }
        }

        if (! $verTodo && ! $isHistoryView) {
            $query->whereNotIn('estatus', ['Completado', 'Rechazado']);
            $query->whereBetween('fecha_compromiso', [$startDate->toDateString(), $endDate->toDateString()]);
        } elseif ($verTodo) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('fecha_compromiso', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('fecha_final', [$startDate->toDateString(), $endDate->toDateString()]);
            });
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre_actividad', 'like', "%{$request->search}%")
                    ->orWhere('cliente', 'like', "%{$request->search}%")
                    ->orWhere('area', 'like', "%{$request->search}%");
            });
        }

        $mainActivities = $query
            ->orderByRaw("CASE WHEN user_id = {$user->id} THEN 0 ELSE 1 END")
            ->orderByRaw("CASE estatus WHEN 'Completado' THEN 2 ELSE 1 END")
            ->orderByRaw("CASE prioridad WHEN 'Alta' THEN 1 WHEN 'Media' THEN 2 ELSE 4 END")
            ->orderBy('fecha_compromiso', 'asc')
            ->orderBy('created_at', 'desc')
            ->orderBy('hora_inicio_programada')
            ->get();

        // 5. VARIABLES DE EQUIPO
        $teamUsers = collect();
        if ($esDireccion) {
            $teamUsers = User::orderBy('name')->get();
        } elseif ($esSupervisor) {
            $teamUsers = User::whereIn('id', $idsVisibles)->orderBy('name')->get();
        }

        // ALERTAS
        $globalPendingCount = 0;
        $usersWithPending = [];

        if ($esSupervisor || $esDireccion) {
            $alertQuery = Activity::where('estatus', 'Por Aprobar');
            if (! $esDireccion) {
                $alertQuery->whereIn('user_id', $idsVisibles);
            }
            $globalPendingCount = $alertQuery->count();
            $usersWithPending = $alertQuery->pluck('user_id')->unique()->toArray();
        }

        $misRechazos = Activity::where('user_id', $user->id)->where('estatus', 'Rechazado')->get();

        $kpis = [
            'total' => $mainActivities->count(),
            'completadas' => $mainActivities->where('estatus', 'Completado')->count(),
            'proceso' => $mainActivities->where('estatus', 'En proceso')->count(),
            'planeadas' => $mainActivities->where('estatus', 'Planeado')->count(),
            'retardos' => $mainActivities->where('estatus', 'Retardo')->count(),
        ];

        $startOfWeek = now()->startOfWeek();
        $puedeGestionarPlaneacion = $miEmpleado && $miEmpleado->es_coordinador;

        // Cargar proyectos disponibles para el usuario
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

        return view('activities.index', compact(
            'mainActivities', 'teamUsers', 'targetUser', 'kpis',
            'esDireccion', 'esSupervisor',
            'puedePlanificar', 'esPuestoPlanificador', 'esHorarioPermitido',
            'globalPendingCount', 'misRechazos',
            'isHistoryView', 'verTodo',
            'areasSistema', 'empleadosAsignables',
            'usersWithPending', 'filterOrigin',
            'startDate', 'endDate', 'periodLabel', 'rangeType', 'prevDateRef', 'nextDateRef', 'startOfWeek',
            'puedeGestionarPlaneacion', 'proyectos', 'esRh'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_actividad' => 'required|max:255',
            'fecha_compromiso' => 'required|date',
            'area' => 'required|string',
        ]);

        $data = $request->all();
        $currentUser = Auth::user();

        // Proyecto_id (opcional)
        $proyectoId = $request->filled('proyecto_id') ? $request->proyecto_id : null;

        // Determinar destinatario
        $targetUserId = $request->filled('assigned_to') ? $request->assigned_to : $currentUser->id;

        $data['user_id'] = $targetUserId;
        $data['asignado_por'] = $currentUser->id;
        $data['fecha_inicio'] = now();
        $data['metrico'] = 1;

        // --- REGLA DE JERARQUÍA ---
        if ($targetUserId == $currentUser->id) {
            $data['estatus'] = 'En proceso';
        } else {
            $soyDireccion = $currentUser->empleado && Str::contains(strtolower($currentUser->empleado->posicion), 'direcc');
            $targetUser = User::with('empleado')->find($targetUserId);
            $soySuJefe = false;

            if ($targetUser?->empleado && $currentUser?->empleado) {
                if ($targetUser->empleado->supervisor_id === $currentUser->empleado->id) {
                    $soySuJefe = true;
                }
            }

            $soySupervisor = false;
            if ($currentUser->empleado) {
                $soySupervisor = Empleado::where('supervisor_id', $currentUser->empleado->id)->exists();
            }
            $esDestinoSupervisor = false;
            if ($targetUser && $targetUser->empleado) {
                $esDestinoSupervisor = Empleado::where('supervisor_id', $targetUser->empleado->id)->exists();
            }

            if ($soyDireccion) {
                $data['estatus'] = 'Planeado';
            } elseif ($soySuJefe) {
                $data['estatus'] = 'Planeado';
            } elseif ($soySupervisor && $esDestinoSupervisor) {
                $data['estatus'] = 'Por Aprobar';
            } else {
                $data['estatus'] = 'Por Aprobar';
            }
        }

        // Agregar proyecto si se seleccionó
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
        if (! (now()->isMonday() && now()->hour >= 9 && now()->hour < 11)) {
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
        if ($activity->estatus == 'Completado' && $original['estatus'] != 'Completado') {
            $activity->fecha_final = now();
        }
        if ($original['estatus'] == 'Completado' && $activity->estatus != 'Completado') {
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

        $esDireccion = $user->empleado && str_contains(strtolower($user->empleado->posicion), 'direcc');
        $esSupervisor = $user->empleado && $activity->user->empleado && $user->empleado->id === $activity->user->empleado->supervisor_id;

        if ($esDireccion || $esSupervisor) {
            $activity->delete();

            return redirect()->back()->with('success', 'Eliminado.');
        }
        abort(403, 'No tienes permiso para eliminar esta actividad.');
    }

    public function approve(Request $request, $id)
    {
        $act = Activity::with(['user.empleado', 'asignador.empleado'])->findOrFail($id);
        $currentUser = Auth::user();

        $soyDireccion = $currentUser->empleado && Str::contains(strtolower($currentUser->empleado->posicion), 'direcc');

        $soySuJefeDirecto = false;
        if ($act->user->empleado && $currentUser->empleado) {
            if ($act->user->empleado->supervisor_id === $currentUser->empleado->id) {
                $soySuJefeDirecto = true;
            }
        }

        $assigner = $act->asignador;
        $target = $act->user;

        $assignerIsSupervisor = $assigner && $assigner->empleado && Empleado::where('supervisor_id', $assigner->empleado->id)->exists();
        $targetIsSupervisor = $target && $target->empleado && Empleado::where('supervisor_id', $target->empleado->id)->exists();

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

        $esDireccion = $user->empleado && str_contains(strtolower($user->empleado->posicion), 'direcc');
        $esSupervisor = $user->empleado && $act->user->empleado && $user->empleado->id === $act->user->empleado->supervisor_id;

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

        // Validar permisos (Dirección o Supervisor directo)
        $esDireccion = $user->empleado && str_contains(strtolower($user->empleado->posicion), 'direcc');
        $esSupervisor = $user->empleado && $act->user->empleado && $user->empleado->id === $act->user->empleado->supervisor_id;

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
            'completadas' => $actividades->where('estatus', 'Completado')->count(),
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
            abort(403);
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
            abort(403);
        }

        $request->validate([
            'dia_semana' => 'required|integer|between:1,7',
            'hora_apertura' => 'required|date_format:H:i',
            'hora_cierre' => 'required|date_format:H:i|after:hora_apertura',
        ]);

        // Desactivar cualquier ventana del mismo día antes de crear la nueva
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
            abort(403);
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
}
