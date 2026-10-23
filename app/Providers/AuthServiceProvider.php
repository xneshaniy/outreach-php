<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Lead;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\EmailAccount;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Deal;
use App\Models\Task;
use App\Policies\LeadPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\EmailAccountPolicy;
use App\Policies\PipelinePolicy;
use App\Policies\TaskPolicy;
use App\Policies\WorkspacePolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Lead::class => LeadPolicy::class,
        Company::class => CompanyPolicy::class,
        Campaign::class => CampaignPolicy::class,
        EmailAccount::class => EmailAccountPolicy::class,
        Pipeline::class => PipelinePolicy::class,
        PipelineStage::class => PipelinePolicy::class,
        Deal::class => PipelinePolicy::class,
        Task::class => TaskPolicy::class,
        Workspace::class => WorkspacePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Super admin can do everything
        Gate::before(function (User $user, string $ability) {
            if ($user->is_super_admin) {
                return true;
            }
            return null;
        });

        // Workspace-level permissions
        Gate::define('manage workspace', function (User $user) {
            return $user->isOwnerOf($user->currentWorkspace);
        });

        Gate::define('manage workspace users', function (User $user) {
            return $user->isOwnerOf($user->currentWorkspace) || $user->hasPermissionTo('manage workspace users');
        });

        Gate::define('manage pipelines', function (User $user) {
            return $user->hasPermissionTo('manage pipelines');
        });

        Gate::define('create deals', function (User $user) {
            return $user->hasPermissionTo('create deals');
        });

        Gate::define('delete deals', function (User $user) {
            return $user->hasPermissionTo('delete deals');
        });

        Gate::define('create campaigns', function (User $user) {
            return $user->hasPermissionTo('create campaigns');
        });

        Gate::define('manage campaigns', function (User $user) {
            return $user->hasPermissionTo('manage campaigns');
        });

        Gate::define('send campaigns', function (User $user) {
            return $user->hasPermissionTo('send campaigns');
        });

        Gate::define('manage email accounts', function (User $user) {
            return $user->hasPermissionTo('manage email accounts');
        });

        Gate::define('export leads', function (User $user) {
            return $user->hasPermissionTo('export leads');
        });

        Gate::define('import leads', function (User $user) {
            return $user->hasPermissionTo('import leads');
        });

        Gate::define('delete leads', function (User $user) {
            return $user->hasPermissionTo('delete leads');
        });

        Gate::define('delete companies', function (User $user) {
            return $user->hasPermissionTo('delete companies');
        });

        Gate::define('delete tasks', function (User $user) {
            return $user->hasPermissionTo('delete tasks');
        });
    }
}