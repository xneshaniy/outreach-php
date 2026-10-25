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
use Symfony\Component\HttpFoundation\StreamedResponse;
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
            ->with(['emailAccount', 'emailAccounts', 'leadList', 'creator'])
            ->withCount([
                'recipients as total_recipients',
                'recipients as pending_recipients' => fn($q) => $q->where('status', CampaignRecipient::STATUS_PENDING),
                'recipients as active_recipients' => fn($q) => $q->where('status', CampaignRecipient::STATUS_ACTIVE),
                'recipients as completed_recipients' => fn($q) => $q->where('status', CampaignRecipient::STATUS_COMPLETED),
                'recipients as bounced_recipients' => fn($q) => $q->where('status', CampaignRecipient::STATUS_BOUNCED),
                'recipients as replied_recipients' => fn($q) => $q->where('status', CampaignRecipient::STATUS_REPLIED),
                'recipients as failed_recipients' => fn($q) => $q->where('status', CampaignRecipient::STATUS_FAILED),
            ]);

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $campaigns = $query->latest()->paginate(15)->withQueryString();

        // Calculate global workspace campaign dashboard metrics
        $allCampaigns = Campaign::where('workspace_id', $workspace->id)->get();
        $stats = [
            'total_campaigns' => $allCampaigns->count(),
            'active_campaigns' => $allCampaigns->where('status', Campaign::STATUS_ACTIVE)->count(),
            'paused_campaigns' => $allCampaigns->where('status', Campaign::STATUS_PAUSED)->count(),
            'completed_campaigns' => $allCampaigns->where('status', Campaign::STATUS_COMPLETED)->count(),
            'total_sent' => CampaignRecipient::whereHas('campaign', fn($q) => $q->where('workspace_id', $workspace->id))->sum('emails_sent'),
            'total_delivered' => CampaignRecipient::whereHas('campaign', fn($q) => $q->where('workspace_id', $workspace->id))->where('status', CampaignRecipient::STATUS_COMPLETED)->count(),
            'total_failed' => CampaignRecipient::whereHas('campaign', fn($q) => $q->where('workspace_id', $workspace->id))->where('status', CampaignRecipient::STATUS_FAILED)->count(),
            'total_bounced' => CampaignRecipient::whereHas('campaign', fn($q) => $q->where('workspace_id', $workspace->id))->where('status', CampaignRecipient::STATUS_BOUNCED)->count(),
            'total_replied' => CampaignRecipient::whereHas('campaign', fn($q) => $q->where('workspace_id', $workspace->id))->where('status', CampaignRecipient::STATUS_REPLIED)->count(),
            'remaining_emails' => CampaignRecipient::whereHas('campaign', fn($q) => $q->where('workspace_id', $workspace->id))->whereIn('status', [CampaignRecipient::STATUS_PENDING, CampaignRecipient::STATUS_ACTIVE])->count(),
            'total_connected_accounts' => EmailAccount::where('workspace_id', $workspace->id)->where('is_active', true)->count(),
        ];

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'filters' => $request->only(['search', 'status']),
            'statuses' => Campaign::STATUSES,
            'stats' => $stats,
        ]);
    }

    public function create(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $emailAccounts = EmailAccount::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->get()
            ->map(fn($acc) => [
                'id' => $acc->id,
                'name' => $acc->name,
                'email' => $acc->email,
                'provider' => $acc->provider,
                'daily_send_limit' => $acc->daily_send_limit,
                'sent_today' => $acc->last_sent_date === now()->toDateString() ? $acc->sent_today : 0,
                'remaining_today' => $acc->getRemainingToday(),
            ]);

        $leadLists = LeadList::where('workspace_id', $workspace->id)
            ->get(['id', 'name']);

        return Inertia::render('Campaigns/Create', [
            'emailAccounts' => $emailAccounts,
            'leadLists' => $leadLists,
            'statuses' => Campaign::STATUSES,
            'strategies' => [
                ['id' => 'round_robin', 'name' => 'Round Robin', 'description' => 'Distribute emails between selected accounts evenly in sequence (Account 1 -> 2 -> 3 -> 1).'],
                ['id' => 'capacity_based', 'name' => 'Automatic (Capacity-Based)', 'description' => 'Automatically route to accounts with the most remaining daily capacity.'],
                ['id' => 'account_based', 'name' => 'Account-Based Allocation', 'description' => 'Assign specific number of emails to each individual account.'],
            ],
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['workspace_id'] = $request->user()->currentWorkspace->id;
        $data['created_by'] = $request->user()->id;

        $campaign = $this->campaignService->createCampaign($data);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign created successfully with multi-account distribution.');
    }

    public function show(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('view', $campaign);

        return redirect()->route('campaigns.report', $campaign);
    }

    public function edit(Request $request, Campaign $campaign): Response
    {
        $this->authorize('update', $campaign);

        $workspace = $request->user()->currentWorkspace;

        $emailAccounts = EmailAccount::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->get()
            ->map(fn($acc) => [
                'id' => $acc->id,
                'name' => $acc->name,
                'email' => $acc->email,
                'daily_send_limit' => $acc->daily_send_limit,
                'sent_today' => $acc->last_sent_date === now()->toDateString() ? $acc->sent_today : 0,
                'remaining_today' => $acc->getRemainingToday(),
            ]);

        $leadLists = LeadList::where('workspace_id', $workspace->id)
            ->get(['id', 'name']);

        $campaign->load(['steps' => fn($q) => $q->orderBy('step_order'), 'emailAccounts']);

        return Inertia::render('Campaigns/Edit', [
            'campaign' => $campaign,
            'selectedAccountIds' => $campaign->emailAccounts->pluck('id')->toArray(),
            'emailAccounts' => $emailAccounts,
            'leadLists' => $leadLists,
            'statuses' => Campaign::STATUSES,
            'strategies' => [
                ['id' => 'round_robin', 'name' => 'Round Robin', 'description' => 'Distribute emails between selected accounts evenly in sequence.'],
                ['id' => 'capacity_based', 'name' => 'Automatic (Capacity-Based)', 'description' => 'Route to accounts with the most remaining daily capacity.'],
                ['id' => 'account_based', 'name' => 'Account-Based Allocation', 'description' => 'Assign specific number of emails to each individual account.'],
            ],
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $this->campaignService->updateCampaign($campaign, $request->validated());

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    public function duplicate(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $copy = $this->campaignService->duplicateCampaign($campaign);

        return redirect()->route('campaigns.index')
            ->with('success', "Campaign duplicated successfully as '{$copy->name}'.");
    }

    public function report(Request $request, Campaign $campaign): Response
    {
        $this->authorize('view', $campaign);

        $campaign->load(['emailAccounts', 'steps', 'creator']);

        $query = $campaign->recipients()->with(['lead', 'emailAccount']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('lead', function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $recipients = $query->latest()->paginate(25)->withQueryString();

        $accountPerformance = $campaign->campaignEmailAccounts()
            ->with('emailAccount')
            ->get()
            ->map(function ($pivot) {
                $acc = $pivot->emailAccount;
                return [
                    'id' => $acc?->id,
                    'name' => $acc?->name ?? 'Account',
                    'email' => $acc?->email ?? '—',
                    'daily_limit' => $acc?->daily_send_limit ?? 0,
                    'sent_today' => $acc?->sent_today ?? 0,
                    'remaining' => $acc?->getRemainingToday() ?? 0,
                    'successful' => $pivot->sent_count,
                    'failed' => $pivot->failed_count,
                    'allocation' => $pivot->allocation_count,
                ];
            });

        // Overview stats
        $totalRecipients = $campaign->recipients()->count();
        $sentRecipients = $campaign->recipients()->where('emails_sent', '>', 0)->count();
        $completedRecipients = $campaign->recipients()->where('status', CampaignRecipient::STATUS_COMPLETED)->count();
        $failedRecipients = $campaign->recipients()->where('status', CampaignRecipient::STATUS_FAILED)->count();
        $bouncedRecipients = $campaign->recipients()->where('status', CampaignRecipient::STATUS_BOUNCED)->count();
        $pendingRecipients = $campaign->recipients()->whereIn('status', [CampaignRecipient::STATUS_PENDING, CampaignRecipient::STATUS_ACTIVE])->count();
        $totalRetries = $campaign->recipients()->sum('retry_count');

        $overview = [
            'name' => $campaign->name,
            'status' => $campaign->status,
            'distribution_strategy' => $campaign->distribution_strategy ?? 'round_robin',
            'started_at' => $campaign->started_at?->toDateTimeString(),
            'completed_at' => $campaign->completed_at?->toDateTimeString(),
            'total_recipients' => $totalRecipients,
            'total_sent' => $sentRecipients,
            'successful' => $completedRecipients,
            'failed' => $failedRecipients,
            'bounced' => $bouncedRecipients,
            'pending' => $pendingRecipients,
            'retry_count' => $totalRetries,
        ];

        return Inertia::render('Campaigns/Report', [
            'campaign' => $campaign,
            'overview' => $overview,
            'accountPerformance' => $accountPerformance,
            'recipients' => $recipients,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function exportReport(Campaign $campaign): StreamedResponse
    {
        $this->authorize('view', $campaign);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="campaign_' . $campaign->id . '_report.csv"',
        ];

        return response()->stream(function () use ($campaign) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Recipient Email',
                'Recipient Name',
                'Sending Email Account',
                'Status',
                'Emails Sent',
                'Last Sent At',
                'Retry Count',
                'Error Message',
                'SMTP Response',
            ]);

            $campaign->recipients()->with(['lead', 'emailAccount'])->chunk(250, function ($recipients) use ($handle) {
                foreach ($recipients as $r) {
                    fputcsv($handle, [
                        $r->lead?->email ?? '',
                        $r->lead?->full_name ?? '',
                        $r->emailAccount?->email ?? 'Default Account',
                        $r->status,
                        $r->emails_sent,
                        $r->last_sent_at?->toDateTimeString() ?? '',
                        $r->retry_count,
                        $r->error_message ?? '',
                        $r->last_response ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function sendBatch(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $sent = $this->campaignService->processDueEmails($campaign, 50);

        return back()->with('success', "Batch run complete: processed {$sent} email(s) across selected sending accounts.");
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
            return back()->with('success', 'Campaign started and active. Distributing emails across sending accounts.');
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

        return back()->with('success', 'Campaign stopped.');
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
}
