<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureTenantScope — Middleware central del sistema multi-tenant
 *
 * Responsabilidades:
 * 1. Verificar que el usuario está autenticado (Sanctum ya lo garantiza,
 *    pero hacemos doble check por seguridad).
 * 2. Verificar que el usuario está activo.
 * 3. Si es super_admin: NO inyectar tenant (puede ver todo).
 * 4. Si es usuario de empresa: verificar que tiene tenant_id válido,
 *    que el tenant existe, y que está activo.
 * 5. Inyectar el tenant_id en el contenedor de Laravel para que
 *    TenantScope lo consuma automáticamente en todas las queries.
 *
 * Este middleware se aplica en el grupo de rutas protegidas de la API.
 * Debe ir DESPUÉS de 'auth:sanctum' en el stack de middlewares.
 *
 * Orden correcto en routes/api.php:
 *   ->middleware(['auth:sanctum', 'tenant.scope'])
 */
class EnsureTenantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Seguridad: verificar que el usuario existe (auth:sanctum ya lo hace,
        // pero este check explícito mejora la legibilidad y el testing)
        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        // Verificar que el usuario está activo
        if (! $user->active) {
            return response()->json([
                'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador.',
            ], 403);
        }

        // ─── SUPER ADMIN ────────────────────────────────────────────────────
        // El super_admin no tiene tenant_id.
        // No aplicamos ningún filtro — puede ver y operar en todos los tenants.
        // TenantScope detectará que current_tenant_id es null y no filtrará.
        if ($user->isSuperAdmin()) {
            app()->instance('current_tenant_id', false);
            return $next($request);
        }

        // ─── USUARIO DE EMPRESA ──────────────────────────────────────────────
        // Todo usuario que no sea super_admin DEBE tener un tenant_id.
        if (! $user->tenant_id) {
            return response()->json([
                'message' => 'Usuario sin empresa asignada. Contacta al administrador.',
            ], 403);
        }

        // Cargar el tenant y verificar que existe y está activo.
        // Usamos eager loading para evitar N+1 en requests posteriores.
        $tenant = $user->tenant;

        if (! $tenant) {
            return response()->json([
                'message' => 'La empresa asignada no existe.',
            ], 403);
        }

        if (! $tenant->active) {
            return response()->json([
                'message' => 'Tu empresa está inactiva. Contacta a soporte.',
            ], 403);
        }

        // ─── Inyectar tenant_id en el contenedor ────────────────────────────
        // A partir de este punto, TenantScope añadirá automáticamente
        // WHERE tenant_id = '{uuid}' en todas las queries de Product
        // e InventoryMovement sin necesidad de código adicional en los
        // controladores.
        app()->instance('current_tenant_id', $user->tenant_id);

        // Hacer el tenant disponible en el request para acceso rápido
        // en controladores: $request->tenant
        $request->merge(['_tenant' => $tenant]);

        return $next($request);
    }
}
