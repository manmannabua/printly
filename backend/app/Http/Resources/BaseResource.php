<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    /**
     * Include a field conditionally based on permission.
     */
    protected function whenHasPermission(string $permission, mixed $value, mixed $default = null): mixed
    {
        $user = request()->user();

        if ($user && $user->hasPermission($permission)) {
            return value($value);
        }

        return value($default);
    }

    /**
     * Include a field conditionally based on any of the given permissions.
     */
    protected function whenHasAnyPermission(array $permissions, mixed $value, mixed $default = null): mixed
    {
        $user = request()->user();

        if ($user && $user->hasAnyPermission($permissions)) {
            return value($value);
        }

        return value($default);
    }

    /**
     * Include a field conditionally based on role.
     */
    protected function whenHasRole(string $role, mixed $value, mixed $default = null): mixed
    {
        $user = request()->user();

        if ($user && $user->hasRole($role)) {
            return value($value);
        }

        return value($default);
    }

    /**
     * Include a field conditionally based on any of the given roles.
     */
    protected function whenHasAnyRole(array $roles, mixed $value, mixed $default = null): mixed
    {
        $user = request()->user();

        if ($user && $user->hasAnyRole($roles)) {
            return value($value);
        }

        return value($default);
    }

    /**
     * Include a field only if the authenticated user owns the resource.
     */
    protected function whenOwner(mixed $value, mixed $default = null): mixed
    {
        $user = request()->user();

        if ($user && $this->resource->user_id === $user->id) {
            return value($value);
        }

        return value($default);
    }

    /**
     * Get the format for date attributes.
     */
    protected function formatDate(?string $date): ?string
    {
        return $date ? date('Y-m-d', strtotime($date)) : null;
    }

    /**
     * Get the format for datetime attributes.
     */
    protected function formatDateTime(?string $dateTime): ?string
    {
        return $dateTime ? date('Y-m-d H:i:s', strtotime($dateTime)) : null;
    }

    /**
     * Get the format for time attributes.
     */
    protected function formatTime(?string $time): ?string
    {
        return $time ? date('H:i', strtotime($time)) : null;
    }
}
