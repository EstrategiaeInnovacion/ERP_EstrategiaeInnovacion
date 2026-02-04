<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityHistory;
use App\Models\User;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; 

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

        // LISTA DE USUARIOS PARA ASIGNAR (Todos)
        $empleadosAsignables = User::whereHas('empleado', function($q) {
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
            $esPuestoPlanificador = Str::contains($posicionLower, ['anexo 24', 'anexo24', 'post-operacion', 'post operacion']);
            $esHorarioPermitido = now()->isMonday() && now()->hour >= 9 && now()->hour < 11;

            if ($esPuestoPlanificador && $esHorarioPermitido) $puedePlanificar = true;
            if (str_contains($posicionLower, 'direcc')) $esDireccion = true;

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

        // 4. FECHAS Y CONSULTAS
        $viewDate = $request->has('week_view') ? Carbon::parse($request->week_view) : now();
        $startOfWeek = $viewDate->copy()->startOfWeek();
        $endOfWeek = $viewDate->copy()->endOfWeek();
        $isHistoryView = $endOfWeek->lt(now()->startOfWeek());
        $verTodo = $request->has('ver_historial') && $request->ver_historial == '1';

        // --- CORRECCIÓN 1: LÓGICA DE VISUALIZACIÓN DE FECHAS ---
        // Aparece si se asignó esta semana O si vence esta semana
        $query = Activity::with(['user.empleado.supervisor', 'historial.user'])
            ->where('user_id', $targetUserId) 
            ->where(function($q) use ($startOfWeek, $endOfWeek) {
                $q->whereBetween('fecha_compromiso', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
                  ->orWhereBetween('fecha_inicio', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')]);
            });

        if (!$verTodo && !$isHistoryView) $query->whereNotIn('estatus', ['Completado', 'Rechazado']);
        
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nombre_actividad', 'like', "%{$request->search}%")
                  ->orWhere('cliente', 'like', "%{$request->search}%")
                  ->orWhere('area', 'like', "%{$request->search}%");
            });
        }

        $weeklyActivities = $query
            ->orderByRaw("CASE estatus WHEN 'Completado' THEN 2 ELSE 1 END")
            ->orderByRaw("CASE prioridad WHEN 'Alta' THEN 1 WHEN 'Media' THEN 2 ELSE 4 END")
            ->orderBy('fecha_compromiso')
            ->orderBy('hora_inicio_programada')
            ->get();

        $pendingActivities = collect();
        if (!$isHistoryView) { 
            $pendingActivities = Activity::with(['user.empleado', 'historial.user'])
                ->where('user_id', $targetUserId)
                ->where('estatus', 'Por Aprobar')
                ->get();
        }

        $mainActivities = $weeklyActivities->merge($pendingActivities)->unique('id');

        // 5. VARIABLES DE EQUIPO Y ALERTAS ESPECÍFICAS
        $teamUsers = collect();
        if ($esDireccion) {
            $teamUsers = User::orderBy('name')->get();
        } elseif ($esSupervisor) {
            $teamUsers = User::whereIn('id', $idsVisibles)->orderBy('name')->get();
        }

        // --- CORRECCIÓN 2: OBTENER IDS EXACTOS DE QUIÉN TIENE PENDIENTES ---
        $globalPendingCount = 0;
        $usersWithPending = []; // Array para guardar IDs de usuarios con pendientes

        if ($esSupervisor || $esDireccion) {
            // Base query para buscar pendientes bajo mi supervisión
            $alertQuery = Activity::where('estatus', 'Por Aprobar');
            
            if (!$esDireccion) {
                // Si soy supervisor, solo busco en mis ids visibles
                $alertQuery->whereIn('user_id', $idsVisibles);
            }
            // Excluir mis propias tareas de la alerta global (opcional, pero recomendado)
            // $alertQuery->where('user_id', '!=', $user->id);

            // Contamos totales
            $globalPendingCount = $alertQuery->count();
            
            // Obtenemos la lista de IDs únicos que tienen pendientes
            $usersWithPending = $alertQuery->pluck('user_id')->unique()->toArray();
        }

        $misRechazos = Activity::where('user_id', $user->id)->where('estatus', 'Rechazado')->get();

        $kpis = [
            'total' => $weeklyActivities->count(),
            'completadas' => $weeklyActivities->where('estatus', 'Completado')->count(),
            'proceso' => $weeklyActivities->where('estatus', 'En proceso')->count(),
            'pendientes' => $weeklyActivities->where('estatus', 'En blanco')->count(),
            'retardos' => $weeklyActivities->where('estatus', 'Retardo')->count(),
        ];

        return view('activities.index', compact(
            'mainActivities', 'teamUsers', 'targetUser', 'kpis', 
            'esDireccion', 'esSupervisor', 
            'puedePlanificar', 'esPuestoPlanificador', 'esHorarioPermitido',
            'globalPendingCount', 'misRechazos', 
            'startOfWeek', 'endOfWeek', 'isHistoryView', 'verTodo',
            'areasSistema', 'empleadosAsignables', 
            'usersWithPending' // <--- PASAMOS LA VARIABLE NUEVA A LA VISTA
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_actividad' => 'required|max:255',
            'fecha_compromiso' => 'required|date',
            'area'             => 'required|string'
        ]);

        $data = $request->all();
        $currentUser = Auth::user();

        // Determinar destinatario
        $targetUserId = $request->filled('assigned_to') ? $request->assigned_to : $currentUser->id;
        $data['user_id'] = $targetUserId;
        $data['fecha_inicio'] = now(); 
        $data['metrico'] = 1;

        // --- REGLA DE JERARQUÍA (HIERARCHY RULE) ---
        if ($targetUserId == $currentUser->id) {
            // 1. Auto-asignación: Se crea como borrador
            $data['estatus'] = 'En blanco';
        } else {
            // 2. Asignación a terceros (Requiere chequeo de rango)
            
            // Check si soy Dirección
            $soyDireccion = $currentUser->empleado && Str::contains(strtolower($currentUser->empleado->posicion), 'direcc');
            
            // Check si soy el Supervisor Directo del destinatario
            $targetUser = User::with('empleado')->find($targetUserId);
            $soySuJefe = false;
            if ($targetUser && $targetUser->empleado && $currentUser->empleado) {
                if ($targetUser->empleado->supervisor_id === $currentUser->empleado->id) {
                    $soySuJefe = true;
                }
            }

            if ($soyDireccion || $soySuJefe) {
                // JEFE A SUBORDINADO: Pasa directo (Autoridad)
                $data['estatus'] = 'Planeado'; 
            } else {
                // PAR A PAR (o SUBORDINADO A JEFE): Requiere Aprobación (Pimponeo Controlado)
                // Esto generará una alerta en el dashboard del supervisor del destinatario
                $data['estatus'] = 'Por Aprobar'; 
            }
        }

        Activity::create($data);

        $msg = ($data['estatus'] == 'Por Aprobar') 
            ? 'Tarea enviada a validación del supervisor.' 
            : 'Actividad asignada correctamente.';

        return redirect()->back()->with('success', $msg);
    }

    public function storeBatch(Request $request)
    {
        if (! (now()->isMonday() && now()->hour >= 9 && now()->hour < 11) ) {
            return redirect()->back()->with('error', 'El periodo de planificación semanal ha cerrado.');
        }

        $request->validate(['semana_inicio' => 'required|date', 'plan' => 'array']);
        
        return DB::transaction(function () use ($request) {
            $fechaBase = Carbon::parse($request->semana_inicio);
            $count = 0;
            
            if (empty($request->plan)) return redirect()->back()->with('warning', 'Sin datos.');

            foreach ($request->plan as $diaIndex => $tareasDelDia) {
                $fechaReal = $fechaBase->copy()->addDays($diaIndex);
                if (!is_array($tareasDelDia)) continue;

                foreach ($tareasDelDia as $tarea) {
                    $nombre = trim($tarea['actividad'] ?? '');
                    if (empty($nombre)) continue;

                    // El batch es auto-planificación, va a 'Por Aprobar' para que el jefe revise la semana entera
                    Activity::create([
                        'user_id'          => Auth::id(), 
                        'area'             => $tarea['area'] ?? 'General',
                        'cliente'          => $tarea['cliente'] ?? null,
                        'tipo_actividad'   => $tarea['tipo'] ?? 'Operativo',
                        'nombre_actividad' => $nombre,
                        'hora_inicio_programada' => $tarea['start_time'] ?? null,
                        'hora_fin_programada'    => $tarea['end_time'] ?? null,
                        'fecha_inicio'     => now(),
                        'fecha_compromiso' => $fechaReal,
                        'prioridad'        => 'Media',
                        'estatus'          => 'Por Aprobar',
                        'metrico'          => 1,
                    ]);
                    $count++;
                }
            }
            return redirect()->route('activities.index')->with('success', "Plan enviado: {$count} actividades.");
        });
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
        $esDuenoBorrador = ($activity->user_id === $user->id && $activity->estatus === 'En blanco');

        $puedeEditarTodo = $esDireccion || $esSupervisor || $esDuenoBorrador;

        $original = $activity->toArray(); 

        if ($puedeEditarTodo) {
            $activity->fill($request->except(['evidencia']));
        } else {
            $activity->estatus = $request->estatus;
            $activity->comentarios = $request->comentarios;
        }

        $mapaCampos = [
            'nombre_actividad' => 'Actividad', 
            'estatus' => 'Estatus', 
            'prioridad' => 'Prioridad',
            'fecha_compromiso' => 'Fecha Compromiso', 
            'hora_inicio_programada' => 'Hora Inicio', 
            'hora_fin_programada' => 'Hora Fin', 
            'comentarios' => 'Comentarios',
            'cliente' => 'Cliente',
            'area' => 'Área'
        ];

        foreach ($activity->getDirty() as $campo => $nuevoValor) {
            if (!array_key_exists($campo, $mapaCampos)) continue;
            
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

            if ($valorAnterior == $nuevoValor) continue;

            $mensaje = ($campo === 'comentarios') 
                ? "Actualizó comentarios / bitácora" 
                : "Cambió $nombreLegible: '$valorAnterior' ➝ '$nuevoValor'";

            ActivityHistory::create([
                'activity_id' => $activity->id, 
                'user_id' => Auth::id(),
                'action' => 'updated', 
                'details' => $mensaje
            ]);
        }

        if ($activity->estatus == 'Completado' && $original['estatus'] != 'Completado') {
            $activity->fecha_final = now();
        }
        if ($original['estatus'] == 'Completado' && $activity->estatus != 'Completado') {
            $activity->fecha_final = null;
            $activity->resultado_dias = null;
            $activity->porcentaje = null;
        }
        
        if ($activity->estatus === 'Rechazado' && $original['estatus'] === 'Rechazado') {
            // Lógica opcional para rechazos repetidos
        }

        if ($request->hasFile('evidencia')) {
            if ($activity->evidencia_path) Storage::disk('public')->delete($activity->evidencia_path);
            $activity->evidencia_path = $request->file('evidencia')->store('evidencias', 'public');
            ActivityHistory::create([
                'activity_id' => $activity->id, 'user_id' => Auth::id(), 
                'action' => 'file', 'details' => 'Adjuntó evidencia'
            ]);
        }

        $activity->save();
        return redirect()->back()->with('success', 'Actualizado.');
    }

    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);
        $user = Auth::user();
        
        $esDireccion = $user->empleado && str_contains(strtolower($user->empleado->posicion), 'direcc');
        $esSupervisor = $user->empleado && $activity->user->empleado && $user->empleado->id === $activity->user->empleado->supervisor_id;
        $esDuenoBorrador = ($activity->user_id === $user->id && $activity->estatus === 'En blanco');

        if ($esDireccion || $esSupervisor || $esDuenoBorrador) {
            $activity->delete();
            return redirect()->back()->with('success', 'Eliminado.');
        }
        abort(403, 'No tienes permiso para eliminar esta actividad.');
    }

    public function approve(Request $request, $id)
    {
        $act = Activity::findOrFail($id);
        $act->estatus = 'Planeado'; 
        $act->motivo_rechazo = null;
        $act->save();
        ActivityHistory::create(['activity_id'=>$id, 'user_id'=>Auth::id(), 'action'=>'approved', 'details'=>'Aprobó la actividad']);
        return back()->with('success', 'Aprobada.');
    }

    public function reject(Request $request, $id)
    {
        $act = Activity::findOrFail($id);
        $act->estatus = 'Rechazado';
        $act->motivo_rechazo = $request->input('motivo', 'Revisión');
        $act->save();
        ActivityHistory::create(['activity_id'=>$id, 'user_id'=>Auth::id(), 'action'=>'rejected', 'details'=>'Rechazó: '.$request->motivo]);
        return back()->with('warning', 'Rechazada.');
    }

    public function start($id)
    {
        $act = Activity::findOrFail($id);
        $act->estatus = 'En proceso';
        $act->fecha_inicio = now(); 
        $act->save();
        ActivityHistory::create(['activity_id'=>$id, 'user_id'=>Auth::id(), 'action'=>'updated', 'details'=>'Inició ejecución']);
        return back()->with('success', 'Iniciada.');
    }
}