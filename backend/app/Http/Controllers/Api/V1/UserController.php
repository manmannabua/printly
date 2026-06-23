<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\User\AssignRolesRequest;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends BaseController
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with([
            'roles.permissions',
            'directPermissions',
        ])->withCount('roles');

        // Search by email
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('email', 'like', "%{$search}%");
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.name', $request->input('role'));
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['email', 'is_active', 'last_login_at', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        // Paginate
        $perPage = min($request->input('per_page', 15), 100);
        $users = $query->paginate($perPage);

        $users->getCollection()->transform(fn ($user) => new UserResource($user));

        return $this->paginated($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'is_active' => $request->input('is_active', true),
                'email_verified_at' => now(),
            ]);

            if ($request->has('roles')) {
                $this->syncRoles($user, $request->input('roles'));
            }

            return $user;
        });

        AuditLog::log($user, 'created', null, $user->toArray());

        $user->load('roles.permissions');

        return $this->success(
            new UserResource($user),
            'User created successfully.',
            201
        );
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        $user->load('roles.permissions', 'directPermissions');

        return $this->success(new UserResource($user));
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $old = $user->toArray();
        $data = [];

        if ($request->has('email')) {
            $data['email'] = $request->input('email');
        }

        if ($request->has('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        if ($request->has('is_active') && $request->user()->hasPermission('users.activate')) {
            $data['is_active'] = $request->input('is_active');
        }

        if (!empty($data)) {
            $hasPassword = isset($data['password']);
            $user->update($data);

            $newValues = $user->fresh()->toArray();
            if ($hasPassword) {
                $old['password'] = '[changed]';
                $newValues['password'] = '[changed]';
            }
            AuditLog::log($user, 'updated', $old, $newValues);
        }

        $user->load('roles.permissions');

        return $this->success(
            new UserResource($user),
            'User updated successfully.'
        );
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $snapshot = $user->toArray();

        DB::transaction(function () use ($user) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->roles()->detach();
            $user->delete();
        });

        AuditLog::log($user, 'deleted', $snapshot);

        return $this->success(null, 'User deleted successfully.');
    }

    /**
     * Activate or deactivate a user.
     */
    public function activate(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update([
            'is_active' => $request->input('is_active'),
        ]);

        AuditLog::log($user, $user->is_active ? 'activated' : 'deactivated');

        // Revoke all sessions when deactivating to force immediate logout
        if (!$user->is_active) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
        }

        $status = $user->is_active ? 'activated' : 'deactivated';

        return $this->success(
            new UserResource($user),
            "User {$status} successfully."
        );
    }

    /**
     * Assign roles to a user.
     */
    public function assignRoles(AssignRolesRequest $request, User $user): JsonResponse
    {
        $oldRoles = $user->roles()->pluck('name')->toArray();
        $oldDirectCount = $user->directPermissions()->count();

        $this->syncRoles($user, $request->input('roles'));

        // Clear direct permissions so the new role takes full effect.
        if ($oldDirectCount > 0) {
            $user->directPermissions()->detach();
        }

        $newRoles = $user->roles()->pluck('name')->toArray();
        AuditLog::log($user, 'roles_assigned', ['roles' => $oldRoles], ['roles' => $newRoles]);

        $user->load('roles.permissions');

        return $this->success(
            new UserResource($user),
            'Roles assigned successfully.'
        );
    }

    /**
     * Sync user roles with proper pivot data.
     */
    protected function syncRoles(User $user, array $roleIds): void
    {
        $syncData = [];
        $assignedBy = auth()->id();

        foreach ($roleIds as $roleId) {
            $syncData[$roleId] = [
                'id' => Str::uuid()->toString(),
                'assigned_by' => $assignedBy,
                'created_at' => now(),
            ];
        }

        $user->roles()->sync($syncData);
    }
}
