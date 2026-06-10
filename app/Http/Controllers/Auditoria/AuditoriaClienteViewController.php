<?php
 
namespace App\Http\Controllers\Auditoria;
 
use App\Http\Controllers\Controller;
use App\Models\Auditoria\ProyectoAuditoria;
use App\Models\Auditoria\ActividadAuditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
 
class AuditoriaClienteViewController extends Controller
{
    // Renderizar la vista de cliente (pública)
    public function show(Request $request, $token)
    {
        $proyecto = ProyectoAuditoria::with(['cliente', 'analista', 'coordinador'])->where('token_publico', $token)->firstOrFail();
 
        // 1. Validar expiración del enlace
        if ($proyecto->publico_expira_at && $proyecto->publico_expira_at->isPast()) {
            abort(403, 'Este enlace de seguimiento de auditoría ha expirado.');
        }
 
        // 2. Validar contraseña si existe
        if (!empty($proyecto->publico_password)) {
            $sessionKey = 'auditoria_client_auth_' . $proyecto->id;
            if (!session()->has($sessionKey) || session()->get($sessionKey) !== true) {
                return view('Auditoria.proyectos.cliente-login', compact('token', 'proyecto'));
            }
        }
 
        // 3. Cargar actividades y subprocesos filtrados para el cliente
        $actividades = collect();
        if ($proyecto->mostrar_detalle_cliente) {
            $actividades = ActividadAuditoria::with([
                'subprocesos' => function ($q) {
                    $q->orderBy('orden')->with([
                        'comentariosList' => function ($q) {
                            $q->where('visible_cliente', true)->with('autor:id,name');
                        }
                    ]);
                },
                'comentariosList' => function ($q) {
                    $q->where('visible_cliente', true)->with('autor:id,name');
                }
            ])
            ->where('proyecto_id', $proyecto->id)
            ->whereNull('padre_id')
            ->orderBy('orden')
            ->get();
        }
 
        return view('Auditoria.proyectos.cliente', compact('proyecto', 'actividades'));
    }
 
    // Procesar contraseña ingresada por el cliente
    public function verifyPassword(Request $request, $token)
    {
        $proyecto = ProyectoAuditoria::where('token_publico', $token)->firstOrFail();
 
        $request->validate([
            'password' => 'required|string',
        ]);
 
        if (Hash::check($request->password, $proyecto->publico_password)) {
            session()->put('auditoria_client_auth_' . $proyecto->id, true);
            return redirect()->route('auditoria.publico.show', $token);
        }
 
        return redirect()->back()->withErrors(['password' => 'La contraseña ingresada es incorrecta.']);
    }
}
