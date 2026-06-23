<?php

namespace App\Traits;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasPermissions
{
    /**
     * Permissions directly assigned to this user (not via roles).
     */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    /**
     * Get all effective permissions for the user.
     *
     * Direct user permissions, when present, fully replace the base role
     * permissions. Otherwise the union of the user's role permissions applies.
     */
    public function getAllPermissions(): Collection
    {
        $this->loadMissing(['roles.permissions', 'directPermissions']);

        $basePermissions = $this->roles
            ->flatMap(fn ($role) => $role->permissions);

        $explicit = $this->directPermissions->unique('id');

        if ($explicit->isEmpty()) {
            return $basePermissions->unique('id');
        }

        return $explicit;
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Admin bypass
        if ($this->hasRole('admin')) {
            return true;
        }

        return $this->getAllPermissions()->contains('name', $permission);
    }

    /**
     * Check if the user holds a permission by its exact name (admin still bypasses).
     */
    public function hasExactPermission(string $permission): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return $this->getAllPermissions()->contains('name', $permission);
    }

    /**
     * Check if the user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }
}
