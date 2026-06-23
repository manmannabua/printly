<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'level' => $this->level,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Permissions (when loaded)
            'permissions' => $this->when(
                $this->relationLoaded('permissions'),
                fn () => PermissionResource::collection($this->permissions)
            ),

            // Permission count (when counted but not loaded)
            'permissions_count' => $this->when(
                isset($this->permissions_count),
                fn () => $this->permissions_count
            ),

            // Users (when loaded)
            'users' => $this->when(
                $this->relationLoaded('users'),
                fn () => UserResource::collection($this->users)
            ),

            // Users count (when counted)
            'users_count' => $this->when(
                isset($this->users_count),
                fn () => $this->users_count
            ),

            // Is system role (cannot be deleted)
            'is_system_role' => in_array($this->name, ['admin', 'employee']),
        ];
    }
}
