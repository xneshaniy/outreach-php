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

        $stats = [
            'total_leads' => Lead::where('workspace_id', $workspace->id)->count(),
            'new_leads' => Lead::where('workspace_id', $workspace->id)->where('status', 'new')->count(),
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
            ->map(fn($deals) => [
                'stage' => $deals->first()->stage,
                'count' => $deals->count(),
                'value' => $deals->sum('value'),
            ])
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