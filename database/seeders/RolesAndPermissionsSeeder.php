<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Creates the global roles and permissions used by Outreach.click CRM.
 *
 * This seeder is safe to run repeatedly and does not create users or demo
 * records. Production can run this class safely:
 *
 *   php artisan db:seed --class=RolesAndPermissionsSeeder --force
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Complete granular permission matrix across all CRM modules:
     * Actions: view, create, edit, delete, download, export, share, manage
     */
    public const PERMISSIONS = [
        // 1. Contacts & Companies
        'view contacts',
        'create contacts',
        'edit contacts',
        'delete contacts',
        'export contacts',
        'delete companies',

        // 2. Leads & Lists
        'view leads',
        'create leads',
        'edit leads',
        'delete leads',
        'export leads',
        'import leads',

        // 3. Email Accounts & Mailboxes
        'view email accounts',
        'create email accounts',
        'edit email accounts',
        'delete email accounts',
        'manage email accounts',

        // 4. Campaigns & Sequences
        'view campaigns',
        'create campaigns',
        'edit campaigns',
        'delete campaigns',
        'send campaigns',
        'manage campaigns',

        // 5. Templates
        'view templates',
        'create templates',
        'edit templates',
        'delete templates',

        // 6. Follow-ups & Reminders
        'view follow-ups',
        'manage follow-ups',

        // 7. Google Sheets Integration
        'view google sheets',
        'sync google sheets',
        'manage google sheets',

        // 8. Reports & Analytics
        'view reports',
        'export reports',
        'download reports',

        // 9. Automations & AI
        'view automations',
        'manage automations',

        // 10. Workspaces
        'view workspaces',
        'manage workspaces',
        'manage workspace users',

        // 11. Clients (Client Portal)
        'view clients',
        'manage clients',

        // 12. Users & Team
        'view users',
        'manage users',

        // 13. Billing & Usage
        'view billing',
        'manage billing',

        // 14. Settings
        'view settings',
        'manage settings',

        // Pipeline, Deals & Tasks (CRM operations)
        'manage pipelines',
        'create deals',
        'delete deals',
        'manage tasks',
        'delete tasks',
    ];

    public static function getRolesDefinition(): array
    {
        $all = self::PERMISSIONS;

        $workspaceAdmin = $all; // All workspace permissions

        $manager = [
            'view contacts', 'create contacts', 'edit contacts', 'export contacts',
            'view leads', 'create leads', 'edit leads', 'delete leads', 'export leads', 'import leads',
            'view email accounts', 'manage email accounts',
            'view campaigns', 'create campaigns', 'edit campaigns', 'send campaigns', 'manage campaigns',
            'view templates', 'create templates', 'edit templates',
            'view follow-ups', 'manage follow-ups',
            'view google sheets', 'sync google sheets', 'manage google sheets',
            'view reports', 'export reports', 'download reports',
            'view automations', 'manage automations',
            'view workspaces',
            'view clients', 'manage clients',
            'view users',
            'view settings',
            'manage pipelines', 'create deals', 'delete deals', 'manage tasks', 'delete tasks',
        ];

        $campaignOperator = [
            'view contacts', 'create contacts', 'edit contacts',
            'view leads', 'create leads', 'edit leads', 'export leads', 'import leads',
            'view email accounts',
            'view campaigns', 'create campaigns', 'edit campaigns', 'send campaigns', 'manage campaigns',
            'view templates', 'create templates', 'edit templates',
            'view follow-ups', 'manage follow-ups',
            'view google sheets', 'sync google sheets',
            'view reports',
            'manage tasks',
        ];

        $analyst = [
            'view contacts',
            'view leads', 'export leads',
            'view email accounts',
            'view campaigns',
            'view templates',
            'view google sheets',
            'view reports', 'export reports', 'download reports',
            'view billing',
        ];

        $client = [
            'view contacts',
            'view campaigns',
            'view reports',
        ];

        $user = [
            'view contacts', 'create contacts', 'edit contacts',
            'view leads', 'create leads', 'edit leads', 'export leads',
            'view email accounts',
            'view campaigns', 'create campaigns', 'send campaigns',
            'view templates',
            'view google sheets', 'sync google sheets',
            'view reports',
            'create deals', 'manage tasks',
        ];

        return [
            'Super Admin' => $all,
            'Workspace Admin' => $workspaceAdmin,
            'Admin' => $workspaceAdmin, // Backward compatibility alias
            'Manager' => $manager,
            'Campaign Operator' => $campaignOperator,
            'Analyst' => $analyst,
            'Client' => $client,
            'User' => $user,
        ];
    }

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach (self::getRolesDefinition() as $name => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);
        }
    }
}
