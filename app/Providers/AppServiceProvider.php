<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use App\Models\Workspace;
use App\Models\WorkspaceSetting;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrapFive();

        // Share current workspace with all views
        View::composer('*', function ($view) {
            if (auth()->check()) {
                $view->with('currentWorkspace', auth()->user()->currentWorkspace);
            }
        });

        // Register custom config
        config([
            'crm.token_fallbacks' => [
                'first_name' => 'there',
                'last_name' => '',
                'full_name' => 'there',
                'company_name' => 'your company',
                'company_domain' => 'your company',
                'job_title' => 'your role',
            ],
            'crm.message_retention_days' => 365,
            'crm.audit_log_retention_days' => 365,
            'crm.activity_retention_days' => 180,
            'crm.import_retention_days' => 90,
        ]);
    }
}