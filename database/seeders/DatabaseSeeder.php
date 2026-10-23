<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\EmailAccount;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\Company;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission as SpatiePermission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
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

        foreach ($permissions as $permission) {
            SpatiePermission::firstOrCreate(['name' => permission, 'guard_name' => 'web']);
        }

        // Create roles
        $adminRole = SpatieRole::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($permissions);

        $managerRole = SpatieRole::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $managerRole->givePermissionTo([
            'create deals',
            'create campaigns',
            'manage campaigns',
            'send campaigns',
            'manage email accounts',
            'export leads',
            'import leads',
            'manage tasks',
        ]);

        $userRole = SpatieRole::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $userRole->givePermissionTo([
            'create deals',
            'create campaigns',
            'send campaigns',
            'export leads',
            'manage tasks',
        ]);

        // Create demo user
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'is_active' => true,
                'is_super_admin' => true,
            ]
        );

        // Create workspace
        $workspace = Workspace::firstOrCreate(
            ['slug' => 'demo-workspace'],
            [
                'name' => 'Demo Workspace',
                'description' => 'Default workspace for demonstration',
                'primary_color' => '#1e3a5f',
                'secondary_color' => '#0d9488',
                'is_active' => true,
            ]
        );

        // Attach user to workspace as owner
        $workspace->users()->syncWithoutDetaching([
            $user->id => [
                'role_id' => $adminRole->id,
                'is_owner' => true,
                'is_active' => true,
                'joined_at' => now(),
            ],
        ]);

        $user->setCurrentWorkspace($workspace);

        // Create default pipeline
        $pipeline = Pipeline::firstOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Default Sales Pipeline'],
            [
                'workspace_id' => $workspace->id,
                'created_by' => $user->id,
                'name' => 'Default Sales Pipeline',
                'description' => 'Standard sales pipeline',
                'is_default' => true,
            ]
        );

        // Create pipeline stages
        $stages = [
            ['name' => 'New', 'color' => '#6366f1', 'order' => 1, 'probability' => 10],
            ['name' => 'Contacted', 'color' => '#0ea5e9', 'order' => 2, 'probability' => 25],
            ['name' => 'Qualified', 'color' => '#22c55e', 'order' => 3, 'probability' => 50],
            ['name' => 'Proposal', 'color' => '#f59e0b', 'order' => 4, 'probability' => 75],
            ['name' => 'Won', 'color' => '#10b981', 'order' => 5, 'probability' => 100, 'is_won' => true, 'is_closed' => true],
            ['name' => 'Lost', 'color' => '#ef4444', 'order' => 6, 'probability' => 0, 'is_lost' => true, 'is_closed' => true],
        ];

        foreach ($stages as $stage) {
            PipelineStage::firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'name' => $stage['name']],
                array_merge($stage, ['pipeline_id' => $pipeline->id])
            );
        }

        // Create sample email account (placeholder - user needs to configure)
        EmailAccount::firstOrCreate(
            ['workspace_id' => $workspace->id, 'email' => 'noreply@your-domain.com'],
            [
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'name' => 'Default Email',
                'email' => 'noreply@your-domain.com',
                'normalized_email' => 'noreply@your-domain.com',
                'provider' => 'smtp',
                'host' => 'smtp.your-provider.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'noreply@your-domain.com',
                'password' => 'your-password',
                'from_name' => 'Outreach CRM',
                'daily_send_limit' => 200,
                'is_active' => false,
                'is_default' => true,
            ]
        );

        // Create sample lead list
        $leadList = LeadList::firstOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'All Leads'],
            [
                'workspace_id' => $workspace->id,
                'created_by' => $user->id,
                'name' => 'All Leads',
                'description' => 'Main lead list',
            ]
        );

        // Create sample companies
        $companies = [
            ['name' => 'Acme Corporation', 'domain' => 'acme.com', 'industry' => 'Technology', 'size' => '501-1000', 'country' => 'USA', 'city' => 'San Francisco'],
            ['name' => 'Globex Inc', 'domain' => 'globex.com', 'industry' => 'Manufacturing', 'size' => '201-500', 'country' => 'USA', 'city' => 'New York'],
            ['name' => 'Wayne Enterprises', 'domain' => 'wayne.com', 'industry' => 'Conglomerate', 'size' => '1000+', 'country' => 'USA', 'city' => 'Gotham'],
            ['name' => 'Stark Industries', 'domain' => 'stark.com', 'industry' => 'Defense', 'size' => '1000+', 'country' => 'USA', 'city' => 'New York'],
            ['name' => 'Umbrella Corp', 'domain' => 'umbrella.com', 'industry' => 'Pharmaceuticals', 'size' => '501-1000', 'country' => 'USA', 'city' => 'Raccoon City'],
        ];

        foreach ($companies as $companyData) {
            $company = Company::firstOrCreate(
                ['workspace_id' => $workspace->id, 'domain' => $companyData['domain']],
                array_merge($companyData, [
                    'workspace_id' => $workspace->id,
                    'created_by' => $user->id,
                ])
            );

            // Create sample leads for each company
            $firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'Robert', 'Lisa'];
            $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
            $titles = ['CEO', 'CTO', 'VP Sales', 'Director of Marketing', 'Head of Operations', 'Procurement Manager', 'IT Director', 'Finance Director'];

            for ($i = 0; $i < 3; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $email = strtolower($firstName . '.' . $lastName . '@' . $companyData['domain']);

                Lead::firstOrCreate(
                    ['workspace_id' => $workspace->id, 'normalized_email' => $email],
                    [
                        'workspace_id' => $workspace->id,
                        'company_id' => $company->id,
                        'owner_id' => $user->id,
                        'created_by' => $user->id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'normalized_email' => $email,
                        'phone' => '+1-555-' . rand(100, 999) . '-' . rand(1000, 9999),
                        'job_title' => $titles[array_rand($titles)],
                        'country' => $companyData['country'],
                        'city' => $companyData['city'],
                        'source' => 'demo',
                        'status' => ['new', 'contacted', 'qualified'][array_rand(['new', 'contacted', 'qualified'])],
                        'tags' => ['demo', 'sample'],
                        'follow_up_at' => now()->addDays(rand(1, 14)),
                    ]
                );
            }

            // Add leads to list
            $leadIds = Lead::where('workspace_id', $workspace->id)
                ->where('company_id', $company->id)
                ->pluck('id');

            $leadList->leads()->syncWithoutDetaching($leadIds->mapWithKeys(fn($id) => [$id => ['added_by' => $user->id]])->toArray());
        }

        // Create sample campaign
        $campaign = \App\Models\Campaign::firstOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Welcome Sequence'],
            [
                'workspace_id' => $workspace->id,
                'created_by' => $user->id,
                'email_account_id' => null,
                'lead_list_id' => $leadList->id,
                'name' => 'Welcome Sequence',
                'description' => 'Automated welcome email sequence for new leads',
                'status' => \App\Models\Campaign::STATUS_DRAFT,
                'timezone' => 'UTC',
                'daily_send_limit' => 50,
                'delay_between_emails' => 3600,
            ]
        );

        // Create campaign steps
        $steps = [
            [
                'step_order' => 1,
                'name' => 'Welcome Email',
                'subject' => 'Welcome to {{company_name}}, {{first_name}}!',
                'body_html' => '<p>Hi {{first_name}},</p><p>Welcome to {{company_name}}! We\'re excited to have you on board.</p><p>Best regards,<br>The Team</p>',
                'delay_days' => 0,
                'delay_hours' => 0,
                'delay_minutes' => 0,
            ],
            [
                'step_order' => 2,
                'name' => 'Follow-up: Value Proposition',
                'subject' => 'How {{company_name}} can help {{company_name}}',
                'body_html' => '<p>Hi {{first_name}},</p><p>I wanted to follow up and share how we\'ve helped companies like {{company_name}} achieve their goals.</p><p>Best regards,<br>The Team</p>',
                'delay_days' => 2,
                'delay_hours' => 0,
                'delay_minutes' => 0,
            ],
            [
                'step_order' => 3,
                'name' => 'Final Follow-up',
                'subject' => 'Quick question for {{first_name}}',
                'body_html' => '<p>Hi {{first_name}},</p><p>Just checking in one more time. Would you be open to a brief call to discuss how we can help?</p><p>Best regards,<br>The Team</p>',
                'delay_days' => 5,
                'delay_hours' => 0,
                'delay_minutes' => 0,
            ],
        ];

        foreach ($steps as $step) {
            \App\Models\CampaignStep::firstOrCreate(
                ['campaign_id' => $campaign->id, 'step_order' => $step['step_order']],
                array_merge($step, ['campaign_id' => $campaign->id, 'is_active' => true])
            );
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Demo login: admin@example.com / password');
    }
}