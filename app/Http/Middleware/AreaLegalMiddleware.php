<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AreaLegalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $area     = $user?->empleado?->area;
        $posicion = $user?->empleado?->posicion;

        $areaNorm = $area     ? mb_strtolower(preg_replace('/\s+/u', ' ', trim($area)),     'UTF-8') : null;
        $posNorm  = $posicion ? mb_strtolower(preg_replace('/\s+/u', ' ', trim($posicion)), 'UTF-8') : null;

        $esLegal = ($areaNorm && (str_contains($areaNorm, 'legal') || str_contains($areaNorm, 'juridico')))
                || ($posNorm  && (str_contains($posNorm,  'legal') || str_contains($posNorm,  'juridico')));

        if (!$user || !$esLegal) {
            return redirect()->route('welcome')
                ->with('error', 'Acceso restringido al área Legal.');
        }

        return $next($request);
    }
}
