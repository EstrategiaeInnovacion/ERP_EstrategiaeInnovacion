<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SolicitudVacacion;
use App\Models\DiaFestivo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Notifications\SolicitudVacacionNotification;

class SolicitudVacacionController extends Controller
{
    /**
     * Vista de aprobaciones pendientes para supervisores y RH.
     */
    public function indexAprobaciones()
    {
        $user = Auth::user();
        
        $solicitudesSupervisor = collect();
        $solicitudesRH = collect();

        // Si es supervisor, traer las de sus subordinados que estén pendientes
        if ($user->empleado) {
            $solicitudesSupervisor = SolicitudVacacion::where('supervisor_id', $user->empleado->id)
                ->where('estado', 'pendiente')
                ->with('empleado')
                ->latest()
                ->get();
        }

        // Si es RH, traer las que ya aprobó el supervisor, y las que están pendientes de supervisor para monitoreo
        $solicitudesGlobalesPendientes = collect();
        $historialRH = collect();
        if ($user->isRh()) {
            $solicitudesRH = SolicitudVacacion::where('estado', 'aprobado_supervisor')
                ->with('empleado', 'supervisor')
                ->latest()
                ->get();
                
            $solicitudesGlobalesPendientes = SolicitudVacacion::where('estado', 'pendiente')
                ->with('empleado', 'supervisor')
                ->latest()
                ->get();
                
            $historialRH = SolicitudVacacion::where('estado', 'aprobado_rh')
                ->with('empleado', 'supervisor')
                ->latest()
                ->take(50) // Limitar a las últimas 50 para no sobrecargar
                ->get();
        }

        return view('Recursos_Humanos.vacaciones.aprobaciones', compact('solicitudesSupervisor', 'solicitudesRH', 'solicitudesGlobalesPendientes', 'historialRH'));
    }

    /**
     * Calcula los días hábiles entre dos fechas.
     */
    public function calcularDias(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ]);

        $inicio = Carbon::parse($request->fecha_inicio);
        $fin = Carbon::parse($request->fecha_fin);
        $dias = $this->obtenerDiasHabiles($inicio, $fin);

        return response()->json(['dias' => $dias]);
    }

    private function obtenerDiasHabiles(Carbon $inicio, Carbon $fin): int
    {
        $dias = 0;
        $actual = $inicio->copy();
        while ($actual->lte($fin)) {
            if (!$actual->isWeekend() && !DiaFestivo::esDiaFestivo($actual)) {
                $dias++;
            }
            $actual->addDay();
        }
        return $dias;
    }

    /**
     * Guarda la solicitud de vacaciones.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'motivo' => 'nullable|string'
        ]);

        $empleado = Auth::user()->empleado;
        if (!$empleado) {
            return back()->with('error', 'No tienes un perfil de empleado asignado.');
        }

        $inicio = Carbon::parse($request->fecha_inicio);
        $fin = Carbon::parse($request->fecha_fin);
        $diasSolicitados = $this->obtenerDiasHabiles($inicio, $fin);

        if ($diasSolicitados <= 0) {
            return back()->with('error', 'El rango seleccionado no contiene días hábiles.');
        }

        $diasDisponibles = $empleado->obtenerVacacionesDisponibles();
        if ($diasSolicitados > $diasDisponibles) {
            return back()->with('error', 'No tienes suficientes días de vacaciones disponibles.');
        }

        $solicitud = SolicitudVacacion::create([
            'empleado_id' => $empleado->id,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'dias_solicitados' => $diasSolicitados,
            'motivo' => $request->motivo,
            'estado' => 'pendiente',
            'supervisor_id' => $empleado->supervisor_id
        ]);

        // Notificar al supervisor
        if ($empleado->supervisor && $empleado->supervisor->user) {
            $empleado->supervisor->user->notify(new SolicitudVacacionNotification($solicitud, 'para_supervisor'));
        } else {
            // Si no tiene supervisor, pasa a RRHH directamente
            $solicitud->update(['estado' => 'aprobado_supervisor', 'aprobado_supervisor_at' => now()]);
            $this->notificarARh($solicitud);
        }

        return back()->with('success', 'Solicitud de vacaciones enviada correctamente.');
    }

    /**
     * Aprueba la solicitud (Supervisor o RH)
     */
    public function aprobar(Request $request, $id)
    {
        $solicitud = SolicitudVacacion::findOrFail($id);
        $user = Auth::user();

        // Si es el supervisor
        if ($solicitud->estado == 'pendiente' && $user->empleado && $solicitud->supervisor_id == $user->empleado->id) {
            $solicitud->update([
                'estado' => 'aprobado_supervisor',
                'aprobado_supervisor_at' => now(),
                'comentarios_supervisor' => $request->comentarios
            ]);
            $this->notificarARh($solicitud);
            return back()->with('success', 'Solicitud autorizada. Pasó a revisión de Recursos Humanos.');
        }

        // Si es RH
        if ($solicitud->estado == 'aprobado_supervisor' && $user->isRh()) {
            $solicitud->update([
                'estado' => 'aprobado',
                'rh_aprobador_id' => $user->id,
                'aprobado_rh_at' => now(),
                'comentarios_rh' => $request->comentarios
            ]);
            
            // Notificar al analista
            if ($solicitud->empleado->user) {
                $solicitud->empleado->user->notify(new SolicitudVacacionNotification($solicitud, 'para_analista'));
            }
            return back()->with('success', 'Solicitud de vacaciones aprobada definitivamente.');
        }

        return back()->with('error', 'No tienes permisos para aprobar esta solicitud en su estado actual.');
    }

    /**
     * Rechaza la solicitud (Supervisor o RH)
     */
    public function rechazar(Request $request, $id)
    {
        $request->validate(['comentarios' => 'required|string']);
        $solicitud = SolicitudVacacion::findOrFail($id);
        $user = Auth::user();

        // Si es el supervisor
        if ($solicitud->estado == 'pendiente' && $user->empleado && $solicitud->supervisor_id == $user->empleado->id) {
            $solicitud->update([
                'estado' => 'rechazado',
                'aprobado_supervisor_at' => now(),
                'comentarios_supervisor' => $request->comentarios
            ]);
        } 
        // Si es RH
        elseif ($solicitud->estado == 'aprobado_supervisor' && $user->isRh()) {
            $solicitud->update([
                'estado' => 'rechazado',
                'rh_aprobador_id' => $user->id,
                'aprobado_rh_at' => now(),
                'comentarios_rh' => $request->comentarios
            ]);
        } else {
            return back()->with('error', 'No tienes permisos para rechazar esta solicitud.');
        }

        // Notificar al analista
        if ($solicitud->empleado->user) {
            $solicitud->empleado->user->notify(new SolicitudVacacionNotification($solicitud, 'para_analista'));
        }

        return back()->with('success', 'Solicitud de vacaciones rechazada.');
    }

    private function notificarARh($solicitud)
    {
        // Notificar a usuarios de RH
        $rhUsers = User::all()->filter(function ($user) {
            return $user->isRh();
        });

        foreach ($rhUsers as $rhUser) {
            $rhUser->notify(new SolicitudVacacionNotification($solicitud, 'para_rh'));
        }
    }
}
