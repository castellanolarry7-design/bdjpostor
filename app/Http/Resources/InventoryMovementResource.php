<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'quantity'     => $this->quantity,
            'stock_before' => $this->stock_before,
            'stock_after'  => $this->stock_after,
            'unit_cost'    => (float) $this->unit_cost,
            'unit_price'   => (float) $this->unit_price,
            'note'         => $this->note,
            'reference'    => $this->reference,
            'moved_at'     => $this->moved_at?->toISOString(),
            'created_at'   => $this->created_at->toISOString(),
            'product'      => $this->whenLoaded('product', fn() => $this->product ? [
                'id'   => $this->product->id,
                'name' => $this->product->name,
                'sku'  => $this->product->sku,
            ] : null),
            'user'         => $this->whenLoaded('user', fn() => $this->user ? [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ] : null),
        ];
    }
}
