<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\CampaignStep;
use App\Models\CampaignRecipient;
use App\Models\EmailAccount;
use App\Models\LeadList;
use App\Models\Workspace;
use App\Services\CampaignService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function __construct(
        protected CampaignService $campaignService,
        protected EmailService $emailService
    ) {}

    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $query = Campaign::where('workspace_id', $workspace->id)
            ->with(['emailAccount', 'leadList', 'creator'])
            ->withCount([
                'recipients as total_recipients',
                'recipients as pending_recipients' => fn($q) => $q->where('status', CampaignRecipient::STATUS_PENDING),
                'recipients as active_recipients' => fn($q) => $q->where('status', CampaignRecipient::STATUS_ACTIVE),
                'recipients as completed_recipients' => fn($q) => $q->where('status', CampaignRecipient::STATUS_COMPLETED),
                'recipients as bounced_recipients' => fn($q) => $q->where('status', CampaignRecipient::STATUS_BOUNCED),
                'recipients as replied_recipients' => fn($q) => $q->where('status', CampaignRecipient::STATUS_REPLIED),
            ]);

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $campaigns = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'filters' => $request->only(['search', 'status']),
            'statuses' => Campaign::STATUSES,
        ]);
    }

    public function create(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $emailAccounts = EmailAccount::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        $leadLists = LeadList::where('workspace_id', $workspace->id)
            ->get(['id', 'name']);

        return Inertia::render('Campaigns/Create', [
            'emailAccounts' => $emailAccounts,
            'leadLists' => $leadLists,
            'statuses' => Campaign::STATUSES,
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['workspace_id'] = $request->user()->currentWorkspace->id;
        $data['created_by'] = $request->user()->id;

        $campaign = $this->campaignService->createCampaign($data);

        return redirect()->route('campaigns.edit', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(Request $request, Campaign $campaign): Response
    {
        $this->authorize('view', $campaign);

        $campaign->load([
            'emailAccount',
            'leadList',
            'creator',
            'steps' => fn($q) => $q->orderBy('step_order'),
            'recipients' => fn($q) => $q->with('lead')->latest()->limit(50),
        ]);

        $stats = $campaign->getStats();

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
            'stats' => $stats,
        ]);
    }

    public function edit(Request $request, Campaign $campaign): Response
    {
        $this->authorize('update', $campaign);

        $workspace = $request->user()->currentWorkspace;

        $emailAccounts = EmailAccount::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        $leadLists = LeadList::where('workspace_id', $workspace->id)
            ->get(['id', 'name']);

        $campaign->load(['steps' => fn($q) => $q->orderBy('step_order')]);

        return Inertia::render('Campaigns/Edit', [
            'campaign' => $campaign,
            'emailAccounts' => $emailAccounts,
            'leadLists' => $leadLists,
            'statuses' => Campaign::STATUSES,
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $campaign->update($request->validated());

        return redirect()->route('campaigns.edit', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    public function addRecipients(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'exists:leads,id',
        ]);

        $added = $this->campaignService->addRecipients($campaign, $request->lead_ids);

        return back()->with('success', "Added {$added} recipients to campaign.");
    }

    public function addRecipientsFromList(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $request->validate([
            'lead_list_id' => 'required|exists:lead_lists,id',
        ]);

        $list = LeadList::findOrFail($request->lead_list_id);
        $added = $this->campaignService->addRecipientsFromList($campaign, $list);

        return back()->with('success', "Added {$added} recipients from list.");
    }

    public function schedule(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $request->validate([
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $at = $request->scheduled_at ? \Carbon\Carbon::parse($request->scheduled_at) : null;
        $this->campaignService->scheduleCampaign($campaign, $at);

        return back()->with('success', $at ? 'Campaign scheduled.' : 'Campaign scheduled for immediate start.');
    }

    public function activate(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        try {
            $this->campaignService->activateCampaign($campaign);
            return back()->with('success', 'Campaign activated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function pause(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $this->campaignService->pauseCampaign($campaign);

        return back()->with('success', 'Campaign paused.');
    }

    public function resume(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $this->campaignService->resumeCampaign($campaign);

        return back()->with('success', 'Campaign resumed.');
    }

    public function cancel(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $this->campaignService->cancelCampaign($campaign);

        return back()->with('success', 'Campaign cancelled.');
    }

    public function testSend(Request $request, Campaign $campaign): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $campaign);

        $request->validate([
            'emails' => 'required|array|min:1|max:5',
            'emails.*' => 'email',
        ]);

        try {
            $results = $this->campaignService->testSend($campaign, $request->emails);
            return response()->json(['success' => true, 'results' => $results]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function preview(Request $request, Campaign $campaign, CampaignStep $step): Response
    {
        $this->authorize('view', $campaign);

        $lead = \App\Models\Lead::where('workspace_id', $campaign->workspace_id)->first();
        if (!$lead) {
            abort(404, 'No leads available for preview');
        }

        $personalization = [
            'first_name' => $lead->first_name ?? 'John',
            'last_name' => $lead->last_name ?? 'Doe',
            'full_name' => $lead->full_name ?? 'John Doe',
            'email' => $lead->email ?? 'john@example.com',
            'job_title' => $lead->job_title ?? 'Manager',
            'company_name' => $lead->company?->name ?? 'Acme Inc',
            'company_domain' => $lead->company?->domain ?? 'acme.com',
        ];

        $subject = $this->replaceTokens($step->subject, $personalization);
        $bodyHtml = $this->replaceTokens($step->body_html, $personalization);
        $bodyText = $step->body_text ? $this->replaceTokens($step->body_text, $personalization) : null;

        return Inertia::render('Campaigns/Preview', [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'step' => $step,
            'sample_lead' => $lead,
        ]);
    }

    private function replaceTokens(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            fn($matches) => $data[$matches[1]] ?? "{{$matches[1]}}",
            $template
        );
    }
}