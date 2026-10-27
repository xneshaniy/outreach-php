<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'appName' => config('app.name'),
            'appDescription' => config('crm.description', 'Outreach CRM'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? null,
                    'is_super_admin' => (bool) ($user->is_super_admin ?? false),
                ] : null,
            ],
            'currentWorkspace' => fn () => $user && $user->currentWorkspace ? [
                'id' => $user->currentWorkspace->id,
                'name' => $user->currentWorkspace->name,
                'slug' => $user->currentWorkspace->slug,
            ] : null,
            'notifications' => fn () => $user && $user->currentWorkspace ? \App\Models\CrmNotification::where('workspace_id', $user->currentWorkspace->id)
                ->whereNull('read_at')
                ->latest()
                ->limit(10)
                ->get(['id', 'type', 'title', 'message', 'created_at'])
                ->map(fn($n) => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'message' => $n->message,
                    'time' => $n->created_at->diffForHumans(),
                    'read' => false,
                ]) : [],
            'unreadNotificationsCount' => fn () => $user && $user->currentWorkspace
                ? \App\Models\CrmNotification::where('workspace_id', $user->currentWorkspace->id)->whereNull('read_at')->count()
                : 0,
            'navWorkspaces' => fn () => $user
                ? $user->activeWorkspaces()->get(['workspaces.id', 'workspaces.name', 'workspaces.slug'])->toArray()
                : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'message' => fn () => $request->session()->get('message'),
                'invitationSuccess' => fn () => $request->session()->get('invitationSuccess'),
            ],
        ]);
    }
}
