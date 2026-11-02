<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLoginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (!$workspace) {
            $workspace = $user->activeWorkspaces()->first();
            if ($workspace) {
                $user->setCurrentWorkspace($workspace);
            }
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showRegisterForm(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        // Create personal workspace
        $workspace = Workspace::create([
            'name' => $user->name . '\'s Workspace',
            'slug' => Str::slug($user->name . '-workspace') . '-' . Str::random(6),
            'created_by' => $user->id,
        ]);

        $workspace->users()->attach($user->id, [
            'role_id' => null,
            'is_owner' => true,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $user->setCurrentWorkspace($workspace);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showWorkspaceSwitcher(): Response
    {
        $user = auth()->user();

        // Super admin sees every active workspace; regular users see their own.
        if ($user->is_super_admin) {
            $workspaces = Workspace::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'primary_color']);
        } else {
            $workspaces = $user->activeWorkspaces()
                ->where('workspaces.is_active', true)
                ->orderBy('workspaces.name')
                ->get(['workspaces.id', 'workspaces.name', 'workspaces.slug', 'workspaces.primary_color']);
        }

        return Inertia::render('Auth/WorkspaceSwitcher', [
            'workspaces' => $workspaces,
            'current'    => $user->currentWorkspace ? $user->currentWorkspace->only(['id', 'name', 'slug']) : null,
        ]);
    }

    public function switchWorkspace(Request $request): RedirectResponse
    {
        $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
        ]);

        $workspace = Workspace::findOrFail($request->workspace_id);
        $user      = $request->user();

        if (!$user->hasAccessToWorkspace($workspace)) {
            abort(403, 'You do not have access to this workspace.');
        }

        $user->setCurrentWorkspace($workspace);

        // Store in session so currentWorkspace() picks it up immediately on
        // the very next request (before last_accessed_at ordering catches up).
        session(['current_workspace_id' => $workspace->id]);

        return redirect()->route('dashboard')->with('success', 'Switched to ' . $workspace->name);
    }
}