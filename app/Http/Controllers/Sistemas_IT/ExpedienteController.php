<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\Sistemas_IT\EquipoAsignado;
use App\Models\Sistemas_IT\Expediente;
use App\Models\Sistemas_IT\Mantenimiento;
use App\Models\Sistemas_IT\MantenimientoArchivo;
use App\Models\Sistemas_IT\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpedienteController extends Controller
{
    // ── Expedientes ──────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Expediente::with(['equipoAsignado.user.empleado'])
            ->orderByDesc('updated_at');

        if ($search = $request->input('q')) {
            $query->whereHas('equipoAsignado', function ($q) use ($search) {
                $q->where('nombre_equipo', 'like', "%{$search}%")
                  ->orWhere('modelo', 'like', "%{$search}%")
                  ->orWhere('numero_serie', 'like', "%{$search}%");
            });
        }

        if ($search) {
            $query->orWhereHas('equipoAsignado.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }

        $expedientes = $query->paginate(20)->withQueryString();

        return view('Sistemas_IT.admin.expedientes.index', compact('expedientes'));
    }

    public function show(Expediente $expediente)
    {
        $expediente->load([
            'equipoAsignado.user.empleado',
            'mantenimientos.tecnico',
            'mantenimientos.archivos',
            'mantenimientos.ticket',
            'creador',
        ]);

        $tecnicos = self::queryTecnicos();

        $ticketsPendientes = Ticket::where('equipo_asignado_id', $expediente->equipo_asignado_id)
            ->where('estado', '!=', 'cerrado')
            ->orderByDesc('created_at')
            ->get(['id', 'folio', 'created_at', 'estado']);

        return view('Sistemas_IT.admin.expedientes.show', compact(
            'expediente', 'tecnicos', 'ticketsPendientes'
        ));
    }

    public function cerrar(Request $request, Expediente $expediente)
    {
        $request->validate([
            'estado'        => 'required|in:retirado,renovado',
            'motivo_cierre' => 'required|string|max:500',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $expediente->update([
            'estado'        => $request->estado,
            'fecha_cierre'  => now()->toDateString(),
            'motivo_cierre' => $request->motivo_cierre,
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('admin.expedientes.show', $expediente)
            ->with('success', 'Expediente cerrado correctamente.');
    }

    public function reactivar(Expediente $expediente)
    {
        $expediente->update([
            'estado'        => 'activo',
            'fecha_cierre'  => null,
            'motivo_cierre' => null,
        ]);

        return redirect()->route('admin.expedientes.show', $expediente)
            ->with('success', 'Expediente reactivado.');
    }

    public function destroy(Expediente $expediente)
    {
        $expediente->load('mantenimientos.archivos');

        DB::transaction(function () use ($expediente) {
            foreach ($expediente->mantenimientos as $mantenimiento) {
                foreach ($mantenimiento->archivos as $archivo) {
                    Storage::disk('public')->delete($archivo->ruta);
                    $archivo->delete();
                }
                $mantenimiento->delete();
            }
            $expediente->delete();
        });

        return redirect()->route('admin.expedientes.index')
            ->with('success', 'Expediente eliminado correctamente.');
    }

    public function crearMantenimientoDesdeTicket(Ticket $ticket)
    {
        if (!$ticket->equipo_asignado_id) {
            return redirect()->back()
                ->with('error', 'Este ticket no tiene un equipo asignado. Asigna un equipo primero.');
        }

        $expediente = Expediente::firstOrCreate(
            ['equipo_asignado_id' => $ticket->equipo_asignado_id],
            ['estado' => 'activo', 'fecha_apertura' => now()->toDateString(), 'created_by' => Auth::id()]
        );

        return redirect()->route('admin.expedientes.mantenimiento.create', $expediente)
            ->withInput(['ticket_id' => $ticket->id, 'tecnico_id' => Auth::id()]);
    }

    // ── Mantenimientos ───────────────────────────────────────────────────────

    public function createMantenimiento(Expediente $expediente)
    {
        $expediente->load('equipoAsignado.user');

        $tecnicos = self::queryTecnicos();

        $ticketsDisponibles = Ticket::where('equipo_asignado_id', $expediente->equipo_asignado_id)
            ->where('estado', '!=', 'cerrado')
            ->orderByDesc('created_at')
            ->get(['id', 'folio', 'created_at', 'estado']);

        $checklist = Mantenimiento::checklistTemplate('preventivo');

        return view('Sistemas_IT.admin.expedientes.mantenimiento-form', compact(
            'expediente', 'tecnicos', 'ticketsDisponibles', 'checklist'
        ));
    }

    public function storeMantenimiento(Request $request, Expediente $expediente)
    {
        $validated = $request->validate([
            'tipo'                  => 'required|in:preventivo,correctivo,emergente',
            'prioridad'             => 'required|in:baja,media,alta,critica',
            'tecnico_id'            => 'nullable|exists:users,id',
            'ticket_id'             => 'nullable|exists:tickets,id',
            'descripcion_problema'  => 'nullable|string|max:2000',
            'fecha_inicio'          => 'nullable|date',
            'fecha_fin'             => 'nullable|date|after_or_equal:fecha_inicio',
            'actividades'           => 'nullable|array',
            'actividades.*.categoria'    => 'required|string',
            'actividades.*.actividad'    => 'required|string',
            'actividades.*.estado'       => 'required|in:pendiente,completado,no_aplica',
            'actividades.*.observaciones'=> 'nullable|string',
            'hallazgos'             => 'nullable|array',
            'hallazgos.*.descripcion'    => 'required|string',
            'hallazgos.*.nivel_riesgo'   => 'required|in:bajo,medio,alto,critico',
            'hallazgos.*.recomendacion'  => 'nullable|string',
            'observaciones'         => 'nullable|string|max:2000',
            'proximo_mantenimiento' => 'nullable|date',
            'frecuencia_siguiente'  => 'nullable|in:mensual,trimestral,semestral,anual',
            'firma_tecnico'         => 'nullable|string',
            'firma_usuario'         => 'nullable|string',
            'nombre_firma_usuario'  => 'nullable|string|max:255',
        ]);

        $estado = 'pendiente';
        if (!empty($validated['fecha_inicio']) && empty($validated['fecha_fin'])) {
            $estado = 'en_proceso';
        } elseif (!empty($validated['fecha_fin'])) {
            $estado = 'completado';
        }

        $equipo  = $expediente->equipoAsignado->load('user.empleado');
        $usuario = $equipo->user;

        DB::transaction(function () use ($validated, $expediente, $estado, $usuario, $request) {
            $mantenimiento = $expediente->mantenimientos()->create([
                ...$validated,
                'estado'             => $estado,
                'usuario_al_momento' => $usuario?->name,
                'area_al_momento'    => $usuario?->empleado?->area,
                'created_by'         => Auth::id(),
            ]);

            if ($estado === 'completado') {
                $this->actualizarEquipo($expediente, $mantenimiento);
            }

            if ($expediente->estado === 'activo' && $estado === 'en_proceso') {
                $expediente->update(['estado' => 'en_reparacion']);
            } elseif ($estado === 'completado' && $expediente->estado === 'en_reparacion') {
                $expediente->update(['estado' => 'activo']);
            }

            $this->guardarArchivos($request, $mantenimiento);
        });

        return redirect()->route('admin.expedientes.show', $expediente)
            ->with('success', 'Mantenimiento registrado correctamente.');
    }

    public function showMantenimiento(Expediente $expediente, Mantenimiento $mantenimiento)
    {
        abort_if($mantenimiento->expediente_id !== $expediente->id, 404);

        $mantenimiento->load(['expediente.equipoAsignado.user', 'tecnico', 'archivos', 'ticket', 'creador']);

        return view('Sistemas_IT.admin.expedientes.mantenimiento-show', compact('expediente', 'mantenimiento'));
    }

    public function editMantenimiento(Expediente $expediente, Mantenimiento $mantenimiento)
    {
        abort_if($mantenimiento->expediente_id !== $expediente->id, 404);

        $expediente->load('equipoAsignado.user');

        $tecnicos = self::queryTecnicos();

        $ticketsDisponibles = Ticket::where('equipo_asignado_id', $expediente->equipo_asignado_id)
            ->orderByDesc('created_at')
            ->get(['id', 'folio', 'created_at', 'estado']);

        $checklist = $mantenimiento->actividades ?? Mantenimiento::checklistTemplate($mantenimiento->tipo);

        return view('Sistemas_IT.admin.expedientes.mantenimiento-form', compact(
            'expediente', 'mantenimiento', 'tecnicos', 'ticketsDisponibles', 'checklist'
        ));
    }

    public function updateMantenimiento(Request $request, Expediente $expediente, Mantenimiento $mantenimiento)
    {
        abort_if($mantenimiento->expediente_id !== $expediente->id, 404);

        $validated = $request->validate([
            'tipo'                  => 'required|in:preventivo,correctivo,emergente',
            'prioridad'             => 'required|in:baja,media,alta,critica',
            'estado'                => 'required|in:pendiente,en_proceso,completado,cancelado',
            'tecnico_id'            => 'nullable|exists:users,id',
            'ticket_id'             => 'nullable|exists:tickets,id',
            'descripcion_problema'  => 'nullable|string|max:2000',
            'fecha_inicio'          => 'nullable|date',
            'fecha_fin'             => 'nullable|date|after_or_equal:fecha_inicio',
            'actividades'           => 'nullable|array',
            'actividades.*.categoria'    => 'required|string',
            'actividades.*.actividad'    => 'required|string',
            'actividades.*.estado'       => 'required|in:pendiente,completado,no_aplica',
            'actividades.*.observaciones'=> 'nullable|string',
            'hallazgos'             => 'nullable|array',
            'hallazgos.*.descripcion'    => 'required|string',
            'hallazgos.*.nivel_riesgo'   => 'required|in:bajo,medio,alto,critico',
            'hallazgos.*.recomendacion'  => 'nullable|string',
            'observaciones'         => 'nullable|string|max:2000',
            'proximo_mantenimiento' => 'nullable|date',
            'frecuencia_siguiente'  => 'nullable|in:mensual,trimestral,semestral,anual',
            'firma_tecnico'         => 'nullable|string',
            'firma_usuario'         => 'nullable|string',
            'nombre_firma_usuario'  => 'nullable|string|max:255',
        ]);

        $yaEstabaCompletado = $mantenimiento->estado === 'completado';

        DB::transaction(function () use ($validated, $expediente, $mantenimiento, $yaEstabaCompletado, $request) {
            $mantenimiento->update($validated);

            if (!$yaEstabaCompletado && $validated['estado'] === 'completado') {
                $this->actualizarEquipo($expediente, $mantenimiento);
            }

            if ($validated['estado'] === 'completado' && $expediente->estado === 'en_reparacion') {
                $expediente->update(['estado' => 'activo']);
            }

            $this->guardarArchivos($request, $mantenimiento);
        });

        return redirect()->route('admin.expedientes.mantenimiento.show', [$expediente, $mantenimiento])
            ->with('success', 'Mantenimiento actualizado.');
    }

    public function destroyMantenimiento(Expediente $expediente, Mantenimiento $mantenimiento)
    {
        abort_if($mantenimiento->expediente_id !== $expediente->id, 404);

        $mantenimiento->delete();

        return redirect()->route('admin.expedientes.show', $expediente)
            ->with('success', 'Mantenimiento eliminado.');
    }

    // ── Archivos ─────────────────────────────────────────────────────────────

    public function uploadArchivo(Request $request, Mantenimiento $mantenimiento)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:jpeg,jpg,png,gif,pdf|max:5120',
            'momento' => 'required|in:antes,despues,documento',
        ]);

        $file = $request->file('archivo');
        $path = $file->store("mantenimientos/{$mantenimiento->id}", 'public');

        $mantenimiento->archivos()->create([
            'momento'        => $request->momento,
            'ruta'           => $path,
            'nombre_original'=> $file->getClientOriginalName(),
            'tipo_mime'      => $file->getMimeType(),
            'tamanio_bytes'  => $file->getSize(),
        ]);

        return back()->with('success', 'Archivo subido correctamente.');
    }

    public function deleteArchivo(MantenimientoArchivo $archivo)
    {
        Storage::disk('public')->delete($archivo->ruta);
        $archivo->delete();

        return back()->with('success', 'Archivo eliminado.');
    }

    // ── Helpers privados ─────────────────────────────────────────────────────

    private static function queryTecnicos()
    {
        return User::whereHas('empleado', function ($q) {
            $q->where('area', 'like', '%sistemas%')
              ->orWhere('area', 'like', '%IT%')
              ->orWhere('area', 'like', '%tecnología%')
              ->orWhere('posicion', 'like', '%técnico%')
              ->orWhere('posicion', 'like', '%soporte%');
        })->where('status', 'approved')
          ->orderBy('name')
          ->get(['id', 'name']);
    }

    private function actualizarEquipo(Expediente $expediente, Mantenimiento $mantenimiento): void
    {
        $updates = ['last_maintenance_at' => now()];

        if ($mantenimiento->proximo_mantenimiento) {
            $updates['next_maintenance_at'] = $mantenimiento->proximo_mantenimiento;
        }

        $expediente->equipoAsignado->update($updates);
    }

    private function guardarArchivos(Request $request, Mantenimiento $mantenimiento): void
    {
        foreach (['antes', 'despues', 'documento'] as $momento) {
            $campo = "archivos_{$momento}";
            if ($request->hasFile($campo)) {
                foreach ($request->file($campo) as $file) {
                    if (!$file->isValid()) continue;
                    $path = $file->store("mantenimientos/{$mantenimiento->id}", 'public');
                    $mantenimiento->archivos()->create([
                        'momento'        => $momento,
                        'ruta'           => $path,
                        'nombre_original'=> $file->getClientOriginalName(),
                        'tipo_mime'      => $file->getMimeType(),
                        'tamanio_bytes'  => $file->getSize(),
                    ]);
                }
            }
        }
    }
}
