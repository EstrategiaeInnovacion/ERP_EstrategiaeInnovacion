<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AreaAuditoriaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $area     = $user?->empleado?->area     ?? '';
        $posicion = $user?->empleado?->posicion ?? '';

        $areaNorm = mb_strtolower(preg_replace('/\s+/u', ' ', $area),     'UTF-8');
        $posNorm  = mb_strtolower(preg_replace('/\s+/u', ' ', $posicion), 'UTF-8');

        $esAuditoria = str_contains($areaNorm, 'auditoria') || str_contains($posNorm, 'auditoria')
                    || str_contains($areaNorm, 'auditor')   || str_contains($posNorm, 'auditor')
                    || str_contains($posNorm, 'direccion')  || str_contains($posNorm, 'dirección')
                    || str_contains($areaNorm, 'direccion') || str_contains($areaNorm, 'dirección');

        if (!$esAuditoria) {
            return redirect()->route('welcome')->with('error', 'Acceso restringido a Auditoría.');
        }

        return $next($request);
    }
}
