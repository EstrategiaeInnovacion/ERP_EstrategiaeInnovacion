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
        
        // Debug logs
        \Log::info('SistemasAdminMiddleware - Verificando acceso', [
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'empleado_area' => optional($user->empleado)->area,
            'empleado_posicion' => optional($user->empleado)->posicion,
        ]);
        
        // Verificar si el usuario es admin y tiene área Sistemas o posición TI/IT
        if (!$user || 
            $user->role !== 'admin' || 
            !(optional($user->empleado)->area === 'Sistemas' || 
              optional($user->empleado)->posicion === 'TI' || 
              optional($user->empleado)->posicion === 'IT')
        ) {
            \Log::warning('SistemasAdminMiddleware - Acceso denegado', [
                'user_id' => $user?->id,
                'reason' => 'No cumple con los requisitos',
            ]);
            
            return redirect()->route('tickets.mis-tickets')
                ->with('error', 'No tienes acceso al panel administrativo de Sistemas.');
        }
        
        \Log::info('SistemasAdminMiddleware - Acceso permitido', [
            'user_id' => $user->id,
        ]);
        
        return $next($request);
    }
}
