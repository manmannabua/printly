<?php

namespace App\Traits;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRoles
{
    /**
     * Get all roles for the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(Role|string $role, ?string $assignedBy = null): self
    {
        $roleModel = $role instanceof Role ? $role : Role::where('name', $role)->firstOrFail();

        if (!$this->hasRole($roleModel->name)) {
            $this->roles()->attach($roleModel->id, [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'assigned_by' => $assignedBy ?? auth()->id(),
            ]);
        }

        return $this;
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(Role|string $role): self
    {
        $roleModel = $role instanceof Role ? $role : Role::where('name', $role)->first();

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
        }

        return $this;
    }

    /**
     * Sync user roles.
     */
    public function syncRoles(array $roles, ?string $assignedBy = null): self
    {
        $roleIds = collect($roles)->map(function ($role) {
            if ($role instanceof Role) {
                return $role->id;
            }
            return Role::where('name', $role)->first()?->id;
        })->filter()->all();

        $syncData = [];
        foreach ($roleIds as $roleId) {
            $syncData[$roleId] = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'assigned_by' => $assignedBy ?? auth()->id(),
            ];
        }

        $this->roles()->sync($syncData);

        return $this;
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    /**
     * Check if the user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    /**
     * Check if the user has all of the given roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        $userRoleNames = $this->roles->pluck('name');
        return collect($roles)->every(fn($role) => $userRoleNames->contains($role));
    }

    /**
     * Get the user's role level (lowest number = highest privilege).
     */
    public function getRoleLevel(): int
    {
        return $this->roles->min('level') ?? 999;
    }

    /**
     * Get the primary role (highest privilege role).
     */
    public function getPrimaryRoleAttribute(): ?Role
    {
        return $this->roles->sortBy('level')->first();
    }

    /**
     * Check if the user is an admin.
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Get role names as an array.
     */
    public function getRoleNamesAttribute(): array
    {
        return $this->roles->pluck('name')->toArray();
    }
}
