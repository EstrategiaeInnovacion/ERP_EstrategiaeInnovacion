<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AreaLogisticaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Verificar área Y posición para determinar si es de logística
        $area = $user?->empleado?->area;
        $posicion = $user?->empleado?->posicion;
        
        $areaNorm = $area ? mb_strtolower(preg_replace('/\s+/u',' ',$area),'UTF-8') : null;
        $posNorm = $posicion ? mb_strtolower(preg_replace('/\s+/u',' ',$posicion),'UTF-8') : null;
        
        // Permitir si el área contiene "logistica" o si la posición contiene "logistica"
        $esLogistica = ($areaNorm && stripos($areaNorm, 'logistic') !== false) || 
                       ($posNorm && stripos($posNorm, 'logistic') !== false) ||
                       ($posNorm && (stripos($posNorm, 'direccion') !== false || stripos($posNorm, 'dirección') !== false)) ||
                       ($areaNorm && (stripos($areaNorm, 'direccion') !== false || stripos($areaNorm, 'dirección') !== false));
        
        if (!$esLogistica) {
            return redirect()->route('welcome')->with('error','Acceso restringido a Logística');
        }
        
        return $next($request);
    }
}
