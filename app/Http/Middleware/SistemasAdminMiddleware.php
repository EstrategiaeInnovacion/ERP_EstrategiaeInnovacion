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
        $empleado = $user?->empleado;
        $areaNorm = $empleado ? mb_strtolower(preg_replace('/\s+/u', ' ', trim($empleado->area ?? '')), 'UTF-8') : '';
        $posNorm = $empleado ? mb_strtolower(preg_replace('/\s+/u', ' ', trim($empleado->posicion ?? '')), 'UTF-8') : '';

        if ($user->role !== 'admin' ||
            !(str_contains($areaNorm, 'sistemas') || $posNorm === 'ti' || $posNorm === 'it')
        ) {
            return redirect()->route('tickets.mis-tickets')
                ->with('error', 'No tienes acceso al panel administrativo de Sistemas.');
        }

        return $next($request);
    }
}
