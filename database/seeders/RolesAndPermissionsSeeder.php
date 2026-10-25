<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Creates the global roles and permissions used by the settings UI.
 *
 * This seeder is safe to run repeatedly and does not create users or demo
 * records. Production should run this class only:
 *
 *   php artisan db:seed --class=RolesAndPermissionsSeeder --force
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'manage workspace',
        'manage workspace users',
        'manage pipelines',
        'create deals',
        'delete deals',
        'create campaigns',
        'manage campaigns',
        'send campaigns',
        'manage email accounts',
        'export leads',
        'import leads',
        'delete leads',
        'delete companies',
        'delete tasks',
        'manage tasks',
    ];

    public const ROLES = [
        'Admin' => self::PERMISSIONS,
        'Manager' => [
            'create deals',
            'create campaigns',
            'manage campaigns',
            'send campaigns',
            'manage email accounts',
            'export leads',
            'import leads',
            'manage tasks',
        ],
        'User' => [
            'create deals',
            'create campaigns',
            'send campaigns',
            'export leads',
            'manage tasks',
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach (self::ROLES as $name => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);
        }
    }
}
