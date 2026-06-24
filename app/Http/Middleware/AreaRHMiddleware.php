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
        
        $area = $user?->empleado?->area;
        $posicion = $user?->empleado?->posicion;
        
        $areaNorm = $area ? mb_strtolower(preg_replace('/\s+/u', ' ', trim($area)), 'UTF-8') : null;
        $posNorm = $posicion ? mb_strtolower(preg_replace('/\s+/u', ' ', trim($posicion)), 'UTF-8') : null;
        
        $esRH = ($posNorm && (str_contains($posNorm, 'administracion rh') || str_contains($posNorm, 'administración rh') || str_contains($posNorm, 'direccion') || str_contains($posNorm, 'dirección') || $posNorm === 'ti' || $posNorm === 'it')) ||
                ($areaNorm && (str_contains($areaNorm, 'recursos humanos') || str_contains($areaNorm, 'administracion rh') || str_contains($areaNorm, 'administración rh') || str_contains($areaNorm, 'direccion') || str_contains($areaNorm, 'dirección')));
        
        if (!$esRH) {
            return redirect()->route('welcome')
                ->with('error', 'Acceso restringido a personal autorizado de Recursos Humanos.');
        }
        
        return $next($request);
    }
}