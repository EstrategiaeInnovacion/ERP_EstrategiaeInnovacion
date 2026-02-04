<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // 1. Seguridad: Verificar usuario
        if (!$user) {
            return redirect()->route('login');
        }

        // 2. Normalizar el área y posición para evitar errores por mayúsculas o espacios
        $areaRaw = optional($user->empleado)->area;
        $area = $areaRaw ? mb_strtolower(preg_replace('/\s+/u', ' ', $areaRaw), 'UTF-8') : null;
        
        $posicionRaw = optional($user->empleado)->posicion;
        $posicion = $posicionRaw ? mb_strtolower(preg_replace('/\s+/u', ' ', $posicionRaw), 'UTF-8') : null;

        // --- A. REDIRECCIONES A "CUEVAS" ESPECÍFICAS ---

        // RH -> Su Dashboard (por área o posición)
        if ($area === 'rh' || $area === 'recursos humanos' || ($posicion && str_contains($posicion, 'administracion rh'))) {
            return redirect()->route('recursos-humanos.index');
        }

        // Logística -> Su Dashboard (por área o posición)
        if ($area === 'logistica' || $area === 'logística' || ($posicion && str_contains($posicion, 'logistica'))) {
            return redirect()->route('logistica.index');
        }

        // Sistemas -> Tickets o Admin
        if ($area === 'sistemas') {
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('tickets.mis-tickets');
        }

        // Comercio Exterior -> (Si consideras que ellos tienen "cueva" de tickets, déjalo, si no, quítalo para que vayan al welcome)
        if ($area === 'comercio exterior') {
            return redirect()->route('tickets.mis-tickets');
        }

        // --- B. EL RESTO DEL MUNDO (FALLBACK) ---
        // Si no cayó en ninguna de las "cuevas" anteriores (Ventas, Contabilidad, Dirección, etc.)
        // los mandamos al Portal Corporativo Nuevo.
        
        return redirect()->route('welcome');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}