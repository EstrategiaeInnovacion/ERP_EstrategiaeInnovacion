<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SistemasAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Verificar si el usuario es admin y tiene área Sistemas o posición TI/IT
        if (!$user ||
        $user->role !== 'admin' ||
        !(optional($user->empleado)->area === 'Sistemas' ||
        optional($user->empleado)->posicion === 'TI' ||
        optional($user->empleado)->posicion === 'IT')
        ) {
            return redirect()->route('tickets.mis-tickets')
                ->with('error', 'No tienes acceso al panel administrativo de Sistemas.');
        }

        return $next($request);
    }
}
