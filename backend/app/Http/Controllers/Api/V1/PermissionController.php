<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends BaseController
{
    /**
     * Display a listing of permissions, grouped by module.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query();

        // Filter by module
        if ($request->has('module')) {
            $query->where('module', $request->input('module'));
        }

        // Search by name or display_name
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

        // Sort within groups
        $query->orderBy('module')->orderBy('name');

        $permissions = $query->get();

        // Group by module if requested (default: true)
        if ($request->input('grouped', true)) {
            $grouped = $permissions->groupBy('module')->map(function ($modulePermissions, $module) {
                return [
                    'module' => $module,
                    'display_name' => $this->getModuleDisplayName($module),
                    'permissions' => PermissionResource::collection($modulePermissions),
                ];
            })->values();

            return $this->success($grouped);
        }

        // Flat list
        return $this->success(PermissionResource::collection($permissions));
    }

    /**
     * Get a human-readable display name for a module.
     */
    protected function getModuleDisplayName(string $module): string
    {
        $displayNames = [
            'stores' => 'Stores',
            'catalog' => 'Catalog',
            'orders' => 'Orders',
            'customers' => 'Customers',
            'payments' => 'Payments',
            'subscriptions' => 'Subscriptions',
            'reports' => 'Reports',
            'settings' => 'Settings',
            'users' => 'User Management',
            'roles' => 'Role Management',
            'permissions' => 'Permissions',
            'audit-logs' => 'Audit Logs',
        ];

        return $displayNames[$module] ?? ucwords(str_replace('-', ' ', $module));
    }
}
