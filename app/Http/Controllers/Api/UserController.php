<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * UserController
 *
 * Super Admin: puede gestionar usuarios de CUALQUIER tenant.
 * Admin de tenant: puede gestionar usuarios de SU tenant.
 * User: solo puede ver/editar su propio perfil.
 */
class UserController extends Controller
{
    /**
     * GET /api/users
     *
     * Super admin: lista todos los usuarios (con filtro de tenant opcional).
     * Admin: lista usuarios de su propio tenant.
     *
     * Parámetros: ?tenant_id=uuid&role=admin&active=true&search=nombre
     */
    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $this->authorize('viewAny', User::class);

        $query = User::query()->with('tenant');

        // ─── Control de acceso por rol ───────────────────────────────────

        if ($authUser->isSuperAdmin()) {
            // Super admin puede filtrar por tenant específico
            if ($tenantId = $request->get('tenant_id')) {
                $query->where('tenant_id', $tenantId);
            }
            // Y puede ver (o no) a los super admins
            if (! $request->boolean('include_super_admins')) {
                $query->where('role', '!=', 'super_admin');
            }
        } else {
            // Admin solo ve usuarios de su propia empresa, y nunca super_admins
            // (ni siquiera para saber que existen).
            $query->where('tenant_id', $authUser->tenant_id)
                  ->where('role', '!=', 'super_admin');
        }

        // ─── Filtros ─────────────────────────────────────────────────────

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        if ($request->has('active')) {
            $query->where('active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/users
     *
     * Crea un nuevo usuario.
     *
     * Super admin: puede asignar cualquier tenant y cualquier rol.
     * Admin: puede crear usuarios SOLO en su tenant, con roles 'admin' o 'user'.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $authUser = $request->user();
        // Usar la policy para verificar si el usuario puede crear usuarios con los datos proporcionados
        $this->authorize('create', [User::class, $request->all()]);

        // El tenant NO se toma del body salvo para el super admin: si no, un
        // admin podría crear usuarios dentro de otra empresa.
        $tenantId = $authUser->isSuperAdmin()
            ? ($request->tenant_id ?? null)
            : $authUser->tenant_id;

        // Segunda barrera, además de la policy: un admin jamás crea super_admins.
        $role = $request->role ?? 'user';
        if (! $authUser->isSuperAdmin() && $role === 'super_admin') {
            abort(403, 'No puedes crear usuarios con ese rol.');
        }

        $user = User::create([
            'id'        => Str::uuid(),
            'tenant_id' => $tenantId,
            'name'      => $request->name,
            'email'     => mb_strtolower(trim($request->email)),
            'password'  => $request->password, // El cast 'hashed' en el modelo lo encripta
            'role'      => $role,
            'active'    => true,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data'    => new UserResource($user->load('tenant')),
        ], 201);
    }

    /**
     * GET /api/users/{id}
     *
     * Muestra un usuario específico.
     * El usuario puede ver su propio perfil.
     * Admin puede ver usuarios de su tenant.
     * Super admin puede ver cualquier usuario.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = User::with('tenant')->findOrFail($id);

        // Control de acceso
        $this->authorize('view', $user);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * PUT /api/users/{id}
     *
     * Actualiza un usuario.
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // La policy se encarga de verificar si el usuario autenticado puede actualizar el $user
        $this->authorize('update', [$user, $request->all()]);

        $data = $request->validated();

        // Cambiar la contraseña, el rol o la empresa invalida las sesiones
        // abiertas: si el token se filtró, seguiría sirviendo para siempre
        // (los tokens no caducan por diseño).
        $revocar = array_key_exists('password', $data)
                || (array_key_exists('role', $data)      && $data['role']      !== $user->role)
                || (array_key_exists('tenant_id', $data) && $data['tenant_id'] !== $user->tenant_id);

        if (array_key_exists('email', $data)) {
            $data['email'] = mb_strtolower(trim($data['email']));
        }

        $user->update($data);

        if ($revocar) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data'    => new UserResource($user->fresh()->load('tenant')),
        ]);
    }

    /**
     * PATCH /api/users/{id}/toggle-active
     *
     * Activa o desactiva un usuario.
     */
    public function toggleActive(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // La policy se encarga de verificar si se puede activar/desactivar y si no es el mismo usuario
        $this->authorize('toggleActive', $user);

        $user->update(['active' => ! $user->active]);

        // Desactivar debe surtir efecto ya: sin esto, el usuario seguiría
        // operando con el token que ya tenía en la mano.
        if (! $user->active) {
            $user->tokens()->delete();
        }

        $status = $user->active ? 'activado' : 'desactivado';

        return response()->json([
            'message' => "Usuario {$status} correctamente.",
            'active'  => $user->active,
        ]);
    }

    /**
     * DELETE /api/users/{id}
     *
     * Soft delete de usuario.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // La policy se encarga de verificar si se puede eliminar y si no es el mismo usuario
        $this->authorize('delete', $user);

        $user->tokens()->delete();   // cerrar sus sesiones antes de borrarlo
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
