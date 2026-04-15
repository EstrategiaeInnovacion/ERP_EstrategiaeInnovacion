<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProyectoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $esRh = $user->isRh();
        $miEmpleado = $user->empleado;

        $esCoordinador = false;
        if ($miEmpleado) {
            $esCoordinador = Empleado::where('supervisor_id', $miEmpleado->id)->exists();
        }

        $query = Proyecto::with(['creador', 'usuarios']);

        if (! $esRh) {
            if ($esCoordinador && $miEmpleado) {
                $subordinadosIds = Empleado::where('supervisor_id', $miEmpleado->id)->pluck('user_id')->filter()->toArray();
                $query->where(function ($q) use ($user, $subordinadosIds) {
                    $q->where('usuario_id', $user->id)
                        ->orWhereHas('usuarios', fn ($uq) => $uq->whereIn('users.id', array_merge([$user->id], $subordinadosIds)));
                });
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('usuario_id', $user->id)
                        ->orWhereHas('usuarios', fn ($uq) => $uq->where('users.id', $user->id));
                });
            }
        }

        if ($request->has('archivado')) {
            $query->where('archivado', $request->archivado === '1');
        } else {
            $query->where('archivado', false);
        }

        $proyectos = $query->orderBy('fecha_inicio', 'desc')->get();

        $proyectosConActividades = $proyectos->map(function ($p) {
            $p->total_actividades = $p->actividades()->count();
            $p->actividades_pendientes = $p->actividades()->whereNotIn('estatus', ['Completado', 'Rechazado'])->count();

            return $p;
        });

        return view('proyectos.index', compact('proyectos', 'proyectosConActividades', 'esRh', 'esCoordinador'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user->isRh()) {
            abort(403, 'No tienes permiso para crear proyectos.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'recurrencia' => 'required|in:semanal,quincenal,mensual',
        ]);

        $proyecto = Proyecto::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'usuario_id' => $user->id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'recurrencia' => $request->recurrencia,
            'notas' => $request->notas,
        ]);

        if ($request->has('usuarios') && is_array($request->usuarios)) {
            $proyecto->usuarios()->sync($request->usuarios);
        }

        return redirect()->route('proyectos.index')->with('success', 'Proyecto creado correctamente.');
    }

    public function show($id)
    {
        $proyecto = Proyecto::with(['creador', 'usuarios.empleado', 'actividades.user'])->findOrFail($id);

        $user = Auth::user();
        $esRh = $user->isRh();
        $miEmpleado = $user->empleado;

        $esCoordinador = false;
        if ($miEmpleado) {
            $esCoordinador = Empleado::where('supervisor_id', $miEmpleado->id)->exists();
        }

        $puedeVer = $esRh ||
                    $proyecto->usuario_id === $user->id ||
                    $proyecto->usuarios()->where('users.id', $user->id)->exists() ||
                    ($esCoordinador && $proyecto->usuarios()->whereIn('users.id',
                        Empleado::where('supervisor_id', $miEmpleado->id)->pluck('user_id')->toArray())->exists());

        if (! $puedeVer) {
            abort(403, 'No tienes acceso a este proyecto.');
        }

        $siguienteJunta = $proyecto->siguienteFechaJunta();

        return view('proyectos.show', compact('proyecto', 'siguienteJunta', 'esRh'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user->isRh()) {
            abort(403, 'No tienes permiso para editar proyectos.');
        }

        $proyecto = Proyecto::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'recurrencia' => 'required|in:semanal,quincenal,mensual',
        ]);

        $proyecto->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'recurrencia' => $request->recurrencia,
            'notas' => $request->notas,
        ]);

        if ($request->has('usuarios')) {
            $proyecto->usuarios()->sync($request->usuarios);
        }

        return redirect()->back()->with('success', 'Proyecto actualizado.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (! $user->isRh()) {
            abort(403, 'No tienes permiso para archivar proyectos.');
        }

        $proyecto = Proyecto::findOrFail($id);
        $proyecto->archivado = true;
        $proyecto->save();

        return redirect()->route('proyectos.index')->with('success', 'Proyecto archivado.');
    }

    public function restore($id)
    {
        $user = Auth::user();
        if (! $user->isRh()) {
            abort(403, 'No tienes permiso para restaurar proyectos.');
        }

        $proyecto = Proyecto::findOrFail($id);
        $proyecto->archivado = false;
        $proyecto->save();

        return redirect()->route('proyectos.index', ['archivado' => '0'])->with('success', 'Proyecto restaurado.');
    }

    public function asignarUsuarios(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user->isRh()) {
            abort(403, 'No tienes permiso para asignar usuarios.');
        }

        $proyecto = Proyecto::findOrFail($id);

        $request->validate([
            'usuarios' => 'required|array',
            'usuarios.*' => 'exists:users,id',
        ]);

        $proyecto->usuarios()->sync($request->usuarios);

        return redirect()->back()->with('success', 'Usuarios asignados correctamente.');
    }

    public function quitarUsuario(Request $request, $id, $userId)
    {
        $user = Auth::user();
        if (! $user->isRh()) {
            abort(403, 'No puedes quitar usuarios.');
        }

        $proyecto = Proyecto::findOrFail($id);
        $proyecto->usuarios()->detach($userId);

        return redirect()->back()->with('success', 'Usuario removido del proyecto.');
    }

    public function listaUsuarios()
    {
        $user = Auth::user();
        if (! $user->isRh()) {
            abort(403, 'No tienes permiso.');
        }

        $usuarios = User::whereHas('empleado', fn ($q) => $q->where('es_activo', true))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json(['usuarios' => $usuarios]);
    }

    public function edit($id)
    {
        $user = Auth::user();
        if (! $user->isRh()) {
            abort(403, 'No tienes permiso.');
        }

        $proyecto = Proyecto::findOrFail($id);
        $usuariosAsignados = $proyecto->usuarios()->pluck('users.id')->toArray();

        $usuarios = User::whereHas('empleado', fn ($q) => $q->where('es_activo', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        $form = view('proyectos.partials.edit_form', compact('proyecto', 'usuarios', 'usuariosAsignados'))->render();

        return response()->json(['form' => $form]);
    }

    public function actividades($proyectoId)
    {
        $proyecto = Proyecto::findOrFail($proyectoId);

        $user = Auth::user();
        $esRh = $user->isRh();

        $puedeVer = $esRh ||
                    $proyecto->usuario_id === $user->id ||
                    $proyecto->usuarios()->where('users.id', $user->id)->exists();

        if (! $puedeVer) {
            abort(403, 'No tienes acceso a este proyecto.');
        }

        $actividades = $proyecto->actividades()
            ->with('user')
            ->orderBy('fecha_compromiso')
            ->get();

        $usuariosAsignables = $proyecto->usuarios()->orderBy('name')->get();

        if ($usuariosAsignables->isEmpty()) {
            $usuariosAsignables = User::whereHas('empleado', fn ($q) => $q->where('es_activo', true))->orderBy('name')->get();
        }

        $areas = \App\Models\Empleado::where('es_activo', true)
            ->whereNotNull('posicion')->where('posicion', '!=', '')
            ->distinct()->orderBy('posicion')->pluck('posicion');

        if ($areas->isEmpty()) {
            $areas = collect(['General', 'Operativo', 'Administrativo']);
        }

        $kpis = [
            'total' => $actividades->count(),
            'completadas' => $actividades->where('estatus', 'Completado')->count(),
            'enProceso' => $actividades->whereIn('estatus', ['En proceso', 'Planeado'])->count(),
            'pendientes' => $actividades->whereIn('estatus', ['Por Aprobar', 'Por Validar'])->count(),
        ];

        return view('proyectos.actividades', compact('proyecto', 'actividades', 'usuariosAsignables', 'areas', 'kpis', 'esRh'));
    }

    public function guardarActividad(Request $request, $proyectoId)
    {
        $proyecto = Proyecto::findOrFail($proyectoId);
        $user = Auth::user();

        $esRh = $user->isRh();
        $puedeCrear = $esRh ||
                      $proyecto->usuario_id === $user->id ||
                      $proyecto->usuarios()->where('users.id', $user->id)->exists();

        if (! $puedeCrear) {
            abort(403, 'No tienes permiso.');
        }

        $request->validate([
            'nombre_actividad' => 'required|max:255',
            'fecha_compromiso' => 'required|date',
            'area' => 'required|string',
        ]);

        $asignadoA = $request->filled('asignado_a') ? $request->asignado_a : $user->id;

        \App\Models\Activity::create([
            'user_id' => $asignadoA,
            'asignado_por' => $user->id,
            'proyecto_id' => $proyecto->id,
            'nombre_actividad' => $request->nombre_actividad,
            'area' => $request->area,
            'cliente' => $request->cliente,
            'prioridad' => $request->prioridad ?? 'Media',
            'fecha_inicio' => now(),
            'fecha_compromiso' => $request->fecha_compromiso,
            'estatus' => 'Planeado',
            'metrico' => 1,
        ]);

        return redirect()->back()->with('success', 'Actividad creada en el proyecto.');
    }

    public function actualizarActividad(Request $request, $proyectoId, $actividadId)
    {
        $proyecto = Proyecto::findOrFail($proyectoId);
        $actividad = \App\Models\Activity::where('proyecto_id', $proyecto->id)->findOrFail($actividadId);

        $user = Auth::user();
        $esRh = $user->isRh();

        $puedeEditar = $esRh ||
                       $proyecto->usuario_id === $user->id ||
                       $actividad->user_id === $user->id;

        if (! $puedeEditar) {
            abort(403, 'No tienes permiso.');
        }

        $data = $request->only(['nombre_actividad', 'area', 'cliente', 'prioridad', 'fecha_compromiso', 'estatus', 'comentarios']);

        if ($request->filled('asignado_a')) {
            $data['user_id'] = $request->asignado_a;
        }

        $actividad->fill($data);

        if ($request->estatus === 'Completado' && ! $actividad->fecha_final) {
            $actividad->fecha_final = now();
        }

        $actividad->save();

        return redirect()->back()->with('success', 'Actividad actualizada.');
    }

    public function editarActividad($proyectoId, $actividadId)
    {
        $proyecto = Proyecto::findOrFail($proyectoId);
        $actividad = \App\Models\Activity::where('proyecto_id', $proyecto->id)->findOrFail($actividadId);

        $usuariosAsignables = $proyecto->usuarios()->orderBy('name')->get();
        if ($usuariosAsignables->isEmpty()) {
            $usuariosAsignables = \App\Models\User::whereHas('empleado', fn ($q) => $q->where('es_activo', true))->orderBy('name')->get();
        }

        $areas = \App\Models\Empleado::where('es_activo', true)
            ->whereNotNull('posicion')->where('posicion', '!=', '')
            ->distinct()->orderBy('posicion')->pluck('posicion');
        if ($areas->isEmpty()) {
            $areas = collect(['General', 'Operativo', 'Administrativo']);
        }

        $form = view('proyectos.partials.actividad_form', compact('actividad', 'usuariosAsignables', 'areas'))->render();

        return response()->json(['form' => $form]);
    }

    public function eliminarActividad($proyectoId, $actividadId)
    {
        $proyecto = Proyecto::findOrFail($proyectoId);
        $actividad = \App\Models\Activity::where('proyecto_id', $proyecto->id)->findOrFail($actividadId);

        $user = Auth::user();
        $esRh = $user->isRh();

        if (! $esRh && $proyecto->usuario_id !== $user->id) {
            abort(403, 'No tienes permiso para eliminar.');
        }

        $actividad->delete();

        return redirect()->back()->with('success', 'Actividad eliminada.');
    }

    public function finalizar(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user->isRh()) {
            abort(403, 'No tienes permiso para finalizar proyectos.');
        }

        $proyecto = Proyecto::findOrFail($id);

        $request->validate([
            'fecha_fin_real' => 'required|date',
        ]);

        $proyecto->update([
            'fecha_fin_real' => $request->fecha_fin_real,
            'finalizado' => true,
        ]);

        return redirect()->route('proyectos.reporte', $proyecto->id)->with('success', 'Proyecto finalizado. Generando reporte...');
    }

    public function reporte($id)
    {
        $user = Auth::user();
        $esRh = $user->isRh();

        $proyecto = Proyecto::with(['creador', 'usuarios', 'actividades.user'])->findOrFail($id);

        $puedeVer = $esRh ||
                    $proyecto->usuario_id === $user->id ||
                    $proyecto->usuarios()->where('users.id', $user->id)->exists();

        if (! $puedeVer) {
            abort(403, 'No tienes acceso a este proyecto.');
        }

        $metricas = $proyecto->metricas();
        $actividades = $proyecto->actividades()->with('user')->orderBy('fecha_compromiso')->get();

        return view('proyectos.reporte', compact('proyecto', 'metricas', 'actividades', 'esRh'));
    }
}
