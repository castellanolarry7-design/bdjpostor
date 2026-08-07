<?php
// app/Observers/InventoryMovementObserver.php
namespace App\Observers;

use App\Events\LowStockAlert;
use App\Models\InventoryMovement;

/**
 * InventoryMovementObserver
 *
 * Se ejecuta automáticamente después de crear un movimiento.
 * Verifica si el stock del producto quedó en nivel bajo
 * y dispara el evento de WebSocket si es así.
 *
 * Registrar en AppServiceProvider:
 *   InventoryMovement::observe(InventoryMovementObserver::class);
 */
class InventoryMovementObserver
{
    public function created(InventoryMovement $movement): void
    {
        // Cargar el producto fresco (con el stock ya actualizado)
        $product = $movement->product()->withoutGlobalScopes()->first();

        if (!$product) return;

        // Disparar alerta si el stock quedó en nivel bajo o crítico
        if ($product->stock_current <= $product->stock_minimum) {
            LowStockAlert::dispatch($product);
        }
    }
}
