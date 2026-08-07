<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'plan'           => $this->plan,
            'active'         => $this->active,
            'created_at'     => $this->created_at->toISOString(),
            'users_count'    => $this->when(isset($this->users_count),    $this->users_count),
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
        ];
    }
}
