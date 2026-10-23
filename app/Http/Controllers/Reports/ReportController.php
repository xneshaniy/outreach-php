<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Deal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        return Inertia::render('Reports/Index', [
            'reportTypes' => [
                ['id' => 'leads', 'name' => 'Leads Report', 'description' => 'Lead acquisition, conversion, and pipeline metrics'],
                ['id' => 'campaigns', 'name' => 'Campaign Performance', 'description' => 'Email campaign delivery, engagement, and ROI'],
                ['id' => 'deals', 'name' => 'Sales Pipeline', 'description' => 'Deal progression, velocity, and forecasting'],
                ['id' => 'team', 'name' => 'Team Performance', 'description' => 'Individual and team activity metrics'],
                ['id' => 'email', 'name' => 'Email Analytics', 'description' => 'Delivery, open rates, click rates, and deliverability'],
            ],
        ]);
    }

    public function leads(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $totalLeads = Lead::where('workspace_id', $workspace->id)->count();
        $newLeads = Lead::where('workspace_id', $workspace->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        $leadsByStatus = Lead::where('workspace_id', $workspace->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        $leadsBySource = Lead::where('workspace_id', $workspace->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('source, count(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        $leadsByOwner = Lead::where('workspace_id', $workspace->id)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('owner_id')
            ->with('owner:id,name,email')
            ->selectRaw('owner_id, count(*) as count')
            ->groupBy('owner_id')
            ->orderByDesc('count')
            ->get();

        $conversionRate = $totalLeads > 0
            ? round((Lead::where('workspace_id', $workspace->id)->where('status', 'converted')->count() / $totalLeads) * 100, 2)
            : 0;

        $leadsOverTime = Lead::where('workspace_id', $workspace->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('Reports/Leads', [
            'days' => $days,
            'totalLeads' => $totalLeads,
            'newLeads' => $newLeads,
            'conversionRate' => $conversionRate,
            'leadsByStatus' => $leadsByStatus,
            'leadsBySource' => $leadsBySource,
            'leadsByOwner' => $leadsByOwner,
            'leadsOverTime' => $leadsOverTime,
        ]);
    }

    public function campaigns(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $campaigns = Campaign::where('workspace_id', $workspace->id)
            ->where('created_at', '>=', $startDate)
            ->withCount([
                'recipients as total',
                'recipients as sent' => fn($q) => $q->where('emails_sent', '>', 0),
                'recipients as delivered' => fn($q) => $q->where('status', CampaignRecipient::STATUS_COMPLETED),
                'recipients as opened' => fn($q) => $q->where('emails_opened', '>', 0),
                'recipients as clicked' => fn($q) => $q->where('links_clicked', '>', 0),
                'recipients as replied' => fn($q) => $q->where('status', CampaignRecipient::STATUS_REPLIED),
                'recipients as bounced' => fn($q) => $q->where('status', CampaignRecipient::STATUS_BOUNCED),
                'recipients as unsubscribed' => fn($q) => $q->where('status', CampaignRecipient::STATUS_UNSUBSCRIBED),
            ])
            ->get();

        $totals = [
            'sent' => $campaigns->sum('sent'),
            'delivered' => $campaigns->sum('delivered'),
            'opened' => $campaigns->sum('opened'),
            'clicked' => $campaigns->sum('clicked'),
            'replied' => $campaigns->sum('replied'),
            'bounced' => $campaigns->sum('bounced'),
            'unsubscribed' => $campaigns->sum('unsubscribed'),
        ];

        $deliveryRate = $totals['sent'] > 0 ? round(($totals['delivered'] / $totals['sent']) * 100, 2) : 0;
        $openRate = $totals['delivered'] > 0 ? round(($totals['opened'] / $totals['delivered']) * 100, 2) : 0;
        $clickRate = $totals['delivered'] > 0 ? round(($totals['clicked'] / $totals['delivered']) * 100, 2) : 0;
        $replyRate = $totals['delivered'] > 0 ? round(($totals['replied'] / $totals['delivered']) * 100, 2) : 0;
        $bounceRate = $totals['sent'] > 0 ? round(($totals['bounced'] / $totals['sent']) * 100, 2) : 0;

        return Inertia::render('Reports/Campaigns', [
            'days' => $days,
            'campaigns' => $campaigns,
            'totals' => $totals,
            'deliveryRate' => $deliveryRate,
            'openRate' => $openRate,
            'clickRate' => $clickRate,
            'replyRate' => $replyRate,
            'bounceRate' => $bounceRate,
        ]);
    }

    public function deals(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;
        $days = $request->get('days', 90);
        $startDate = Carbon::now()->subDays($days);

        $totalDeals = Deal::where('workspace_id', $workspace->id)->count();
        $openDeals = Deal::where('workspace_id', $workspace->id)->where('status', Deal::STATUS_OPEN)->count();
        $wonDeals = Deal::where('workspace_id', $workspace->id)->where('status', Deal::STATUS_WON)->count();
        $lostDeals = Deal::where('workspace_id', $workspace->id)->where('status', Deal::STATUS_LOST)->count();

        $totalValue = Deal::where('workspace_id', $workspace->id)->sum('value');
        $wonValue = Deal::where('workspace_id', $workspace->id)->where('status', Deal::STATUS_WON)->sum('value');
        $openValue = Deal::where('workspace_id', $workspace->id)->where('status', Deal::STATUS_OPEN)->sum('value');

        $dealsByStage = Deal::where('workspace_id', $workspace->id)
            ->where('status', Deal::STATUS_OPEN)
            ->with('stage')
            ->get()
            ->groupBy('stage_id')
            ->map(fn($deals) => [
                'stage' => $deals->first()->stage,
                'count' => $deals->count(),
                'value' => $deals->sum('value'),
                'weighted_value' => $deals->sum(fn($d) => $d->getWeightedValue()),
            ])
            ->values();

        $dealsByOwner = Deal::where('workspace_id', $workspace->id)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('owner_id')
            ->with('owner:id,name,email')
            ->selectRaw('owner_id, count(*) as count, sum(value) as value')
            ->groupBy('owner_id')
            ->orderByDesc('value')
            ->get();

        $winRate = ($wonDeals + $lostDeals) > 0
            ? round(($wonDeals / ($wonDeals + $lostDeals)) * 100, 2)
            : 0;

        $avgDealSize = $wonDeals > 0 ? round($wonValue / $wonDeals, 2) : 0;

        return Inertia::render('Reports/Deals', [
            'days' => $days,
            'totalDeals' => $totalDeals,
            'openDeals' => $openDeals,
            'wonDeals' => $wonDeals,
            'lostDeals' => $lostDeals,
            'totalValue' => $totalValue,
            'wonValue' => $wonValue,
            'openValue' => $openValue,
            'winRate' => $winRate,
            'avgDealSize' => $avgDealSize,
            'dealsByStage' => $dealsByStage,
            'dealsByOwner' => $dealsByOwner,
        ]);
    }

    public function team(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $users = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))
            ->where('is_active', true)
            ->withCount([
                'leads as leads_created' => fn($q) => $q->where('created_at', '>=', $startDate),
                'tasks as tasks_completed' => fn($q) => $q->where('status', Task::STATUS_COMPLETED)->where('completed_at', '>=', $startDate),
                'tasks as tasks_overdue' => fn($q) => $q->where('status', '!=', Task::STATUS_COMPLETED)->where('due_at', '<', now()),
            ])
            ->get();

        return Inertia::render('Reports/Team', [
            'days' => $days,
            'users' => $users,
        ]);
    }

    public function email(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $messages = Message::where('workspace_id', $workspace->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, status, count(*) as count')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        $dailyStats = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $dailyStats[$date] = [
                'queued' => 0,
                'sent' => 0,
                'delivered' => 0,
                'opened' => 0,
                'clicked' => 0,
                'bounced' => 0,
                'failed' => 0,
                'replied' => 0,
            ];
        }

        foreach ($messages as $msg) {
            if (isset($dailyStats[$msg->date])) {
                $dailyStats[$msg->date][$msg->status] = $msg->count;
            }
        }

        $totals = Message::where('workspace_id', $workspace->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return Inertia::render('Reports/Email', [
            'days' => $days,
            'dailyStats' => $dailyStats,
            'totals' => $totals,
        ]);
    }
}