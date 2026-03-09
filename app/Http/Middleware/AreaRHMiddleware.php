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
        
        // Verificar área Y posición
        $area = $user?->empleado?->area;
        $posicion = $user?->empleado?->posicion;
        
        $areaNorm = $area ? mb_strtolower(preg_replace('/\s+/u',' ',$area),'UTF-8') : null;
        $posNorm = $posicion ? mb_strtolower(preg_replace('/\s+/u',' ',$posicion),'UTF-8') : null;
        
        // Permitir si el área es RH o Recursos Humanos, o si la posición contiene "administracion rh"
        $esRH = ($areaNorm === 'rh' || $areaNorm === 'recursos humanos') || 
                ($posNorm && str_contains($posNorm, 'administracion rh'));
        
        if (!$user || !$esRH) {
            return redirect()->route('rh.evaluacion.index')
                ->with('info', 'Acceso restringido únicamente a personal de Recursos Humanos.');
        }
        
        return $next($request);
    }
}