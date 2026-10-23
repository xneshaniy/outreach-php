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

        if (!$user || !$user->currentWorkspace) {
            return redirect()->route('login');
        }

        // Check if user has access to current workspace
        if (!$user->hasAccessToWorkspace($user->currentWorkspace)) {
            abort(403, 'You do not have access to this workspace.');
        }

        // Share workspace with views
        view()->share('currentWorkspace', $user->currentWorkspace);

        return $next($request);
    }
}