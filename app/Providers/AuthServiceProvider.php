<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Lead;
use App\Models\LeadImport;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\EmailAccount;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Deal;
use App\Models\Task;
use App\Policies\LeadPolicy;
use App\Policies\LeadImportPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\EmailAccountPolicy;
use App\Policies\DealPolicy;
use App\Policies\PipelinePolicy;
use App\Policies\PipelineStagePolicy;
use App\Policies\TaskPolicy;
use App\Policies\WorkspacePolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Lead::class => LeadPolicy::class,
        LeadImport::class => LeadImportPolicy::class,
        Company::class => CompanyPolicy::class,
        Campaign::class => CampaignPolicy::class,
        EmailAccount::class => EmailAccountPolicy::class,
        Pipeline::class => PipelinePolicy::class,
        PipelineStage::class => PipelineStagePolicy::class,
        Deal::class => DealPolicy::class,
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

        // Workspace-level permissions. A workspace owner implicitly holds every
        // permission inside that workspace; other members need the Spatie
        // permission to be assigned. checkPermissionTo() returns false (instead
        // of throwing) when a permission has not been seeded yet.
        $workspaceOwnerOr = function (string $permission): \Closure {
            return function (User $user) use ($permission): bool {
                $workspace = $user->currentWorkspace;

                if ($workspace === null) {
                    return false;
                }

                if ($user->isOwnerOf($workspace)) {
                    return true;
                }

                if ($user->checkPermissionTo($permission)) {
                    return true;
                }

                /*
                 * The settings UI stores a member's role in
                 * workspace_user.role_id instead of Spatie's model_has_roles
                 * pivot. Honour that assignment as well, otherwise invited
                 * members would silently lose every permission.
                 */
                $roleId = $user->workspaces()
                    ->where('workspaces.id', $workspace->id)
                    ->value('role_id');

                if (! $roleId) {
                    return false;
                }

                $role = \Spatie\Permission\Models\Role::find($roleId);

                return $role !== null
                    && $role->permissions()->where('name', $permission)->exists();
            };
        };

        Gate::define('manage workspace', function (User $user) {
            return $user->currentWorkspace !== null
                && $user->isOwnerOf($user->currentWorkspace);
        });

        Gate::define('manage workspace users', $workspaceOwnerOr('manage workspace users'));
        Gate::define('manage pipelines', $workspaceOwnerOr('manage pipelines'));
        Gate::define('create deals', $workspaceOwnerOr('create deals'));
        Gate::define('delete deals', $workspaceOwnerOr('delete deals'));
        Gate::define('create campaigns', $workspaceOwnerOr('create campaigns'));
        Gate::define('manage campaigns', $workspaceOwnerOr('manage campaigns'));
        Gate::define('delete campaigns', $workspaceOwnerOr('delete campaigns'));
        Gate::define('send campaigns', $workspaceOwnerOr('send campaigns'));
        Gate::define('manage email accounts', $workspaceOwnerOr('manage email accounts'));
        Gate::define('export leads', $workspaceOwnerOr('export leads'));
        Gate::define('import leads', $workspaceOwnerOr('import leads'));
        Gate::define('delete leads', $workspaceOwnerOr('delete leads'));
        Gate::define('delete companies', $workspaceOwnerOr('delete companies'));
        Gate::define('delete tasks', $workspaceOwnerOr('delete tasks'));
        Gate::define('manage tasks', $workspaceOwnerOr('manage tasks'));
    }
}