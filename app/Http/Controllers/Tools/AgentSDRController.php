<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\SdrApprovalQueue;
use App\Models\SdrEnrichmentProvider;
use App\Models\SdrAiColumn;
use App\Models\Workspace;
use App\Services\AgentSDRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AgentSDRController extends Controller
{
    public function __construct(
        protected AgentSDRService $sdrService
    ) {}

    // -------------------------------------------------------------------------
    // Helper: resolve workspace with multi-level fallback (never crashes)
    // -------------------------------------------------------------------------
    protected function getWorkspace(Request $request): ?Workspace
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return $user->currentWorkspace
            ?? $user->workspaces()->first()
            ?? Workspace::first();
    }

    // -------------------------------------------------------------------------
    // Helper: ensure SDR tables exist, auto-migrate once if they're missing
    // -------------------------------------------------------------------------
    protected function ensureTables(): void
    {
        if (! Schema::hasTable('sdr_enrichment_providers')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::error('AgentSDR auto-migrate failed: ' . $e->getMessage());
            }
        }
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------
    public function index(Request $request): Response
    {
        $workspace = $this->getWorkspace($request);

        // Guard: no workspace yet → return empty page so Vue can render gracefully
        if (! $workspace) {
            return Inertia::render('Tools/AgentSDR/Index', [
                'pendingApprovals' => ['data' => []],
                'providers'        => [],
                'aiColumns'        => [],
                'leadLists'        => [],
                'recentLeads'      => [],
                'supportedProviders' => $this->supportedProviders(),
            ]);
        }

        // Auto-migrate if tables are missing
        $this->ensureTables();

        // ---- queries (each wrapped in try/catch) ----------------------------

        $pendingApprovals = ['data' => []];
        try {
            if (Schema::hasTable('sdr_approval_queues')) {
                $pendingApprovals = SdrApprovalQueue::where('workspace_id', $workspace->id)
                    ->where('status', 'pending')
                    ->with(['lead'])
                    ->latest()
                    ->paginate(15);
            }
        } catch (\Throwable $e) {
            Log::warning('AgentSDR: sdr_approval_queues query failed – ' . $e->getMessage());
        }

        $providers = [];
        try {
            if (Schema::hasTable('sdr_enrichment_providers')) {
                $providers = SdrEnrichmentProvider::where('workspace_id', $workspace->id)
                    ->orderBy('priority', 'asc')
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::warning('AgentSDR: sdr_enrichment_providers query failed – ' . $e->getMessage());
        }

        $aiColumns = [];
        try {
            if (Schema::hasTable('sdr_ai_columns')) {
                $aiColumns = SdrAiColumn::where('workspace_id', $workspace->id)
                    ->with(['leadList'])
                    ->latest()
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::warning('AgentSDR: sdr_ai_columns query failed – ' . $e->getMessage());
        }

        $leadLists = [];
        try {
            if (Schema::hasTable('lead_lists')) {
                $leadLists = LeadList::where('workspace_id', $workspace->id)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
        } catch (\Throwable $e) {
            Log::warning('AgentSDR: lead_lists query failed – ' . $e->getMessage());
        }

        // NOTE: 'enrichment_status' is NOT a column on leads – removed to prevent SQL error
        $recentLeads = [];
        try {
            if (Schema::hasTable('leads')) {
                $recentLeads = Lead::where('workspace_id', $workspace->id)
                    ->latest()
                    ->limit(20)
                    ->get(['id', 'first_name', 'last_name', 'email', 'company_name', 'job_title', 'phone', 'linkedin_url']);
            }
        } catch (\Throwable $e) {
            Log::warning('AgentSDR: leads query failed – ' . $e->getMessage());
        }

        return Inertia::render('Tools/AgentSDR/Index', [
            'pendingApprovals'   => $pendingApprovals,
            'providers'          => $providers,
            'aiColumns'          => $aiColumns,
            'leadLists'          => $leadLists,
            'recentLeads'        => $recentLeads,
            'supportedProviders' => $this->supportedProviders(),
        ]);
    }

    // -------------------------------------------------------------------------
    // saveProvider
    // -------------------------------------------------------------------------
    public function saveProvider(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'provider_name' => 'required|string',
            'api_key'       => 'nullable|string',
            'is_active'     => 'boolean',
            'priority'      => 'integer',
        ]);

        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }

        $provider = SdrEnrichmentProvider::updateOrCreate(
            [
                'workspace_id'  => $workspace->id,
                'provider_name' => $request->input('provider_name'),
            ],
            [
                'api_key'   => $request->input('api_key'),
                'is_active' => $request->input('is_active', true),
                'priority'  => $request->input('priority', 1),
            ]
        );

        return response()->json([
            'provider' => $provider,
            'message'  => "Enrichment provider {$provider->provider_name} configured successfully.",
        ]);
    }

    // -------------------------------------------------------------------------
    // enrichLead
    // -------------------------------------------------------------------------
    public function enrichLead(Request $request, Lead $lead): \Illuminate\Http\JsonResponse
    {
        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }
        if ($lead->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $result = $this->sdrService->enrichLeadWithProviders($lead, $workspace);

        return response()->json([
            'result'  => $result,
            'message' => 'Lead successfully enriched across provider cascade.',
        ]);
    }

    // -------------------------------------------------------------------------
    // createAiColumn
    // -------------------------------------------------------------------------
    public function createAiColumn(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name'            => 'required|string|max:100',
            'prompt_template' => 'required|string',
            'lead_list_id'    => 'nullable|exists:lead_lists,id',
        ]);

        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }

        $column = SdrAiColumn::create([
            'workspace_id'    => $workspace->id,
            'lead_list_id'    => $request->input('lead_list_id'),
            'name'            => $request->input('name'),
            'prompt_template' => $request->input('prompt_template'),
            'model'           => 'gemini-1.5-pro',
            'is_active'       => true,
        ]);

        return response()->json([
            'column'  => $column,
            'message' => 'Dynamic AI column created successfully.',
        ]);
    }

    // -------------------------------------------------------------------------
    // runAiColumn
    // -------------------------------------------------------------------------
    public function runAiColumn(Request $request, SdrAiColumn $column): \Illuminate\Http\JsonResponse
    {
        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }
        if ($column->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $evaluated = $this->sdrService->evaluateAiColumn($column, 25);

        return response()->json([
            'evaluated' => $evaluated,
            'message'   => 'Evaluated AI column for ' . count($evaluated) . ' leads.',
        ]);
    }

    // -------------------------------------------------------------------------
    // proposeAction
    // -------------------------------------------------------------------------
    public function proposeAction(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'lead_id'     => 'required|exists:leads,id',
            'channel'     => 'required|in:email,linkedin,whatsapp',
            'action_type' => 'required|string',
            'context'     => 'nullable|string',
        ]);

        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }

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
            'item'    => $item,
            'message' => 'SDR action drafted and placed in human approval queue.',
        ]);
    }

    // -------------------------------------------------------------------------
    // classifyReply
    // -------------------------------------------------------------------------
    public function classifyReply(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'lead_id'    => 'required|exists:leads,id',
            'reply_text' => 'required|string',
        ]);

        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }

        $lead = Lead::findOrFail($request->input('lead_id'));
        if ($lead->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $analysis = $this->sdrService->classifyAndDraftSdrReply($request->input('reply_text'), $lead, $workspace);

        return response()->json([
            'analysis' => $analysis,
            'message'  => 'Prospect reply analyzed with suggested response.',
        ]);
    }

    // -------------------------------------------------------------------------
    // approveAction
    // -------------------------------------------------------------------------
    public function approveAction(Request $request, SdrApprovalQueue $item): \Illuminate\Http\JsonResponse
    {
        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }
        if ($item->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->sdrService->approveAndSend($item, $request->user());

        return response()->json([
            'item'    => $item->fresh(),
            'message' => 'Action approved and dispatched!',
        ]);
    }

    // -------------------------------------------------------------------------
    // rejectAction
    // -------------------------------------------------------------------------
    public function rejectAction(Request $request, SdrApprovalQueue $item): \Illuminate\Http\JsonResponse
    {
        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }
        if ($item->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $item->update(['status' => 'rejected']);

        return response()->json([
            'item'    => $item->fresh(),
            'message' => 'Queued action rejected and archived.',
        ]);
    }

    // -------------------------------------------------------------------------
    // updateDraft
    // -------------------------------------------------------------------------
    public function updateDraft(Request $request, SdrApprovalQueue $item): \Illuminate\Http\JsonResponse
    {
        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }
        if ($item->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'subject'          => 'nullable|string',
            'proposed_content' => 'required|string',
        ]);

        $item->update([
            'subject'          => $request->input('subject', $item->subject),
            'proposed_content' => $request->input('proposed_content'),
        ]);

        return response()->json([
            'item'    => $item->fresh(),
            'message' => 'Draft updated.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Static data: supported provider templates
    // -------------------------------------------------------------------------
    private function supportedProviders(): array
    {
        return [
            ['name' => 'apollo',      'label' => 'Apollo.io',   'icon' => '🚀', 'description' => 'Email finding, verified direct phone & B2B intent signals'],
            ['name' => 'hunter',      'label' => 'Hunter.io',   'icon' => '🎯', 'description' => 'Domain search, professional email finder & deliverability check'],
            ['name' => 'lusha',       'label' => 'Lusha',       'icon' => '⚡', 'description' => 'Direct mobile phone numbers & accurate executive decision makers'],
            ['name' => 'findymail',   'label' => 'Findymail',   'icon' => '✉️', 'description' => 'Clean verified emails from LinkedIn profiles & scrapers'],
            ['name' => 'snov',        'label' => 'Snov.io',     'icon' => '❄️', 'description' => 'Cold outreach email discovery and sender verification'],
            ['name' => 'zerobounce',  'label' => 'ZeroBounce',  'icon' => '🛡️', 'description' => 'Military-grade email hygiene, spam trap & bounce detection'],
        ];
    }
}
