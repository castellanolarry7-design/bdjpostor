<?php
// ════════════════════════════════════════════════════════════════════════
// PASO 1: Instalar Laravel Reverb en el backend
// ════════════════════════════════════════════════════════════════════════
// php artisan install:broadcasting
// (esto instala Reverb, publica la config y actualiza el .env)

// .env — agregar:
// BROADCAST_CONNECTION=reverb
// REVERB_APP_ID=jpstore
// REVERB_APP_KEY=jpstore-key
// REVERB_APP_SECRET=jpstore-secret
// REVERB_HOST=localhost
// REVERB_PORT=8080
// REVERB_SCHEME=http

// ════════════════════════════════════════════════════════════════════════
// PASO 2: Crear el evento de stock bajo
// php artisan make:event LowStockAlert
// ════════════════════════════════════════════════════════════════════════

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Product $product)
    {
        //
    }

    /**
     * Canal privado por tenant: solo los usuarios de ESE tenant reciben el evento.
     * El canal 'tenant.{id}' es privado — requiere autenticación en el frontend.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->product->tenant_id}"),
        ];
    }

    /**
     * Nombre del evento que escuchará el frontend.
     */
    public function broadcastAs(): string
    {
        return 'low-stock-alert';
    }

    /**
     * Datos enviados al frontend vía WebSocket.
     */
    public function broadcastWith(): array
    {
        return [
            'product_id'    => $this->product->id,
            'product_name'  => $this->product->name,
            'sku'           => $this->product->sku,
            'stock_current' => $this->product->stock_current,
            'stock_minimum' => $this->product->stock_minimum,
        ];
    }
}
