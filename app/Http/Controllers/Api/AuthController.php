<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    /**
     * Hash de descarte para que la comprobación de contraseña tarde lo mismo
     * exista o no el usuario. Es un bcrypt válido de una cadena aleatoria.
     */
    private const DUMMY_HASH = '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

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
        $email = mb_strtolower(trim($request->email));

        // ─── Límite de intentos ──────────────────────────────────────────
        // Doble llave: por IP (frena escaneos masivos) y por email
        // (frena el ataque dirigido a una cuenta concreta desde muchas IPs).
        $ipKey    = 'login:ip:' . $request->ip();
        $userKey  = 'login:user:' . sha1($email);

        foreach ([[$ipKey, 20], [$userKey, 5]] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $seconds = RateLimiter::availableIn($key);

                return response()->json([
                    'message'     => "Demasiados intentos fallidos. Vuelve a intentarlo en {$seconds} segundos.",
                    'retry_after' => $seconds,
                ], 429);
            }
        }

        $user = User::firstWhere('email', $email);

        // Hash::check se ejecuta SIEMPRE, exista o no el usuario. Si solo se
        // comprobara cuando existe, el tiempo de respuesta delataría qué
        // correos están registrados (ataque de tiempo / enumeración).
        $hash      = $user->password ?? self::DUMMY_HASH;
        $validPass = Hash::check($request->password, $hash);

        if (! $user || ! $validPass) {
            RateLimiter::hit($ipKey, 900);    // ventana de 15 minutos
            RateLimiter::hit($userKey, 900);

            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        // Credenciales correctas: se limpia el contador
        RateLimiter::clear($ipKey);
        RateLimiter::clear($userKey);

        // Cuenta o empresa inactivas. Se responde lo mismo en ambos casos para
        // no confirmar qué correos existen ni a qué empresa pertenecen.
        if (! $user->active || ($user->tenant_id && $user->tenant && ! $user->tenant->active)) {
            return response()->json([
                'message' => 'Tu acceso está deshabilitado. Contacta al administrador.',
            ], 403);
        }

        // Solo se revoca el token de ESTE dispositivo. Antes se borraban todos
        // los llamados 'api', así que entrar desde el móvil cerraba la sesión
        // del escritorio.
        $deviceName = $this->resolveDeviceName($request);
        $user->tokens()->where('name', $deviceName)->delete();

        // Crear nuevo token Sanctum
        // Las "abilities" definen qué puede hacer el token (útil para tokens limitados)
        $abilities = $this->resolveAbilities($user->role);

        // Sin caducidad: la sesión dura hasta que se cierre en el dispositivo,
        // que es el comportamiento pedido. La contrapartida es que un token
        // robado vale para siempre, así que se revoca en cuanto el usuario se
        // desactiva, se elimina o cambia su contraseña (ver UserController).
        $token = $user->createToken(
            name: $deviceName,
            abilities: $abilities,
            expiresAt: null
        );

        // Actualizar último login
        $user->update(['last_login_at' => now()]);

        // Cargar relación tenant para la respuesta
        $user->load('tenant');

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toISOString(),
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
        $request->user()?->currentAccessToken()?->delete();

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
     * Nombre del token = identificador del dispositivo.
     * Se sanea porque acaba en la base de datos y se compara en consultas.
     */
    private function resolveDeviceName(Request $request): string
    {
        $raw = (string) $request->input('device_name', '');
        $clean = preg_replace('/[^A-Za-z0-9._-]/', '', $raw);

        return $clean !== '' ? mb_substr($clean, 0, 60) : 'api';
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
