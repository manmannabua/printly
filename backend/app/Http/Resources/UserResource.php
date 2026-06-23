<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'brokerage_id' => $this->brokerage_id,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Roles (when loaded)
            'roles' => $this->when(
                $this->relationLoaded('roles'),
                fn () => RoleResource::collection($this->roles)
            ),

            // Computed: flattened effective permissions list
            'permissions' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $this->getAllPermissions()->pluck('name')->unique()->values()
            ),

            // Primary role (highest privilege)
            'primary_role' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $this->getPrimaryRole()?->name
            ),

            // Admin flag
            'is_admin' => $this->is_admin,

            // Security PIN flag
            'has_security_pin' => $this->has_security_pin,

            // Server-side lock state — persisted in the session so a new tab
            // (which shares the same session cookie) cannot bypass the PIN gate.
            'is_locked' => $request->hasSession()
                ? (bool) $request->session()->get('is_locked', false)
                : false,

            // Role level (for UI permission checks)
            'role_level' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $this->getRoleLevel()
            ),

            // Linked agent profile id (when loaded) — supports a future agent portal
            'agent_id' => $this->when(
                $this->relationLoaded('agent'),
                fn () => $this->agent?->id
            ),
        ];
    }

    /**
     * Get the primary role (highest privilege / lowest level number).
     */
    protected function getPrimaryRole()
    {
        if (!$this->relationLoaded('roles') || $this->roles->isEmpty()) {
            return null;
        }

        return $this->roles->sortBy('level')->first();
    }
}
