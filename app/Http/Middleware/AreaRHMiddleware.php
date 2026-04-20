<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AreaRHMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Si es la ruta de equipo (supervisores), dejar pasar
        if ($request->routeIs('rh.reloj.equipo')) {
            return $next($request);
        }
        
        // Verificar posicion del empleado
        $posicion = $user?->empleado?->posicion;
        $posNorm = $posicion ? mb_strtolower(preg_replace('/\s+/u', ' ', trim($posicion)), 'UTF-8') : null;
        
        // Solo Direccion, Administracion RH y TI tienen acceso completo a RH
        $esRH = $posNorm && in_array($posNorm, ['direccion', 'administracion rh', 'ti']);
        
        if (!$user || !$esRH) {
            return redirect()->route('rh.evaluacion.index')
                ->with('info', 'Acceso restringido a personal autorizado de Recursos Humanos.');
        }
        
        return $next($request);
    }
}