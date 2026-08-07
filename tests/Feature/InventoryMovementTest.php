<?php
// tests/Feature/InventoryMovementTest.php
namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class InventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    private function setup(): array
    {
        $tenant = Tenant::create([
            'id' => Str::uuid(), 'name' => 'Tenant Test',
            'slug' => 'tenant-test', 'email' => 'test@tenant.com', 'active' => true,
        ]);
        $admin = User::create([
            'id' => Str::uuid(), 'tenant_id' => $tenant->id,
            'name' => 'Admin', 'email' => 'admin@tenant.com',
            'password' => bcrypt('password'), 'role' => 'admin', 'active' => true,
        ]);
        $product = Product::create([
            'id' => Str::uuid(), 'tenant_id' => $tenant->id,
            'name' => 'Producto X', 'sku' => 'PROD-X',
            'stock_current' => 10, 'stock_minimum' => 2,
            'cost' => 50.00, 'price' => 100.00, 'active' => true,
        ]);
        return [$tenant, $admin, $product];
    }

    // ─── Actualización atómica de stock ─────────────────────────────────────

    public function test_entrada_increases_stock_atomically(): void
    {
        [$tenant, $admin, $product] = $this->setup();

        $response = $this->actingAs($admin)->postJson('/api/v1/inventory-movements', [
            'product_id' => $product->id,
            'type'       => 'entrada',
            'quantity'   => 5,
            'note'       => 'Compra de prueba',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.stock_before', 10)
                 ->assertJsonPath('data.stock_after',  15);

        // El stock del producto debe haberse actualizado en la BD
        $this->assertEquals(15, $product->fresh()->stock_current);
    }

    public function test_salida_decreases_stock_atomically(): void
    {
        [$tenant, $admin, $product] = $this->setup();

        $this->actingAs($admin)->postJson('/api/v1/inventory-movements', [
            'product_id' => $product->id,
            'type'       => 'salida',
            'quantity'   => 3,
        ])->assertStatus(201);

        $this->assertEquals(7, $product->fresh()->stock_current);
    }

    public function test_salida_fails_when_stock_insufficient(): void
    {
        [$tenant, $admin, $product] = $this->setup(); // stock = 10

        // Intentar sacar más de lo que hay
        $this->actingAs($admin)->postJson('/api/v1/inventory-movements', [
            'product_id' => $product->id,
            'type'       => 'salida',
            'quantity'   => 15, // > 10
        ])->assertStatus(422);

        // El stock NO debe haber cambiado (rollback de la transacción)
        $this->assertEquals(10, $product->fresh()->stock_current);

        // Tampoco debe existir el movimiento
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_ajuste_sets_stock_directly(): void
    {
        [$tenant, $admin, $product] = $this->setup(); // stock = 10

        $this->actingAs($admin)->postJson('/api/v1/inventory-movements', [
            'product_id' => $product->id,
            'type'       => 'ajuste',
            'quantity'   => 7,  // el nuevo stock total
            'note'       => 'Toma física de inventario',
        ])->assertStatus(201)
          ->assertJsonPath('data.stock_after', 7);

        $this->assertEquals(7, $product->fresh()->stock_current);
    }

    public function test_movement_records_stock_snapshot(): void
    {
        [$tenant, $admin, $product] = $this->setup(); // stock = 10

        $response = $this->actingAs($admin)->postJson('/api/v1/inventory-movements', [
            'product_id' => $product->id,
            'type'       => 'entrada',
            'quantity'   => 5,
        ]);

        // Los snapshots de stock deben ser correctos
        $movement = InventoryMovement::find($response->json('data.id'));
        $this->assertEquals(10, $movement->stock_before);
        $this->assertEquals(15, $movement->stock_after);
    }

    public function test_movements_are_immutable_no_update_or_delete(): void
    {
        [$tenant, $admin, $product] = $this->setup();

        $response = $this->actingAs($admin)->postJson('/api/v1/inventory-movements', [
            'product_id' => $product->id, 'type' => 'entrada', 'quantity' => 3,
        ]);

        $movementId = $response->json('data.id');

        // Intentar actualizar — debe ser 404 (ruta no existe)
        $this->actingAs($admin)
             ->putJson("/api/v1/inventory-movements/{$movementId}", ['quantity' => 99])
             ->assertStatus(404);

        // Intentar eliminar — debe ser 404 (ruta no existe)
        $this->actingAs($admin)
             ->deleteJson("/api/v1/inventory-movements/{$movementId}")
             ->assertStatus(404);
    }

    public function test_user_cannot_create_movement_for_other_tenant_product(): void
    {
        $tenantA = Tenant::create([
            'id' => Str::uuid(), 'name' => 'A', 'slug' => 'a',
            'email' => 'a@a.com', 'active' => true,
        ]);
        $tenantB = Tenant::create([
            'id' => Str::uuid(), 'name' => 'B', 'slug' => 'b',
            'email' => 'b@b.com', 'active' => true,
        ]);
        $adminA = User::create([
            'id' => Str::uuid(), 'tenant_id' => $tenantA->id, 'name' => 'AdminA',
            'email' => 'a@admin.com', 'password' => bcrypt('p'), 'role' => 'admin', 'active' => true,
        ]);
        $productB = Product::create([
            'id' => Str::uuid(), 'tenant_id' => $tenantB->id, 'name' => 'P de B',
            'sku' => 'SKU-B', 'stock_current' => 5, 'stock_minimum' => 1,
            'cost' => 10, 'price' => 20, 'active' => true,
        ]);

        // Admin de A intentando crear movimiento sobre producto de B → 404
        $this->actingAs($adminA)->postJson('/api/v1/inventory-movements', [
            'product_id' => $productB->id, 'type' => 'entrada', 'quantity' => 1,
        ])->assertStatus(404);
    }

    public function test_date_filter_returns_movements_in_range(): void
    {
        [$tenant, $admin, $product] = $this->setup();

        // Movimiento de hace 10 días
        $this->actingAs($admin)->postJson('/api/v1/inventory-movements', [
            'product_id' => $product->id, 'type' => 'entrada', 'quantity' => 2,
            'moved_at'   => now()->subDays(10)->toDateTimeString(),
        ]);

        // Movimiento de hoy
        $this->actingAs($admin)->postJson('/api/v1/inventory-movements', [
            'product_id' => $product->id, 'type' => 'salida', 'quantity' => 1,
            'moved_at'   => now()->toDateTimeString(),
        ]);

        // Filtrar solo los de los últimos 5 días
        $response = $this->actingAs($admin)->getJson(
            '/api/v1/inventory-movements?from=' . now()->subDays(5)->toDateString()
        );

        $this->assertCount(1, $response->json('data'));
    }
}
