<?php

namespace App\Http\Controllers\Pipeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pipeline\StorePipelineRequest;
use App\Http\Requests\Pipeline\UpdatePipelineRequest;
use App\Http\Requests\Pipeline\StoreStageRequest;
use App\Http\Requests\Pipeline\UpdateStageRequest;
use App\Http\Requests\Pipeline\StoreDealRequest;
use App\Http\Requests\Pipeline\UpdateDealRequest;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        // Standard 9 CRM Sales Pipeline Stages
        $pipelineStages = [
            ['id' => 'new_lead', 'name' => 'New Lead', 'color' => '#6366f1', 'order' => 1],
            ['id' => 'contacted', 'name' => 'Contacted', 'color' => '#3b82f6', 'order' => 2],
            ['id' => 'replied', 'name' => 'Replied', 'color' => '#8b5cf6', 'order' => 3],
            ['id' => 'interested', 'name' => 'Interested', 'color' => '#ec4899', 'order' => 4],
            ['id' => 'meeting_scheduled', 'name' => 'Meeting Scheduled', 'color' => '#f59e0b', 'order' => 5],
            ['id' => 'proposal_sent', 'name' => 'Proposal Sent', 'color' => '#f97316', 'order' => 6],
            ['id' => 'negotiation', 'name' => 'Negotiation', 'color' => '#eab308', 'order' => 7],
            ['id' => 'won', 'name' => 'Won', 'color' => '#10b981', 'order' => 8],
            ['id' => 'lost', 'name' => 'Lost', 'color' => '#ef4444', 'order' => 9],
        ];

        // Fetch leads for the workspace with relevant relations
        $leads = Lead::where('workspace_id', $workspace->id)
            ->with(['company', 'owner', 'assignedCampaign', 'deals'])
            ->latest()
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'name' => $lead->full_name ?: $lead->email,
                    'email' => $lead->email,
                    'company' => $lead->company ?: ($lead->company?->name ?? '—'),
                    'score' => $lead->score ?? 0,
                    'category' => $lead->category ?? 'cold',
                    'pipeline_stage' => $lead->pipeline_stage ?: 'new_lead',
                    'last_contacted_at' => $lead->last_contacted_at?->diffForHumans() ?? 'Never',
                    'follow_up_at' => $lead->follow_up_at?->format('M d, Y') ?? null,
                    'campaign_name' => $lead->assignedCampaign?->name ?? null,
                    'deal_value' => $lead->deals->sum('value') ?: null,
                ];
            });

        // Group leads by stage
        $groupedLeads = [];
        foreach ($pipelineStages as $stage) {
            $groupedLeads[$stage['id']] = $leads->where('pipeline_stage', $stage['id'])->values();
        }

        $pipelines = Pipeline::where('workspace_id', $workspace->id)
            ->with(['stages', 'deals' => fn($q) => $q->where('status', Deal::STATUS_OPEN)])
            ->withCount('deals')
            ->get();

        $defaultPipeline = $pipelines->firstWhere('is_default', true) ?? $pipelines->first();

        return Inertia::render('Pipeline/Index', [
            'stages' => $pipelineStages,
            'groupedLeads' => $groupedLeads,
            'totalLeads' => $leads->count(),
            'pipelines' => $pipelines,
            'defaultPipeline' => $defaultPipeline,
        ]);
    }

    public function moveLeadStage(Request $request, Lead $lead): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $lead);

        $request->validate([
            'stage' => 'required|string',
        ]);

        $stage = $request->stage;
        $updates = ['pipeline_stage' => $stage];

        if ($stage === 'won') {
            $updates['status'] = 'converted';
            $updates['converted_at'] = now();
        } elseif ($stage === 'lost') {
            $updates['status'] = 'unqualified';
        } elseif ($stage === 'contacted') {
            $updates['status'] = 'contacted';
            $updates['last_contacted_at'] = now();
        } elseif ($stage === 'replied') {
            $updates['status'] = 'contacted';
        }

        $lead->update($updates);
        app(\App\Services\LeadScoreService::class)->calculateScore($lead);

        return response()->json([
            'success' => true,
            'lead_id' => $lead->id,
            'stage' => $stage,
            'score' => $lead->fresh()->score,
            'category' => $lead->fresh()->category,
        ]);
    }

    public function show(Request $request, Pipeline $pipeline): Response
    {
        $this->authorize('view', $pipeline);

        $pipeline->load([
            'stages' => fn($q) => $q->orderBy('order')->with(['deals' => fn($q) => $q->where('status', Deal::STATUS_OPEN)->with(['lead', 'company', 'owner', 'stage'])]),
        ]);

        $leads = Lead::where('workspace_id', $pipeline->workspace_id)
            ->where('status', '!=', 'converted')
            ->where('status', '!=', 'archived')
            ->get(['id', 'first_name', 'last_name', 'email', 'company_id']);

        $users = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $pipeline->workspace_id))
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'avatar_path']);

        return Inertia::render('Pipeline/Show', [
            'pipeline' => $pipeline,
            'leads' => $leads,
            'users' => $users,
        ]);
    }

    public function storePipeline(StorePipelineRequest $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        $pipeline = Pipeline::create(array_merge($request->validated(), [
            'workspace_id' => $workspace->id,
            'created_by' => $request->user()->id,
        ]));

        // Create default stages
        $defaultStages = [
            ['name' => 'New', 'color' => '#6366f1', 'order' => 1, 'probability' => 10],
            ['name' => 'Contacted', 'color' => '#0ea5e9', 'order' => 2, 'probability' => 25],
            ['name' => 'Qualified', 'color' => '#22c55e', 'order' => 3, 'probability' => 50],
            ['name' => 'Proposal', 'color' => '#f59e0b', 'order' => 4, 'probability' => 75],
            ['name' => 'Won', 'color' => '#10b981', 'order' => 5, 'probability' => 100, 'is_won' => true, 'is_closed' => true],
            ['name' => 'Lost', 'color' => '#ef4444', 'order' => 6, 'probability' => 0, 'is_lost' => true, 'is_closed' => true],
        ];

        foreach ($defaultStages as $stage) {
            PipelineStage::create(array_merge($stage, ['pipeline_id' => $pipeline->id]));
        }

        return redirect()->route('pipeline.show', $pipeline)
            ->with('success', 'Pipeline created successfully.');
    }

    public function updatePipeline(UpdatePipelineRequest $request, Pipeline $pipeline): RedirectResponse
    {
        $this->authorize('update', $pipeline);

        $pipeline->update($request->validated());

        return back()->with('success', 'Pipeline updated successfully.');
    }

    public function destroyPipeline(Request $request, Pipeline $pipeline): RedirectResponse
    {
        $this->authorize('delete', $pipeline);

        $pipeline->delete();

        return redirect()->route('pipeline.index')
            ->with('success', 'Pipeline deleted successfully.');
    }

    public function setDefaultPipeline(Request $request, Pipeline $pipeline): RedirectResponse
    {
        $this->authorize('update', $pipeline);

        Pipeline::where('workspace_id', $pipeline->workspace_id)
            ->update(['is_default' => false]);

        $pipeline->update(['is_default' => true]);

        return back()->with('success', 'Default pipeline updated.');
    }

    public function storeStage(StoreStageRequest $request, Pipeline $pipeline): RedirectResponse
    {
        $this->authorize('update', $pipeline);

        $maxOrder = $pipeline->stages()->max('order') ?? 0;

        PipelineStage::create(array_merge($request->validated(), [
            'pipeline_id' => $pipeline->id,
            'order' => $maxOrder + 1,
        ]));

        return back()->with('success', 'Stage created successfully.');
    }

    public function updateStage(UpdateStageRequest $request, PipelineStage $stage): RedirectResponse
    {
        $this->authorize('update', $stage);

        $stage->update($request->validated());

        return back()->with('success', 'Stage updated successfully.');
    }

    public function destroyStage(Request $request, PipelineStage $stage): RedirectResponse
    {
        $this->authorize('delete', $stage);

        // Prevent deleting won/lost stages if they have deals
        if (($stage->is_won || $stage->is_lost) && $stage->deals()->exists()) {
            return back()->with('error', 'Cannot delete stage with deals. Move deals first.');
        }

        $stage->delete();

        return back()->with('success', 'Stage deleted successfully.');
    }

    public function reorderStages(Request $request, Pipeline $pipeline): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $pipeline);

        $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:pipeline_stages,id',
            'stages.*.order' => 'required|integer|min:1',
        ]);

        foreach ($request->stages as $stageData) {
            PipelineStage::where('id', $stageData['id'])
                ->where('pipeline_id', $pipeline->id)
                ->update(['order' => $stageData['order']]);
        }

        return response()->json(['success' => true]);
    }

    public function storeDeal(StoreDealRequest $request, Pipeline $pipeline): RedirectResponse
    {
        $this->authorize('create', [Deal::class, $pipeline]);

        $defaultStage = $pipeline->ensureDefaultStage();

        $deal = Deal::create(array_merge($request->validated(), [
            'workspace_id' => $pipeline->workspace_id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $defaultStage->id,
            'owner_id' => $request->owner_id ?? $request->user()->id,
            'created_by' => $request->user()->id,
        ]));

        return redirect()->route('pipeline.show', $pipeline)
            ->with('success', 'Deal created successfully.');
    }

    public function showDeal(Request $request, Deal $deal): Response
    {
        $this->authorize('view', $deal);

        $deal->load(['lead', 'company', 'owner', 'stage.pipeline', 'tasks' => fn($q) => $q->with('assignee')->latest()]);

        $users = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $deal->workspace_id))
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'avatar_path']);

        return Inertia::render('Pipeline/DealShow', [
            'deal' => $deal,
            'users' => $users,
        ]);
    }

    public function updateDeal(UpdateDealRequest $request, Deal $deal): RedirectResponse
    {
        $this->authorize('update', $deal);

        $oldStageId = $deal->stage_id;
        $deal->update($request->validated());

        // If stage changed, log activity
        if ($oldStageId !== $deal->stage_id) {
            activity()
                ->performedOn($deal)
                ->causedBy($request->user())
                ->withProperties([
                    'old_stage' => PipelineStage::find($oldStageId)?->name,
                    'new_stage' => $deal->stage->name,
                ])
                ->log('deal_stage_changed');
        }

        return back()->with('success', 'Deal updated successfully.');
    }

    public function destroyDeal(Request $request, Deal $deal): RedirectResponse
    {
        $this->authorize('delete', $deal);

        $pipelineId = $deal->pipeline_id;
        $deal->delete();

        return redirect()->route('pipeline.show', $pipelineId)
            ->with('success', 'Deal deleted successfully.');
    }

    public function moveDeal(Request $request, Deal $deal): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $deal);

        $request->validate([
            'stage_id' => 'required|exists:pipeline_stages,id',
        ]);

        $oldStage = $deal->stage;
        $deal->update(['stage_id' => $request->stage_id]);

        activity()
            ->performedOn($deal)
            ->causedBy($request->user())
            ->withProperties([
                'old_stage' => $oldStage->name,
                'new_stage' => $deal->stage->name,
            ])
            ->log('deal_stage_changed');

        return response()->json(['success' => true, 'deal' => $deal->load('stage')]);
    }

    public function winDeal(Request $request, Deal $deal): RedirectResponse
    {
        $this->authorize('update', $deal);

        $deal->win();

        return back()->with('success', 'Deal marked as won.');
    }

    public function loseDeal(Request $request, Deal $deal): RedirectResponse
    {
        $this->authorize('update', $deal);

        $deal->lose();

        return back()->with('success', 'Deal marked as lost.');
    }
}