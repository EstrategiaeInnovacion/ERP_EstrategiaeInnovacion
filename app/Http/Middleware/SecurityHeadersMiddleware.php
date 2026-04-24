<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Anti-Clickjacking: solo permite iframes del mismo origen
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 2. Anti-MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 3. HSTS: fuerza HTTPS por 1 año en navegadores
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains; preload'
        );

        // 4. Referrer: no filtra información de navegación a terceros
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 5. Permissions-Policy: deshabilita APIs de hardware no usadas en el ERP
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), fullscreen=(self)'
        );

        // 6. Content-Security-Policy adaptada al ERP:
        //    - fonts.googleapis.com + fonts.bunny.net: Google Fonts y Bunny Fonts (login de Breeze)
        //    - fonts.gstatic.com + fonts.bunny.net: archivos de fuente de ambos CDNs
        //    - youtube.com: módulo de Capacitación (videos embebidos)
        //    - blob: + data:: previsualizaciones de archivos subidos
        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
                "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",
                "img-src 'self' data: blob: https:",
                "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com",
                "connect-src 'self'",
                "media-src 'self' blob:",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ])
        );

        // Oculta información del servidor en respuestas PHP
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}

