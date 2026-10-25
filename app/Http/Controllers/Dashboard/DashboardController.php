<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $totalLeads = Lead::where('workspace_id', $workspace->id)->count();
        $convertedLeads = Lead::where('workspace_id', $workspace->id)->where(fn($q) => $q->where('status', 'converted')->orWhere('pipeline_stage', 'won'))->count();

        $stats = [
            'total_leads' => $totalLeads,
            'new_leads' => Lead::where('workspace_id', $workspace->id)->where('status', 'new')->count(),
            'qualified_leads' => Lead::where('workspace_id', $workspace->id)->where('category', 'qualified')->count(),
            'interested_leads' => Lead::where('workspace_id', $workspace->id)->where('category', 'interested')->count(),
            'hot_leads' => Lead::where('workspace_id', $workspace->id)->where('category', 'hot')->count(),
            'converted_leads' => $convertedLeads,
            'conversion_rate' => $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0,
            'total_replies' => \App\Models\Message::where('workspace_id', $workspace->id)->whereNotNull('replied_at')->count(),
            'meetings_requested' => \App\Models\Message::where('workspace_id', $workspace->id)->where('reply_classification', 'meeting_requested')->count(),
            'active_campaigns' => Campaign::where('workspace_id', $workspace->id)
                ->whereIn('status', [Campaign::STATUS_ACTIVE, Campaign::STATUS_SCHEDULED])->count(),
            'open_deals' => Deal::where('workspace_id', $workspace->id)
                ->where('status', Deal::STATUS_OPEN)->count(),
            'pipeline_value' => Deal::where('workspace_id', $workspace->id)
                ->where('status', Deal::STATUS_OPEN)->sum('value'),
            'overdue_tasks' => Task::where('workspace_id', $workspace->id)
                ->where('status', '!=', Task::STATUS_COMPLETED)
                ->where('due_at', '<', now())->count(),
            'due_today_tasks' => Task::where('workspace_id', $workspace->id)
                ->where('status', '!=', Task::STATUS_COMPLETED)
                ->whereDate('due_at', today())->count(),
        ];

        // Lead score category distribution
        $leadCategories = [
            'hot' => Lead::where('workspace_id', $workspace->id)->where('category', 'hot')->count(),
            'interested' => Lead::where('workspace_id', $workspace->id)->where('category', 'interested')->count(),
            'qualified' => Lead::where('workspace_id', $workspace->id)->where('category', 'qualified')->count(),
            'cold' => Lead::where('workspace_id', $workspace->id)->where('category', 'cold')->count(),
            'converted' => $convertedLeads,
        ];

        // Sales Pipeline Stage breakdown
        $pipelineStages = [
            ['stage' => 'New Lead', 'count' => Lead::where('workspace_id', $workspace->id)->where('pipeline_stage', 'new_lead')->count()],
            ['stage' => 'Contacted', 'count' => Lead::where('workspace_id', $workspace->id)->where('pipeline_stage', 'contacted')->count()],
            ['stage' => 'Replied', 'count' => Lead::where('workspace_id', $workspace->id)->where('pipeline_stage', 'replied')->count()],
            ['stage' => 'Interested', 'count' => Lead::where('workspace_id', $workspace->id)->where('pipeline_stage', 'interested')->count()],
            ['stage' => 'Meeting Scheduled', 'count' => Lead::where('workspace_id', $workspace->id)->where('pipeline_stage', 'meeting_scheduled')->count()],
            ['stage' => 'Proposal Sent', 'count' => Lead::where('workspace_id', $workspace->id)->where('pipeline_stage', 'proposal_sent')->count()],
            ['stage' => 'Negotiation', 'count' => Lead::where('workspace_id', $workspace->id)->where('pipeline_stage', 'negotiation')->count()],
            ['stage' => 'Won', 'count' => Lead::where('workspace_id', $workspace->id)->where('pipeline_stage', 'won')->count()],
            ['stage' => 'Lost', 'count' => Lead::where('workspace_id', $workspace->id)->where('pipeline_stage', 'lost')->count()],
        ];

        // Reply classifications breakdown
        $replyBreakdown = \App\Models\Message::where('workspace_id', $workspace->id)
            ->whereNotNull('reply_classification')
            ->selectRaw('reply_classification as classification, count(*) as count')
            ->groupBy('reply_classification')
            ->pluck('count', 'classification');

        $recentLeads = Lead::where('workspace_id', $workspace->id)
            ->with(['company', 'owner'])
            ->latest()
            ->limit(5)
            ->get();

        $upcomingTasks = Task::where('workspace_id', $workspace->id)
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->where('due_at', '>=', now())
            ->with(['assignee', 'related'])
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        $overdueTasks = Task::where('workspace_id', $workspace->id)
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->where('due_at', '<', now())
            ->with(['assignee', 'related'])
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        $activeCampaigns = Campaign::where('workspace_id', $workspace->id)
            ->whereIn('status', [Campaign::STATUS_ACTIVE, Campaign::STATUS_SCHEDULED])
            ->withCount(['recipients as pending_count' => fn($q) => $q->where('status', 'pending')])
            ->withCount(['recipients as active_count' => fn($q) => $q->where('status', 'active')])
            ->withCount(['recipients as completed_count' => fn($q) => $q->where('status', 'completed')])
            ->limit(5)
            ->get();

        $pipelineDeals = Deal::where('workspace_id', $workspace->id)
            ->where('status', Deal::STATUS_OPEN)
            ->with('stage')
            ->get()
            ->groupBy('stage_id')
            ->map(function ($deals) {
                $stage = $deals->first()->stage;
                $value = (float) $deals->sum('value');
                $probability = $stage ? (float) ($stage->probability ?? 0) : 0;
                $weighted = ($value * $probability) / 100;

                return [
                    'stage' => $stage,
                    'count' => $deals->count(),
                    'value' => $value,
                    'weighted_value' => $weighted,
                ];
            })
            ->values();

        $emailStats = $this->getEmailStats($workspace->id);

        $leadSources = Lead::where('workspace_id', $workspace->id)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('source, count(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $leadsByStatus = Lead::where('workspace_id', $workspace->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recentLeads' => $recentLeads,
            'upcomingTasks' => $upcomingTasks,
            'overdueTasks' => $overdueTasks,
            'activeCampaigns' => $activeCampaigns,
            'pipelineDeals' => $pipelineDeals,
            'emailStats' => $emailStats,
            'leadSources' => $leadSources,
            'leadsByStatus' => $leadsByStatus,
            'leadCategories' => $leadCategories,
            'pipelineStages' => $pipelineStages,
            'replyBreakdown' => $replyBreakdown,
        ]);
    }

    private function getEmailStats(int $workspaceId): array
    {
        $days = 7;
        $stats = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $stats[$date] = [
                'sent' => 0,
                'delivered' => 0,
                'opened' => 0,
                'clicked' => 0,
                'bounced' => 0,
                'replied' => 0,
            ];
        }

        return $stats;
    }
}