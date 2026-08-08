<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureRole — Middleware de control de acceso por rol
 *
 * Uso en rutas:
 *   ->middleware('role:super_admin')
 *   ->middleware('role:super_admin,admin')
 *   ->middleware('role:admin,user')
 *
 * Ejemplos:
 *   // Solo super_admin puede gestionar tenants
 *   Route::middleware(['auth:sanctum', 'tenant.scope', 'role:super_admin'])
 *        ->group(function () { ... });
 *
 *   // admin y user pueden ver productos de su tenant
 *   Route::middleware(['auth:sanctum', 'tenant.scope', 'role:admin,user'])
 *        ->group(function () { ... });
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        // Verificar si el rol del usuario está en los roles permitidos
        if (! in_array($user->role, $roles)) {
            // No se devuelven los roles esperados ni el del usuario: es
            // información que solo le sirve a quien está sondeando la API.
            return response()->json([
                'message' => 'No tienes permisos para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}
