<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * Modules and their CRUD-style actions.
     *
     * @var array<string, array{label: string, actions: array<int, string>}>
     */
    protected array $modules = [
        'stores'        => ['label' => 'Stores',        'actions' => ['view', 'create', 'update', 'delete']],
        'catalog'       => ['label' => 'Catalog',       'actions' => ['view', 'create', 'update', 'delete']],
        'orders'        => ['label' => 'Orders',        'actions' => ['view', 'process', 'refund', 'delete']],
        'printers'      => ['label' => 'Printers',      'actions' => ['view', 'manage']],
        'customers'     => ['label' => 'Customers',     'actions' => ['view']],
        'payments'      => ['label' => 'Payments',      'actions' => ['view', 'refund']],
        'subscriptions' => ['label' => 'Subscriptions', 'actions' => ['view', 'manage']],
        'reports'       => ['label' => 'Reports',       'actions' => ['view']],
        'settings'      => ['label' => 'Settings',      'actions' => ['view', 'update']],
        'users'         => ['label' => 'Users',         'actions' => ['view', 'create', 'update', 'delete', 'activate']],
        'roles'         => ['label' => 'Roles',         'actions' => ['view', 'create', 'update', 'delete']],
        'audit-logs'    => ['label' => 'Audit Logs',    'actions' => ['view']],
    ];

    public function run(): void
    {
        // 1) Permissions
        $permissionIds = [];
        foreach ($this->modules as $module => $config) {
            foreach ($config['actions'] as $action) {
                $name = "{$module}.{$action}";
                $permission = Permission::firstOrCreate(
                    ['name' => $name],
                    [
                        'display_name' => ucfirst($action) . ' ' . $config['label'],
                        'module' => $module,
                        'description' => ucfirst($action) . ' access for ' . $config['label'],
                    ]
                );
                $permissionIds[$name] = $permission->id;
            }
        }

        // 2) Roles
        $admin = Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Administrator',
            'description' => 'Full access to every module (platform owner).',
            'level' => 1,
        ]);

        $owner = Role::firstOrCreate(['name' => 'store_owner'], [
            'display_name' => 'Store Owner',
            'description' => 'Manages their store: catalog, orders, payments, settings and staff.',
            'level' => 2,
        ]);

        $staff = Role::firstOrCreate(['name' => 'staff'], [
            'display_name' => 'Store Staff',
            'description' => 'Works the order queue: view and process orders.',
            'level' => 3,
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer'], [
            'display_name' => 'Viewer',
            'description' => 'Read-only access across store modules.',
            'level' => 5,
        ]);

        // 3) Assign permissions
        // Admin holds every permission (also bypasses checks via the admin role).
        $this->sync($admin, array_keys($permissionIds), $permissionIds);

        // Store owner: full control of their store's catalog, orders, payments,
        // customers, reports, settings + manage staff.
        $ownerPerms = $this->actionsFor([
            'stores', 'catalog', 'orders', 'printers', 'customers', 'payments', 'reports', 'settings',
        ]);
        $ownerPerms[] = 'subscriptions.view';
        $ownerPerms[] = 'users.view';
        $ownerPerms[] = 'users.create';
        $ownerPerms[] = 'users.update';
        $this->sync($owner, $ownerPerms, $permissionIds);

        // Staff: view catalog + work the order queue.
        $staffPerms = [
            'catalog.view',
            'orders.view',
            'orders.process',
            'printers.view',
            'customers.view',
        ];
        $this->sync($staff, $staffPerms, $permissionIds);

        // Viewer: read-only across store-facing modules.
        $viewerPerms = $this->actionsFor(
            ['stores', 'catalog', 'orders', 'printers', 'customers', 'payments', 'reports'],
            ['view']
        );
        $this->sync($viewer, $viewerPerms, $permissionIds);
    }

    /**
     * Build "module.action" names for the given modules (optionally limited to actions).
     *
     * @return array<int, string>
     */
    protected function actionsFor(array $modules, ?array $onlyActions = null): array
    {
        $names = [];
        foreach ($modules as $module) {
            foreach ($this->modules[$module]['actions'] as $action) {
                if ($onlyActions === null || in_array($action, $onlyActions, true)) {
                    $names[] = "{$module}.{$action}";
                }
            }
        }

        return $names;
    }

    /**
     * Sync a role's permissions by permission name.
     */
    protected function sync(Role $role, array $permissionNames, array $permissionIds): void
    {
        $syncData = [];
        foreach (array_unique($permissionNames) as $name) {
            if (isset($permissionIds[$name])) {
                $syncData[$permissionIds[$name]] = ['id' => (string) Str::uuid()];
            }
        }

        $role->permissions()->sync($syncData);
    }
}
