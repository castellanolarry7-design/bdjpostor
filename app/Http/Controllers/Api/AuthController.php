<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     *
     * Autentica al usuario y devuelve un token Sanctum.
     *
     * Request body:
     * {
     *   "email": "user@example.com",
     *   "password": "secret123",
     *   "device_name": "Chrome/Windows" (opcional)
     * }
     *
     * Response 200:
     * {
     *   "token": "1|abcdef...",
     *   "token_type": "Bearer",
     *   "user": { ...UserResource },
     *   "tenant": { ...TenantResource } | null
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Buscar usuario por email incluyendo soft-deleted (no permitir login)
        $user = User::where('email', $request->email)->first();

        // Verificar que el usuario existe y la contraseña es correcta
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        // Verificar que el usuario está activo
        if (! $user->active) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Contacta al administrador.',
            ], 403);
        }

        // Si tiene tenant, verificar que el tenant también está activo
        if ($user->tenant_id && $user->tenant && ! $user->tenant->active) {
            return response()->json([
                'message' => 'Tu empresa está inactiva. Contacta a soporte JPStore.',
            ], 403);
        }

        // Revocar tokens anteriores del mismo dispositivo (evitar tokens huérfanos)
        // En producción podrías querer mantener múltiples tokens (multi-device)
        $user->tokens()->where('name', $request->device_name ?? 'api')->delete();

        // Crear nuevo token Sanctum
        // Las "abilities" definen qué puede hacer el token (útil para tokens limitados)
        $abilities = $this->resolveAbilities($user->role);

        $token = $user->createToken(
            name: $request->device_name ?? 'api',
            abilities: $abilities,
            expiresAt: now()->addDays(30) // El token expira en 30 días
        );

        // Actualizar último login
        $user->update(['last_login_at' => now()]);

        // Cargar relación tenant para la respuesta
        $user->load('tenant');

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
            'user'       => new UserResource($user),
        ], 200);
    }

    /**
     * POST /api/auth/logout
     *
     * Revoca el token actual del usuario.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revocar solo el token actual (no todos los dispositivos)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }

    /**
     * GET /api/auth/me
     *
     * Devuelve los datos del usuario autenticado.
     * Útil para que el frontend Vue refresque el estado del usuario.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('tenant');

        return response()->json([
            'user' => new UserResource($user),
        ], 200);
    }

    /**
     * Definir las abilities del token según el rol.
     * Esto permite tokens con permisos limitados en el futuro.
     */
    private function resolveAbilities(string $role): array
    {
        return match($role) {
            'super_admin' => ['*'], // Acceso total
            'admin'       => ['read', 'write', 'manage-users'],
            'user'        => ['read', 'write'],
            default       => ['read'],
        };
    }
}
