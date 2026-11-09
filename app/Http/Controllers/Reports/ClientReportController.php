<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ClientReport;
use App\Models\Lead;
use App\Models\Message;
use App\Models\InboundEmailReply;
use App\Models\Deal;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ClientReportController extends Controller
{
    protected function getWorkspace(Request $request): Workspace
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace;
        if (!$workspace && $user) {
            $workspace = $user->workspaces()->first()
                ?? Workspace::where('is_active', true)->orderBy('name')->first()
                ?? Workspace::first();
            if ($workspace) {
                session(['current_workspace_id' => $workspace->id]);
                try {
                    $user->setCurrentWorkspace($workspace);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        if (!$workspace) {
            $workspace = Workspace::create([
                'name' => 'Default Workspace',
                'slug' => 'default-' . Str::random(6),
                'is_active' => true,
            ]);
            if ($user) {
                $user->workspaces()->attach($workspace->id, ['role' => 'admin']);
                $user->setCurrentWorkspace($workspace);
            }
        }

        return $workspace;
    }

    protected function ensureTables(): void
    {
        if (!Schema::hasTable('client_reports')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::error('ClientReportController ensureTables migration failed: ' . $e->getMessage());
            }
        }
    }

    public function index(Request $request): Response
    {
        $workspace = $this->getWorkspace($request);
        $this->ensureTables();

        $reports = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        try {
            if (Schema::hasTable('client_reports')) {
                $reports = ClientReport::where('workspace_id', $workspace->id)
                    ->with('creator:id,name')
                    ->latest()
                    ->paginate(15);
            }
        } catch (\Throwable $e) {
            Log::warning('ClientReportController index failed: ' . $e->getMessage());
        }

        return Inertia::render('Reports/ClientReports/Index', [
            'reports' => $reports,
        ]);
    }

    public function create(Request $request): Response
    {
        $workspace = $this->getWorkspace($request);
        $this->ensureTables();

        $start = $request->input('period_start', now()->subDays(30)->toDateString());
        $end   = $request->input('period_end', now()->toDateString());

        $leadsCount = 0;
        $messagesSent = 0;
        $messagesDelivered = 0;
        $replies = 0;
        $dealsWon = 0;

        try {
            if (Schema::hasTable('leads')) {
                $leadsCount = Lead::where('workspace_id', $workspace->id)
                    ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                    ->count();
            }
        } catch (\Throwable $e) {}

        try {
            if (Schema::hasTable('messages')) {
                $messagesSent = Message::where('workspace_id', $workspace->id)
                    ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                    ->where('status', '!=', 'failed')
                    ->count();

                $messagesDelivered = Message::where('workspace_id', $workspace->id)
                    ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                    ->whereIn('status', ['sent', 'delivered', 'opened', 'clicked', 'replied'])
                    ->count();
            }
        } catch (\Throwable $e) {}

        try {
            if (Schema::hasTable('inbound_email_replies')) {
                $replies = InboundEmailReply::whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])->count();
            }
        } catch (\Throwable $e) {}

        try {
            if (Schema::hasTable('deals')) {
                $dealsWon = Deal::where('workspace_id', $workspace->id)
                    ->where('status', 'won')
                    ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                    ->count();
            }
        } catch (\Throwable $e) {}

        return Inertia::render('Reports/ClientReports/Create', [
            'defaults' => [
                'period_start'       => $start,
                'period_end'         => $end,
                'leads_generated'    => $leadsCount,
                'prospects_contacted'=> $messagesSent,
                'delivered'          => $messagesDelivered,
                'replies_received'   => $replies,
                'meetings_booked'    => $dealsWon,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $this->getWorkspace($request);
        $this->ensureTables();

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'client_name'        => 'required|string|max:255',
            'period_start'       => 'required|date',
            'period_end'         => 'required|date|after_or_equal:period_start',
            'leads_generated'    => 'required|integer|min:0',
            'prospects_contacted'=> 'required|integer|min:0',
            'delivered'          => 'required|integer|min:0',
            'replies_received'   => 'required|integer|min:0',
            'meetings_booked'    => 'required|integer|min:0',
            'summary_notes'      => 'nullable|string',
        ]);

        $report = ClientReport::create([
            'workspace_id'  => $workspace->id,
            'title'         => $validated['title'],
            'client_name'   => $validated['client_name'],
            'period_start'  => $validated['period_start'],
            'period_end'    => $validated['period_end'],
            'metrics'       => [
                'leads_generated'     => $validated['leads_generated'],
                'prospects_contacted' => $validated['prospects_contacted'],
                'delivered'           => $validated['delivered'],
                'replies_received'    => $validated['replies_received'],
                'meetings_booked'     => $validated['meetings_booked'],
            ],
            'summary_notes' => $validated['summary_notes'],
            'created_by'    => $request->user()?->id,
        ]);

        return redirect()->route('reports.client-reports.show', $report->id)->with('success', 'Client Report generated successfully.');
    }

    public function show(Request $request, ClientReport $clientReport): Response
    {
        $workspace = $this->getWorkspace($request);
        abort_if($clientReport->workspace_id !== $workspace->id, 403);

        $clientReport->load(['creator:id,name', 'workspace:id,name']);

        return Inertia::render('Reports/ClientReports/Show', [
            'report' => $clientReport,
        ]);
    }
}
