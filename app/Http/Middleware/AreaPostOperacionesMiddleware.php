<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AreaPostOperacionesMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $area     = $user?->empleado?->area     ?? '';
        $posicion = $user?->empleado?->posicion ?? '';

        $areaNorm = mb_strtolower(preg_replace('/\s+/u', ' ', $area),     'UTF-8');
        $posNorm  = mb_strtolower(preg_replace('/\s+/u', ' ', $posicion), 'UTF-8');

        $esPostOp = str_contains($areaNorm, 'post') || str_contains($posNorm, 'post')
                 || str_contains($areaNorm, 'postoperacion') || str_contains($posNorm, 'postoperacion');

        if (!$esPostOp) {
            return redirect()->route('login')->with('info', 'Acceso restringido a Post-Operaciones.');
        }

        return $next($request);
    }
}
