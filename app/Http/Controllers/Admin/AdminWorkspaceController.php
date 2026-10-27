<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminWorkspaceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Workspace::withCount(['leads', 'users', 'campaigns'])
            ->with(['owners:users.id,users.name,users.email'])
            ->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $active = $status === 'active';
            $query->where('is_active', $active);
        }

        $workspaces = $query->paginate(20)->withQueryString();

        $workspacesData = $workspaces->through(fn($ws) => [
            'id'              => $ws->id,
            'name'            => $ws->name,
            'slug'            => $ws->slug,
            'is_active'       => $ws->is_active,
            'leads_count'     => $ws->leads_count,
            'users_count'     => $ws->users_count,
            'campaigns_count' => $ws->campaigns_count,
            'created_at'      => $ws->created_at?->toIso8601String(),
            'owner'           => $ws->owners->first()
                ? ['id' => $ws->owners->first()->id, 'name' => $ws->owners->first()->name, 'email' => $ws->owners->first()->email]
                : null,
        ]);

        return Inertia::render('Admin/Workspaces/Index', [
            'workspaces' => $workspacesData,
            'filters'    => $request->only(['search', 'status']),
            'summary'    => [
                'total'    => Workspace::count(),
                'active'   => Workspace::where('is_active', true)->count(),
                'inactive' => Workspace::where('is_active', false)->count(),
            ],
        ]);
    }

    public function show(Workspace $workspace): Response
    {
        $workspace->loadCount(['leads', 'users', 'campaigns', 'companies', 'emailAccounts']);

        $members = $workspace->users()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar_path', 'users.is_active')
            ->withPivot('role_id', 'is_owner', 'is_active', 'joined_at', 'last_accessed_at')
            ->orderByDesc('workspace_user.is_owner')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_active' => $u->is_active,
                'is_owner' => (bool) $u->pivot->is_owner,
                'joined_at' => $u->pivot->joined_at,
                'last_accessed_at' => $u->pivot->last_accessed_at,
            ]);

        $recentLeads = $workspace->leads()
            ->with(['company:id,name', 'owner:id,name'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->map(fn($l) => [
                'id' => $l->id,
                'first_name' => $l->first_name,
                'last_name' => $l->last_name,
                'email' => $l->email,
                'phone' => $l->phone,
                'job_title' => $l->job_title,
                'score' => $l->score,
                'status' => $l->status,
                'category' => $l->category,
                'company' => $l->company ? ['id' => $l->company->id, 'name' => $l->company->name] : null,
                'owner' => $l->owner ? ['id' => $l->owner->id, 'name' => $l->owner->name] : null,
                'created_at' => $l->created_at?->toIso8601String(),
            ]);

        $recentCampaigns = $workspace->campaigns()
            ->with('creator:id,name')
            ->withCount('recipients')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
                'recipients_count' => $c->recipients_count,
                'creator' => $c->creator ? ['id' => $c->creator->id, 'name' => $c->creator->name] : null,
                'created_at' => $c->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Workspaces/Show', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'description' => $workspace->description,
                'is_active' => $workspace->is_active,
                'created_at' => $workspace->created_at?->toIso8601String(),
                'leads_count' => $workspace->leads_count,
                'users_count' => $workspace->users_count,
                'campaigns_count' => $workspace->campaigns_count,
                'companies_count' => $workspace->companies_count,
                'email_accounts_count' => $workspace->email_accounts_count,
            ],
            'members' => $members,
            'recentLeads' => $recentLeads,
            'recentCampaigns' => $recentCampaigns,
        ]);
    }

    public function toggleStatus(Workspace $workspace): RedirectResponse
    {
        $workspace->update(['is_active' => !$workspace->is_active]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($workspace)
            ->log('Workspace ' . $workspace->name . ' status toggled to ' . ($workspace->is_active ? 'active' : 'inactive') . ' by Admin');

        return back()->with('success', 'Workspace status updated successfully.');
    }
}
