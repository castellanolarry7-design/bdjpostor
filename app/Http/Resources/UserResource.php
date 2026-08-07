<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'role'          => $this->role,
            'active'        => $this->active,
            'avatar_url'    => $this->avatar_url,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at'    => $this->created_at->toISOString(),
            'tenant'        => $this->whenLoaded('tenant', fn() => $this->tenant ? [
                'id'   => $this->tenant->id,
                'name' => $this->tenant->name,
                'slug' => $this->tenant->slug,
            ] : null),
        ];
    }
}
