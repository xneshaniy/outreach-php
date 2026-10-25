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
        $this->ensureWritableStorageDirectories();

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

    /**
     * Recreate the framework's writable directories if a deployment dropped
     * them. Git and ZIP files cannot carry empty directories reliably, and a
     * missing storage/framework/views makes every Blade render fail with
     * "Please provide a valid cache path."
     */
    private function ensureWritableStorageDirectories(): void
    {
        $directories = [
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                // 0755 is the most permissive mode shared hosting accepts
                // safely; never use 0777. Failure is left to the framework to
                // report, since logging may itself need these directories.
                @mkdir($directory, 0755, true);
            }
        }
    }
}