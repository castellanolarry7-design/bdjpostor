<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * TenantController
 *
 * Solo accesible por SUPER ADMIN.
 * Gestión completa de empresas/clientes de JPStore.
 */
class TenantController extends Controller
{
    /**
     * GET /api/tenants
     *
     * Lista todos los tenants con paginación y filtros.
     * Parámetros opcionales: ?search=nombre&active=true&plan=pro&per_page=15
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query();

        // Filtro de búsqueda por nombre, email o slug
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filtro por estado activo
        if ($request->has('active')) {
            $query->where('active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN));
        }

        // Filtro por plan
        if ($plan = $request->get('plan')) {
            $query->where('plan', $plan);
        }

        // Contar usuarios por tenant para el listado
        $query->withCount('users')->withCount('products');

        $tenants = $query->orderBy('created_at', 'desc')
                         ->paginate($request->get('per_page', 15));

        return response()->json([
            'data'  => TenantResource::collection($tenants->items()),
            'meta'  => [
                'total'        => $tenants->total(),
                'per_page'     => $tenants->perPage(),
                'current_page' => $tenants->currentPage(),
                'last_page'    => $tenants->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/tenants
     *
     * Crea un nuevo tenant (empresa cliente).
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $result = DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name'   => $request->name,
                'slug'   => $request->slug ?? Str::slug($request->name),
                'email'  => $request->email,
                'phone'  => $request->phone,
                'plan'   => $request->plan ?? 'free',
                'active' => true,
            ]);

            $admin = User::create([
                'tenant_id'         => $tenant->id,
                'name'              => $request->admin_name,
                'email'             => $request->admin_email,
                'password'          => $request->admin_password,
                'role'              => 'admin',
                'active'            => true,
                'email_verified_at' => now(),
            ]);

            return ['tenant' => $tenant, 'admin' => $admin];
        });

        return response()->json([
            'message' => 'Empresa y administrador creados correctamente.',
            'data'    => new TenantResource($result['tenant']),
            'admin'   => [
                'name'  => $result['admin']->name,
                'email' => $result['admin']->email,
            ],
        ], 201);
    }

    /**
     * GET /api/tenants/{id}
     *
     * Muestra un tenant específico con sus estadísticas.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = Tenant::withCount(['users', 'products'])
                        ->findOrFail($id);

        return response()->json([
            'data' => new TenantResource($tenant),
        ]);
    }

    /**
     * PUT /api/tenants/{id}
     *
     * Actualiza los datos de un tenant.
     */
    public function update(UpdateTenantRequest $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $tenant->update($request->validated());

        return response()->json([
            'message' => 'Empresa actualizada correctamente.',
            'data'    => new TenantResource($tenant->fresh()),
        ]);
    }

    /**
     * PATCH /api/tenants/{id}/toggle-active
     *
     * Activa o desactiva un tenant.
     * Al desactivar: los usuarios del tenant no podrán autenticarse.
     */
    public function toggleActive(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $tenant->update(['active' => ! $tenant->active]);

        $status = $tenant->active ? 'activada' : 'desactivada';

        return response()->json([
            'message' => "Empresa {$status} correctamente.",
            'active'  => $tenant->active,
        ]);
    }

    /**
     * DELETE /api/tenants/{id}
     *
     * Soft delete de un tenant.
     * En producción real, esto debería tener un flujo de cancelación
     * más robusto (aviso al cliente, período de gracia, etc.)
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        // Verificar que no tiene datos activos importantes
        $activeProducts = $tenant->products()->count();

        if ($activeProducts > 0) {
            return response()->json([
                'message' => "No se puede eliminar: el tenant tiene {$activeProducts} productos. Desactívalo primero.",
            ], 422);
        }

        $tenant->delete();

        return response()->json([
            'message' => 'Empresa eliminada correctamente.',
        ]);
    }
}
