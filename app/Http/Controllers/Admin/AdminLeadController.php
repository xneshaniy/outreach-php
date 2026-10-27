<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminLeadController extends Controller
{
    public function index(Request $request): Response
    {
        // Fetch all workspaces with their owners (pivot is_owner=true) and lead counts
        $workspaces = Workspace::withCount('leads')
            ->with(['owners:users.id,users.name,users.email'])
            ->orderBy('name')
            ->get()
            ->map(fn($ws) => [
                'id'          => $ws->id,
                'name'        => $ws->name,
                'slug'        => $ws->slug,
                'is_active'   => $ws->is_active,
                'leads_count' => $ws->leads_count,
                'owner'       => $ws->owners->first()
                    ? ['id' => $ws->owners->first()->id, 'name' => $ws->owners->first()->name, 'email' => $ws->owners->first()->email]
                    : null,
            ]);

        // Build leads query — scoped by workspace if requested
        $query = Lead::with([
            'workspace:id,name,slug',
            'owner:id,name,email',
            'creator:id,name,email',
            'company:id,name',
        ])->withTrashed(false); // exclude soft-deleted

        $workspaceId = $request->input('workspace_id');
        if ($workspaceId && $workspaceId !== 'all') {
            $query->where('workspace_id', (int) $workspaceId);
        }

        // Search: name, email, phone, job_title, company
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('job_title', 'like', "%{$search}%")
                  ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($stage = $request->input('pipeline_stage')) {
            $query->where('pipeline_stage', $stage);
        }

        // Sorting
        $allowedSorts = ['first_name', 'last_name', 'email', 'score', 'status', 'created_at', 'workspace_id'];
        $sortField = in_array($request->input('sort_field'), $allowedSorts, true)
            ? $request->input('sort_field')
            : 'created_at';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortDir);

        $leads = $query->paginate(25)->withQueryString();

        // Transform leads: never expose password, tokens, or secrets
        $leadsData = $leads->through(fn($lead) => [
            'id'             => $lead->id,
            'first_name'     => $lead->first_name,
            'last_name'      => $lead->last_name,
            'email'          => $lead->email,
            'phone'          => $lead->phone,
            'job_title'      => $lead->job_title,
            'status'         => $lead->status,
            'category'       => $lead->category,
            'pipeline_stage' => $lead->pipeline_stage,
            'score'          => $lead->score,
            'source'         => $lead->source,
            'country'        => $lead->country,
            'city'           => $lead->city,
            'notes'          => $lead->notes,
            'email_verified' => $lead->email_verified,
            'created_at'     => $lead->created_at?->toIso8601String(),
            'workspace'      => $lead->workspace ? ['id' => $lead->workspace->id, 'name' => $lead->workspace->name] : null,
            'company'        => $lead->company  ? ['id' => $lead->company->id,  'name' => $lead->company->name]  : null,
            'owner'          => $lead->owner    ? ['id' => $lead->owner->id,    'name' => $lead->owner->name, 'email' => $lead->owner->email]    : null,
            'creator'        => $lead->creator  ? ['id' => $lead->creator->id,  'name' => $lead->creator->name, 'email' => $lead->creator->email]  : null,
        ]);

        // Summary counts scoped to same workspace filter
        $summaryBase = Lead::query();
        if ($workspaceId && $workspaceId !== 'all') {
            $summaryBase->where('workspace_id', (int) $workspaceId);
        }
        $summaryTotal      = (clone $summaryBase)->count();
        $summaryHot        = (clone $summaryBase)->where('category', 'hot')->count();
        $summaryVerified   = (clone $summaryBase)->where('email_verified', true)->count();
        $summaryWorkspaces = $workspaces->count();

        return Inertia::render('Admin/Leads/Index', [
            'workspaces'       => $workspaces,
            'leads'            => $leadsData,
            'filters'          => $request->only(['workspace_id', 'search', 'status', 'category', 'pipeline_stage', 'sort_field', 'sort_dir']),
            'summary'          => [
                'total_leads'       => $summaryTotal,
                'hot_leads'         => $summaryHot,
                'verified_emails'   => $summaryVerified,
                'total_workspaces'  => $summaryWorkspaces,
            ],
        ]);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->load([
            'workspace:id,name,slug',
            'company:id,name,website,industry,size,country',
            'owner:id,name,email',
            'creator:id,name,email',
        ]);

        return response()->json([
            'lead' => [
                'id' => $lead->id,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'full_name' => $lead->full_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'mobile' => $lead->mobile,
                'job_title' => $lead->job_title,
                'department' => $lead->department,
                'seniority' => $lead->seniority,
                'status' => $lead->status,
                'category' => $lead->category,
                'pipeline_stage' => $lead->pipeline_stage,
                'score' => $lead->score,
                'source' => $lead->source,
                'country' => $lead->country,
                'city' => $lead->city,
                'state' => $lead->state,
                'timezone' => $lead->timezone,
                'website' => $lead->website,
                'industry' => $lead->industry,
                'notes' => $lead->notes,
                'email_verified' => $lead->email_verified,
                'email_verification_status' => $lead->email_verification_status,
                'created_at' => $lead->created_at?->toIso8601String(),
                'workspace' => $lead->workspace ? ['id' => $lead->workspace->id, 'name' => $lead->workspace->name] : null,
                'company' => $lead->company ? ['id' => $lead->company->id, 'name' => $lead->company->name, 'website' => $lead->company->website] : null,
                'owner' => $lead->owner ? ['id' => $lead->owner->id, 'name' => $lead->owner->name, 'email' => $lead->owner->email] : null,
                'creator' => $lead->creator ? ['id' => $lead->creator->id, 'name' => $lead->creator->name] : null,
            ],
        ]);
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $leadName = $lead->full_name ?: $lead->email;
        $lead->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($lead)
            ->log("Lead '{$leadName}' deleted by Super Admin");

        return back()->with('success', "Lead '{$leadName}' has been removed.");
    }
}
