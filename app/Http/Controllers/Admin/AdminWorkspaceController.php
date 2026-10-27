<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
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
}
