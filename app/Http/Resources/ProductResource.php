<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'sku'             => $this->sku,
            'barcode'         => $this->barcode,
            'category'        => $this->category,
            'description'     => $this->description,
            'stock_current'   => $this->stock_current,
            'stock_minimum'   => $this->stock_minimum,
            'unit'            => $this->unit,
            'cost'            => (float) $this->cost,
            'price'           => (float) $this->price,
            'supplier'        => $this->supplier,
            'image_url'       => $this->image_url,
            'active'          => $this->active,
            'is_low_stock'    => $this->isLowStock(),
            'inventory_value' => $this->inventoryValue(),
            'created_at'      => $this->created_at->toISOString(),
            'updated_at'      => $this->updated_at->toISOString(),
            'last_movements'  => $this->whenLoaded('inventoryMovements',
                fn() => InventoryMovementResource::collection($this->inventoryMovements)
            ),
        ];
    }
}
