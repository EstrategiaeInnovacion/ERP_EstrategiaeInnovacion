<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\SolicitudPermiso;
use App\Models\User;
use App\Notifications\SolicitudPermisoNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SolicitudPermisoController extends Controller
{
    /**
     * Guarda la solicitud de permiso (Ausencia Corta, Legal o Especial)
     */
    public function store(Request $request)
    {
        $request->validate([
            'tipo_permiso' => 'required|in:corto,legal,especial',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
            'reposicion_tipo' => 'nullable|string',
            'motivo_detalle' => 'nullable|string',
            'comprobante' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        // Validación Retroactiva (48 Horas de gracia)
        $fechaSolicitud = Carbon::parse($request->fecha_inicio)->startOfDay();
        $hoy = Carbon::now()->startOfDay();
        
        if ($fechaSolicitud->lt($hoy)) {
            $horasPasadas = $fechaSolicitud->diffInHours(Carbon::now());
            if ($horasPasadas > 48) {
                return back()->with('error', 'No puedes solicitar permisos retroactivos mayores a 48 horas. Contacta a Recursos Humanos.');
            }
        }

        // La validación de comprobante ahora es opcional al crear, se verificará al intentar cerrarlo o aprobarlo definitivamente.

        $empleado = Auth::user()->empleado;
        if (!$empleado) {
            return back()->with('error', 'No tienes un perfil de empleado asignado.');
        }

        // Subir comprobante si existe
        $comprobantePath = null;
        if ($request->hasFile('comprobante')) {
            $file = $request->file('comprobante');
            $filename = time() . '_' . $empleado->id . '.' . $file->getClientOriginalExtension();
            $comprobantePath = $file->storeAs('permisos_comprobantes', $filename, 'public');
        }

        $permiso = SolicitudPermiso::create([
            'empleado_id' => $empleado->id,
            'tipo_permiso' => $request->tipo_permiso,
            'motivo_detalle' => $request->motivo_detalle,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin ?? $request->fecha_inicio,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'reposicion_tipo' => $request->reposicion_tipo,
            'comprobante_path' => $comprobantePath,
            'estado' => 'pendiente',
            'supervisor_id' => $empleado->supervisor_id
        ]);

        // Notificar al supervisor
        if ($empleado->supervisor && $empleado->supervisor->user) {
            $empleado->supervisor->user->notify(new SolicitudPermisoNotification($permiso, 'para_supervisor'));
        } else {
            // Si no tiene supervisor, pasa a RRHH directamente
            $permiso->update(['estado' => 'aprobado_supervisor', 'aprobado_supervisor_at' => now()]);
            $this->notificarARh($permiso);
        }

        return back()->with('success', 'Solicitud de permiso enviada correctamente.');
    }

    /**
     * Aprueba la solicitud (Supervisor o RH)
     */
    public function aprobar(Request $request, $id)
    {
        $permiso = SolicitudPermiso::findOrFail($id);
        $user = Auth::user();

        // Si es el supervisor
        if ($permiso->estado == 'pendiente' && $user->empleado && $permiso->supervisor_id == $user->empleado->id) {
            $permiso->update([
                'estado' => 'aprobado_supervisor',
                'aprobado_supervisor_at' => now(),
                'comentarios_supervisor' => $request->comentarios
            ]);
            $this->notificarARh($permiso);
            return back()->with('success', 'Permiso autorizado. Pasó a revisión de Recursos Humanos.');
        }

        // Si es RH
        if ($permiso->estado == 'aprobado_supervisor' && $user->isRh()) {
            $permiso->update([
                'estado' => 'aprobado',
                'rh_aprobador_id' => $user->id,
                'aprobado_rh_at' => now(),
                'comentarios_rh' => $request->comentarios
            ]);
            
            // Notificar al empleado
            if ($permiso->empleado->user) {
                $permiso->empleado->user->notify(new SolicitudPermisoNotification($permiso, 'aprobado_final'));
            }
            return back()->with('success', 'Permiso aprobado definitivamente.');
        }

        return back()->with('error', 'No tienes permisos para aprobar esta solicitud.');
    }

    /**
     * Rechaza la solicitud (Supervisor o RH)
     */
    public function rechazar(Request $request, $id)
    {
        $request->validate(['comentarios' => 'required|string']);
        $permiso = SolicitudPermiso::findOrFail($id);
        $user = Auth::user();

        // Si es el supervisor
        if ($permiso->estado == 'pendiente' && $user->empleado && $permiso->supervisor_id == $user->empleado->id) {
            $permiso->update([
                'estado' => 'rechazado',
                'aprobado_supervisor_at' => now(),
                'comentarios_supervisor' => $request->comentarios
            ]);
        } 
        // Si es RH
        elseif ($permiso->estado == 'aprobado_supervisor' && $user->isRh()) {
            $permiso->update([
                'estado' => 'rechazado',
                'rh_aprobador_id' => $user->id,
                'aprobado_rh_at' => now(),
                'comentarios_rh' => $request->comentarios
            ]);
        } else {
            return back()->with('error', 'No tienes permisos para rechazar esta solicitud.');
        }

        // Notificar al empleado del rechazo
        if ($permiso->empleado->user) {
            $permiso->empleado->user->notify(new SolicitudPermisoNotification($permiso, 'rechazado'));
        }

        return back()->with('success', 'Solicitud rechazada y notificada al colaborador.');
    }

    private function notificarARh($permiso)
    {
        // Buscar usuarios que tengan rol admin o rh
        $usuariosRh = User::whereIn('role', ['admin', 'rh'])->get();

        foreach ($usuariosRh as $rh) {
            $rh->notify(new SolicitudPermisoNotification($permiso, 'para_rh'));
        }
    }

    /**
     * Subir comprobante desfasado (por el empleado o por RH)
     */
    public function subirComprobante(Request $request, $id)
    {
        $request->validate([
            'comprobante' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $permiso = SolicitudPermiso::findOrFail($id);
        $user = Auth::user();

        // Solo el dueño del permiso, su supervisor, un admin, o RH pueden subirlo
        if (
            $permiso->empleado_id != $user->empleado?->id && 
            !$user->isRh() && 
            !$user->isAdmin() && 
            ($user->empleado && $permiso->supervisor_id != $user->empleado->id)
        ) {
            abort(403, 'No tienes permiso para modificar esta solicitud.');
        }

        if ($request->hasFile('comprobante')) {
            // Borrar el anterior si existiera por alguna razón
            if ($permiso->comprobante_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($permiso->comprobante_path);
            }

            $file = $request->file('comprobante');
            $filename = time() . '_' . $permiso->empleado_id . '_retrasado.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('permisos_comprobantes', $filename, 'public');

            $permiso->update([
                'comprobante_path' => $path
            ]);

            return back()->with('success', 'Comprobante oficial subido correctamente. La solicitud ya puede continuar su flujo.');
        }

        return back()->with('error', 'Error al procesar el archivo.');
    }
}
