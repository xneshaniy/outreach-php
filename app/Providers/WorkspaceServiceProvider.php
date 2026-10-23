<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Workspace;

class WorkspaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Share workspace navigation with all views
        View::composer('layouts.navigation', function ($view) {
            if (auth()->check()) {
                $workspaces = auth()->user()->activeWorkspaces()->get();
                $view->with('navWorkspaces', $workspaces);
            }
        });

        // Global workspace context
        View::composer('*', function ($view) {
            if (auth()->check()) {
                $workspace = auth()->user()->currentWorkspace;
                $view->with('workspace', $workspace);

                if ($workspace) {
                    $view->with('workspaceSettings', WorkspaceSetting::where('workspace_id', $workspace->id)
                        ->where('is_public', true)
                        ->get()
                        ->keyBy('key')
                        ->map(fn($s) => $s->getTypedValue())
                        ->toArray());
                }
            }
        });
    }
}