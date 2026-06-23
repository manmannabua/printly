<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Role\AssignPermissionsRequest;
use App\Http\Requests\Role\CreateRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermissionAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleController extends BaseController
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::withCount(['permissions', 'users']);

        // Search by name or display_name
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->input('level'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'level');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['name', 'display_name', 'level', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        // Paginate or get all
        if ($request->input('all', false)) {
            $roles = $query->get();
            return $this->success(RoleResource::collection($roles));
        }

        $perPage = min($request->input('per_page', 15), 100);
        $roles = $query->paginate($perPage);

        return $this->paginated($roles, RoleResource::class);
    }

    /**
     * Store a newly created role.
     */
    public function store(CreateRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->input('name'),
            'display_name' => $request->input('display_name'),
            'description' => $request->input('description'),
            'level' => $request->input('level'),
        ]);

        AuditLog::log($role, 'created', null, $role->toArray());

        $role->loadCount(['permissions', 'users']);

        return $this->success(
            new RoleResource($role),
            'Role created successfully.',
            201
        );
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): JsonResponse
    {
        $role->load(['permissions', 'users']);
        $role->loadCount('users');

        return $this->success(new RoleResource($role));
    }

    /**
     * Add a user to a role.
     */
    public function addUser(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
        ]);

        $user = \App\Models\User::findOrFail($request->input('user_id'));

        if ($user->roles()->where('roles.id', $role->id)->exists()) {
            return $this->error('User already has this role.', 409);
        }

        $user->roles()->attach($role->id, [
            'id' => Str::uuid()->toString(),
            'assigned_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        AuditLog::log($user, 'roles_assigned',
            ['roles' => $user->roles()->pluck('name')->filter(fn ($n) => $n !== $role->name)->values()->toArray()],
            ['roles' => $user->roles()->pluck('name')->values()->toArray()]
        );

        $role->load(['users']);
        $role->loadCount('users');

        return $this->success(new RoleResource($role), 'User added to role.');
    }

    /**
     * Remove a user from a role.
     */
    public function removeUser(Role $role, \App\Models\User $user): JsonResponse
    {
        $user->roles()->detach($role->id);

        AuditLog::log($user, 'roles_assigned',
            ['roles' => [$role->name]],
            ['roles' => $user->fresh()->roles()->pluck('name')->values()->toArray()]
        );

        $role->load(['users']);
        $role->loadCount('users');

        return $this->success(new RoleResource($role), 'User removed from role.');
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $old = $role->toArray();
        $data = $request->only(['name', 'display_name', 'description', 'level']);
        $data = array_filter($data, fn ($value) => $value !== null);

        if (!empty($data)) {
            $role->update($data);
            AuditLog::log($role, 'updated', $old, $role->fresh()->toArray());
        }

        $role->load('permissions');
        $role->loadCount('users');

        return $this->success(
            new RoleResource($role),
            'Role updated successfully.'
        );
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->users()->count() > 0) {
            return $this->error(
                'Cannot delete role with assigned users. Remove all users from this role first.',
                409
            );
        }

        $snapshot = $role->toArray();

        DB::transaction(function () use ($role) {
            // Remove permission assignments
            $role->permissions()->detach();

            // Delete role
            $role->delete();
        });

        AuditLog::log($role, 'deleted', $snapshot);

        return $this->success(null, 'Role deleted successfully.');
    }

    /**
     * Assign permissions to a role.
     */
    public function assignPermissions(AssignPermissionsRequest $request, Role $role): JsonResponse
    {
        $permissionIds = $request->input('permissions', []);

        // Privilege escalation guard: non-admin cannot assign permissions they don't have
        $user = $request->user();
        if (!$user->is_admin && !empty($permissionIds)) {
            $userPermissionIds = $user->getAllPermissions()->pluck('id')->toArray();
            $disallowed = array_diff($permissionIds, $userPermissionIds);

            if (!empty($disallowed)) {
                $disallowedNames = Permission::whereIn('id', $disallowed)
                    ->pluck('name')
                    ->toArray();

                return $this->error(
                    'You cannot assign permissions you do not possess: ' . implode(', ', $disallowedNames),
                    422
                );
            }
        }

        // Snapshot current permissions before sync
        $currentPermissions = $role->permissions()->pluck('name', 'permissions.id');
        $currentIds = $currentPermissions->keys()->all();

        $requestedIds = array_unique($permissionIds);

        $addedIds = array_diff($requestedIds, $currentIds);
        $removedIds = array_diff($currentIds, $requestedIds);

        DB::transaction(function () use ($role, $requestedIds, $addedIds, $removedIds, $currentPermissions, $request) {
            $syncData = [];
            foreach ($requestedIds as $permissionId) {
                $syncData[$permissionId] = [
                    'id' => Str::uuid()->toString(),
                    'created_at' => now(),
                ];
            }

            $role->permissions()->sync($syncData);

            // Only create audit record if permissions actually changed
            if (!empty($addedIds) || !empty($removedIds)) {
                $addedNames = !empty($addedIds)
                    ? Permission::whereIn('id', $addedIds)->pluck('name')->all()
                    : [];

                $removedNames = !empty($removedIds)
                    ? $currentPermissions->only($removedIds)->values()->all()
                    : [];

                RolePermissionAudit::create([
                    'role_id' => $role->id,
                    'actor_id' => $request->user()->id,
                    'added_permissions' => $addedNames,
                    'removed_permissions' => $removedNames,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        });

        $role->load('permissions');
        $role->loadCount('users');

        return $this->success(
            new RoleResource($role),
            'Permissions assigned successfully.'
        );
    }
}
