<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege endpoints de consulta machine-to-machine mediante un API Key estático.
 *
 * El cliente debe enviar el header:  X-API-Key: {valor de API_KEY en .env}
 */
class CheckApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('app.api_key');

        if (empty($apiKey) || $request->header('X-API-Key') !== $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key inválida o no proporcionada.',
            ], 401);
        }

        return $next($request);
    }
}
