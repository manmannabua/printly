<?php

use App\Models\Role;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(Tests\TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Shared Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Helper to create a user with a specific role.
 */
function createUserWithRole(Role $role, ?User $assignedBy = null): User
{
    $user = User::factory()->create();
    $user->roles()->attach($role->id, [
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'assigned_by' => $assignedBy?->id ?? $user->id,
        'created_at' => now(),
    ]);
    return $user;
}

/**
 * Helper to attach permissions to a role.
 */
function attachPermissionsToRole(Role $role, array $permissions): void
{
    foreach ($permissions as $permission) {
        $role->permissions()->attach($permission->id, [
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'created_at' => now(),
        ]);
    }
}
