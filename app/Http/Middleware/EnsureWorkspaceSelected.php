<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Super admins can access any workspace. If they have no current workspace,
            // auto-select the first active workspace so CRM pages don't break.
            if ($user->is_super_admin) {
                if (!$user->currentWorkspace) {
                    $workspace = Workspace::where('is_active', true)->orderBy('name')->first();
                    if ($workspace) {
                        $user->setCurrentWorkspace($workspace);
                    }
                }
                return $next($request);
            }

            // If user has no current workspace but has workspaces, select the first active one
            if (!$user->currentWorkspace && $user->activeWorkspaces()->exists()) {
                $workspace = $user->activeWorkspaces()->first();
                $user->setCurrentWorkspace($workspace);
            }

            // If user has a current workspace but it's inactive, try to find another
            if ($user->currentWorkspace && !$user->currentWorkspace->is_active) {
                $workspace = $user->activeWorkspaces()->where('workspaces.is_active', true)->first();
                if ($workspace) {
                    $user->setCurrentWorkspace($workspace);
                } else {
                    // No active workspace available
                    Auth::logout();
                    return redirect()->route('login')->with('error', 'No active workspace available.');
                }
            }
        }

        return $next($request);
    }
}
