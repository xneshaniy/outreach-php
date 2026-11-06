<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\SdrApprovalQueue;
use App\Models\SdrEnrichmentProvider;
use App\Models\SdrAiColumn;
use App\Services\AgentSDRService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgentSDRController extends Controller
{
    public function __construct(
        protected AgentSDRService $sdrService
    ) {}

    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $pendingApprovals = SdrApprovalQueue::where('workspace_id', $workspace->id)
            ->where('status', 'pending')
            ->with(['lead'])
            ->latest()
            ->paginate(15);

        $providers = SdrEnrichmentProvider::where('workspace_id', $workspace->id)
            ->orderBy('priority', 'asc')
            ->get();

        $aiColumns = SdrAiColumn::where('workspace_id', $workspace->id)
            ->with(['leadList'])
            ->latest()
            ->get();

        $leadLists = LeadList::where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $recentLeads = Lead::where('workspace_id', $workspace->id)
            ->latest()
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'company_name', 'job_title', 'phone', 'linkedin_url', 'enrichment_status']);

        // Default supported provider templates
        $supportedProviders = [
            ['name' => 'apollo', 'label' => 'Apollo.io', 'icon' => '🚀', 'description' => 'Email finding, verified direct phone & B2B intent signals'],
            ['name' => 'hunter', 'label' => 'Hunter.io', 'icon' => '🎯', 'description' => 'Domain search, professional email finder & deliverability check'],
            ['name' => 'lusha', 'label' => 'Lusha', 'icon' => '⚡', 'description' => 'Direct mobile phone numbers & accurate executive decision makers'],
            ['name' => 'findymail', 'label' => 'Findymail', 'icon' => '✉️', 'description' => 'Clean verified emails from LinkedIn profiles & scrapers'],
            ['name' => 'snov', 'label' => 'Snov.io', 'icon' => '❄️', 'description' => 'Cold outreach email discovery and sender verification'],
            ['name' => 'zerobounce', 'label' => 'ZeroBounce', 'icon' => '🛡️', 'description' => 'Military-grade email hygiene, spam trap & bounce detection'],
        ];

        return Inertia::render('Tools/AgentSDR/Index', [
            'pendingApprovals' => $pendingApprovals,
            'providers' => $providers,
            'aiColumns' => $aiColumns,
            'leadLists' => $leadLists,
            'recentLeads' => $recentLeads,
            'supportedProviders' => $supportedProviders,
        ]);
    }

    public function saveProvider(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'provider_name' => 'required|string',
            'api_key' => 'nullable|string',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ]);

        $workspace = $request->user()->currentWorkspace;

        $provider = SdrEnrichmentProvider::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'provider_name' => $request->input('provider_name'),
            ],
            [
                'api_key' => $request->input('api_key'),
                'is_active' => $request->input('is_active', true),
                'priority' => $request->input('priority', 1),
            ]
        );

        return response()->json([
            'provider' => $provider,
            'message' => "Enrichment provider {$provider->provider_name} configured successfully.",
        ]);
    }

    public function enrichLead(Request $request, Lead $lead): \Illuminate\Http\JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;
        if ($lead->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $result = $this->sdrService->enrichLeadWithProviders($lead, $workspace);

        return response()->json([
            'result' => $result,
            'message' => 'Lead successfully enriched across provider cascade.',
        ]);
    }

    public function createAiColumn(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'prompt_template' => 'required|string',
            'lead_list_id' => 'nullable|exists:lead_lists,id',
        ]);

        $workspace = $request->user()->currentWorkspace;

        $column = SdrAiColumn::create([
            'workspace_id' => $workspace->id,
            'lead_list_id' => $request->input('lead_list_id'),
            'name' => $request->input('name'),
            'prompt_template' => $request->input('prompt_template'),
            'model' => 'gemini-1.5-pro',
            'is_active' => true,
        ]);

        return response()->json([
            'column' => $column,
            'message' => 'Dynamic AI column created successfully.',
        ]);
    }

    public function runAiColumn(Request $request, SdrAiColumn $column): \Illuminate\Http\JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;
        if ($column->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $evaluated = $this->sdrService->evaluateAiColumn($column, 25);

        return response()->json([
            'evaluated' => $evaluated,
            'message' => "Evaluated AI column for " . count($evaluated) . " leads.",
        ]);
    }

    public function proposeAction(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'channel' => 'required|in:email,linkedin,whatsapp',
            'action_type' => 'required|string',
            'context' => 'nullable|string',
        ]);

        $workspace = $request->user()->currentWorkspace;
        $lead = Lead::findOrFail($request->input('lead_id'));
        if ($lead->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $item = $this->sdrService->proposeAction(
            $workspace,
            $lead,
            $request->input('channel'),
            $request->input('action_type'),
            $request->input('context'),
            $request->user()
        );

        return response()->json([
            'item' => $item,
            'message' => 'SDR action drafted and placed in human approval queue.',
        ]);
    }

    public function classifyReply(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'reply_text' => 'required|string',
        ]);

        $workspace = $request->user()->currentWorkspace;
        $lead = Lead::findOrFail($request->input('lead_id'));
        if ($lead->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $analysis = $this->sdrService->classifyAndDraftSdrReply($request->input('reply_text'), $lead, $workspace);

        return response()->json([
            'analysis' => $analysis,
            'message' => 'Prospect reply analyzed with suggested response.',
        ]);
    }

    public function approveAction(Request $request, SdrApprovalQueue $item): \Illuminate\Http\JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;
        if ($item->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->sdrService->approveAndSend($item, $request->user());

        return response()->json([
            'item' => $item->fresh(),
            'message' => 'Action approved and dispatched!',
        ]);
    }

    public function rejectAction(Request $request, SdrApprovalQueue $item): \Illuminate\Http\JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;
        if ($item->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $item->update(['status' => 'rejected']);

        return response()->json([
            'item' => $item->fresh(),
            'message' => 'Queued action rejected and archived.',
        ]);
    }

    public function updateDraft(Request $request, SdrApprovalQueue $item): \Illuminate\Http\JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;
        if ($item->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'subject' => 'nullable|string',
            'proposed_content' => 'required|string',
        ]);

        $item->update([
            'subject' => $request->input('subject', $item->subject),
            'proposed_content' => $request->input('proposed_content'),
        ]);

        return response()->json([
            'item' => $item->fresh(),
            'message' => 'Draft updated.',
        ]);
    }
}
