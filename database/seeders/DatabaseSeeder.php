<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\EmailAccount;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\Company;
use Spatie\Permission\Models\Role as SpatieRole;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles and permissions are kept in their own idempotent seeder so
        // production can install them without creating demo users or leads.
        $this->call(RolesAndPermissionsSeeder::class);

        $adminRole = SpatieRole::where('name', 'Admin')
            ->where('guard_name', 'web')
            ->firstOrFail();

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

        // Ensure both configured admin email and demo admin exist
        $primaryEmail = env('MAIL_USERNAME', 'admin@outreach.click');
        $emailsToSeed = array_unique(array_filter([$primaryEmail, 'admin@outreach.click', 'admin@example.com']));

        $user = null;
        foreach ($emailsToSeed as $email) {
            $createdUser = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $email === 'admin@example.com' ? 'Demo Admin' : 'Admin User',
                    'password' => bcrypt('password'),
                    'is_active' => true,
                    'is_super_admin' => true,
                ]
            );

            // Attach user to workspace as owner
            $workspace->users()->syncWithoutDetaching([
                $createdUser->id => [
                    'role_id' => $adminRole->id,
                    'is_owner' => true,
                    'is_active' => true,
                    'joined_at' => now(),
                ],
            ]);

            $createdUser->setCurrentWorkspace($workspace);

            if ($user === null) {
                $user = $createdUser;
            }
        }

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

        // Create default email account using configured environment or Hostinger SMTP
        $smtpEmail = env('MAIL_FROM_ADDRESS', env('MAIL_USERNAME', 'admin@outreach.click'));
        EmailAccount::firstOrCreate(
            ['workspace_id' => $workspace->id, 'email' => $smtpEmail],
            [
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'name' => 'Hostinger SMTP',
                'email' => $smtpEmail,
                'normalized_email' => strtolower($smtpEmail),
                'provider' => 'smtp',
                'host' => env('MAIL_HOST', 'smtp.hostinger.com'),
                'port' => (int) env('MAIL_PORT', 465),
                'encryption' => env('MAIL_ENCRYPTION', 'ssl'),
                'username' => env('MAIL_USERNAME', $smtpEmail),
                'password' => env('MAIL_PASSWORD', '@Haseeb786@!$'),
                'from_name' => env('MAIL_FROM_NAME', 'Outreach CRM'),
                'daily_send_limit' => (int) env('CRM_DAILY_SEND_LIMIT', 200),
                'is_active' => true,
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
        $this->command->info('Admin login: admin@outreach.click (or admin@example.com) / password');
    }
}