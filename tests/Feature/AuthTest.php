<?php
// tests/Feature/AuthTest.php
namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'id'     => Str::uuid(),
            'name'   => 'Empresa Test',
            'slug'   => 'empresa-test',
            'email'  => 'empresa@test.com',
            'active' => true,
        ]);
    }

    private function createUser(Tenant $tenant, string $role = 'user'): User
    {
        return User::create([
            'id'        => Str::uuid(),
            'tenant_id' => $tenant->id,
            'name'      => 'Test User',
            'email'     => "user-{$role}@test.com",
            'password'  => bcrypt('password123'),
            'role'      => $role,
            'active'    => true,
        ]);
    }

    private function createSuperAdmin(): User
    {
        return User::create([
            'id'        => Str::uuid(),
            'tenant_id' => null,
            'name'      => 'Super Admin',
            'email'     => 'superadmin@test.com',
            'password'  => bcrypt('password123'),
            'role'      => 'super_admin',
            'active'    => true,
        ]);
    }

    // ─── Login ──────────────────────────────────────────────────────────────

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $tenant = $this->createTenant();
        $user   = $this->createUser($tenant);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'token', 'token_type', 'expires_at',
                     'user' => ['id', 'name', 'email', 'role'],
                 ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $tenant = $this->createTenant();
        $user   = $this->createUser($tenant);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $tenant = $this->createTenant();
        $user   = $this->createUser($tenant);
        $user->update(['active' => false]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_login_fails_when_tenant_is_inactive(): void
    {
        $tenant = $this->createTenant();
        $user   = $this->createUser($tenant);
        $tenant->update(['active' => false]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_logout_revokes_token(): void
    {
        $tenant = $this->createTenant();
        $user   = $this->createUser($tenant);
        $token  = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/v1/auth/logout')
             ->assertStatus(200);

        // El token debe estar revocado — el siguiente request debe dar 401
        $this->withToken($token)
             ->getJson('/api/v1/auth/me')
             ->assertStatus(401);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
    }
}
