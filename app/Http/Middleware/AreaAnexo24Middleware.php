<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AreaAnexo24Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $area     = $user?->empleado?->area     ?? '';
        $posicion = $user?->empleado?->posicion ?? '';

        $areaNorm = mb_strtolower(preg_replace('/\s+/u', ' ', $area),     'UTF-8');
        $posNorm  = mb_strtolower(preg_replace('/\s+/u', ' ', $posicion), 'UTF-8');

        $esAnexo24 = str_contains($areaNorm, 'anexo') || str_contains($posNorm, 'anexo')
                  || str_contains($areaNorm, 'a24')   || str_contains($posNorm, 'a24')
                  || str_contains($posNorm, 'direccion') || str_contains($posNorm, 'dirección')
                  || str_contains($areaNorm, 'direccion') || str_contains($areaNorm, 'dirección');

        if (!$esAnexo24) {
            return redirect()->route('welcome')->with('error', 'Acceso restringido a Anexo 24.');
        }

        return $next($request);
    }
}
