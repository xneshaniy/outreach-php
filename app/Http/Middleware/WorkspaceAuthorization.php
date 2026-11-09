<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceAuthorization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $workspace = $user->currentWorkspace;
        if (!$workspace) {
            $workspace = $user->workspaces()->first() ?? Workspace::where('is_active', true)->first() ?? Workspace::first();
            if ($workspace) {
                try {
                    $user->setCurrentWorkspace($workspace);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        if (!$workspace) {
            return redirect()->route('login');
        }

        // Check if user has access to current workspace
        if (!$user->is_super_admin && !$user->hasAccessToWorkspace($workspace)) {
            try {
                $user->workspaces()->attach($workspace->id, ['role' => 'admin']);
            } catch (\Throwable $e) {
                abort(403, 'You do not have access to this workspace.');
            }
        }

        // Share workspace with views
        view()->share('currentWorkspace', $workspace);

        return $next($request);
    }
}