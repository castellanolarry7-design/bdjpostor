<?php
// tests/Feature/ProductTest.php
namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function tenant(string $name = 'Tenant A'): Tenant
    {
        return Tenant::create([
            'id' => Str::uuid(), 'name' => $name,
            'slug' => Str::slug($name), 'email' => Str::slug($name).'@test.com',
            'active' => true,
        ]);
    }

    private function adminOf(Tenant $tenant): User
    {
        return User::create([
            'id' => Str::uuid(), 'tenant_id' => $tenant->id,
            'name' => 'Admin', 'email' => "admin-{$tenant->slug}@test.com",
            'password' => bcrypt('password'), 'role' => 'admin', 'active' => true,
        ]);
    }

    private function productFor(Tenant $tenant, array $attrs = []): Product
    {
        return Product::create(array_merge([
            'id' => Str::uuid(), 'tenant_id' => $tenant->id,
            'name' => 'Producto Test', 'sku' => 'SKU-'.rand(1000, 9999),
            'stock_current' => 10, 'stock_minimum' => 2,
            'cost' => 50.00, 'price' => 100.00, 'active' => true,
        ], $attrs));
    }

    // ─── Aislamiento multi-tenant (la prueba MÁS IMPORTANTE) ────────────────

    public function test_tenant_user_cannot_see_other_tenant_products(): void
    {
        $tenantA = $this->tenant('Tenant A');
        $tenantB = $this->tenant('Tenant B');

        $adminA = $this->adminOf($tenantA);
        $adminB = $this->adminOf($tenantB);

        // Crear productos en ambos tenants
        $productA = $this->productFor($tenantA, ['name' => 'Producto de A']);
        $productB = $this->productFor($tenantB, ['name' => 'Producto de B']);

        // Admin de A solo debe ver su producto
        $response = $this->actingAs($adminA)
                         ->getJson('/api/v1/products');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($productA->id));
        $this->assertFalse($ids->contains($productB->id)); // ← CLAVE: no debe ver los de B
    }

    public function test_tenant_user_cannot_access_other_tenant_product_by_id(): void
    {
        $tenantA = $this->tenant('Tenant A');
        $tenantB = $this->tenant('Tenant B');

        $adminA  = $this->adminOf($tenantA);
        $productB = $this->productFor($tenantB);

        // Admin de A intentando acceder al producto de B → debe ser 404
        $this->actingAs($adminA)
             ->getJson("/api/v1/products/{$productB->id}")
             ->assertStatus(404);
    }

    // ─── CRUD básico ─────────────────────────────────────────────────────────

    public function test_admin_can_create_product(): void
    {
        $tenant = $this->tenant();
        $admin  = $this->adminOf($tenant);

        $response = $this->actingAs($admin)->postJson('/api/v1/products', [
            'name'          => 'Laptop Dell',
            'sku'           => 'DELL-001',
            'category'      => 'Electrónica',
            'stock_initial' => 5,
            'stock_minimum' => 2,
            'cost'          => 800.00,
            'price'         => 1200.00,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'Laptop Dell')
                 ->assertJsonPath('data.stock_current', 5);

        // Verificar que se creó el movimiento de stock inicial
        $this->assertDatabaseHas('inventory_movements', [
            'tenant_id' => $tenant->id,
            'type'      => 'entrada',
            'quantity'  => 5,
        ]);
    }

    public function test_product_sku_is_unique_per_tenant_not_globally(): void
    {
        $tenantA = $this->tenant('Tenant A');
        $tenantB = $this->tenant('Tenant B');

        // Mismo SKU en ambos tenants — debe ser permitido
        $this->productFor($tenantA, ['sku' => 'SKU-COMPARTIDO']);
        $this->productFor($tenantB, ['sku' => 'SKU-COMPARTIDO']); // No debe fallar

        $this->assertDatabaseCount('products', 2);
    }

    public function test_duplicate_sku_within_same_tenant_fails(): void
    {
        $tenant = $this->tenant();
        $admin  = $this->adminOf($tenant);

        $this->productFor($tenant, ['sku' => 'SKU-DUPLICADO']);

        // Intentar crear otro producto con el mismo SKU en el mismo tenant
        $this->actingAs($admin)->postJson('/api/v1/products', [
            'name' => 'Otro producto',
            'sku'  => 'SKU-DUPLICADO',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['sku']);
    }

    public function test_admin_can_update_product(): void
    {
        $tenant  = $this->tenant();
        $admin   = $this->adminOf($tenant);
        $product = $this->productFor($tenant);

        $this->actingAs($admin)
             ->putJson("/api/v1/products/{$product->id}", ['name' => 'Nombre Actualizado'])
             ->assertStatus(200)
             ->assertJsonPath('data.name', 'Nombre Actualizado');
    }

    public function test_admin_can_soft_delete_product(): void
    {
        $tenant  = $this->tenant();
        $admin   = $this->adminOf($tenant);
        $product = $this->productFor($tenant);

        $this->actingAs($admin)
             ->deleteJson("/api/v1/products/{$product->id}")
             ->assertStatus(200);

        // Soft delete: el registro existe pero con deleted_at
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_search_filter_returns_matching_products(): void
    {
        $tenant = $this->tenant();
        $admin  = $this->adminOf($tenant);

        $this->productFor($tenant, ['name' => 'Silla de oficina', 'sku' => 'SILLA-001']);
        $this->productFor($tenant, ['name' => 'Mesa de escritorio', 'sku' => 'MESA-001']);

        $response = $this->actingAs($admin)
                         ->getJson('/api/v1/products?search=silla');

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Silla de oficina', $data[0]['name']);
    }

    public function test_low_stock_filter_returns_only_low_stock_products(): void
    {
        $tenant = $this->tenant();
        $admin  = $this->adminOf($tenant);

        $this->productFor($tenant, ['stock_current' => 1, 'stock_minimum' => 5]);  // bajo
        $this->productFor($tenant, ['stock_current' => 10, 'stock_minimum' => 5]); // normal

        $response = $this->actingAs($admin)
                         ->getJson('/api/v1/products?low_stock=true');

        $this->assertCount(1, $response->json('data'));
    }
}
