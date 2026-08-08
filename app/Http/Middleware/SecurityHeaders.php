<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todas las respuestas de la API.
 *
 * La API devuelve JSON, así que la política de contenido puede ser muy
 * restrictiva: nada de scripts, nada de incrustar la respuesta en un iframe.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Evita que el navegador "adivine" el tipo de contenido y ejecute
        // como HTML/JS algo que devolvimos como JSON.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // La API nunca debe cargarse dentro de un marco (clickjacking).
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");

        // No filtrar la URL completa (que puede llevar identificadores) al
        // navegar hacia otros dominios.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Sin permisos de hardware para la API.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Las respuestas con datos de sesión no deben quedar en caché.
        if ($request->is('api/*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
