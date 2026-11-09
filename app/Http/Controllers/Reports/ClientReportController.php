<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ClientReport;
use App\Models\Lead;
use App\Models\Message;
use App\Models\InboundEmailReply;
use App\Models\Deal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientReportController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;
        if (!$workspace) {
            return Inertia::render('Reports/ClientReports/Index', [
                'reports' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15),
            ]);
        }

        $reports = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('client_reports')) {
                $reports = ClientReport::where('workspace_id', $workspace->id)
                    ->with('creator:id,name')
                    ->latest()
                    ->paginate(15);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ClientReportController index failed: ' . $e->getMessage());
        }

        return Inertia::render('Reports/ClientReports/Index', [
            'reports' => $reports,
        ]);
    }

    public function create(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $start = $request->input('period_start', now()->subDays(30)->toDateString());
        $end   = $request->input('period_end', now()->toDateString());

        $leadsCount = Lead::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->count();

        $messagesSent = Message::whereHas('campaign', fn($c) => $c->where('workspace_id', $workspace->id))
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->where('status', '!=', 'failed')
            ->count();

        $messagesDelivered = Message::whereHas('campaign', fn($c) => $c->where('workspace_id', $workspace->id))
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->whereIn('status', ['sent', 'delivered', 'opened', 'clicked', 'replied'])
            ->count();

        $replies = InboundEmailReply::whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])->count();

        $dealsWon = Deal::where('workspace_id', $workspace->id)
            ->where('status', 'won')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->count();

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
        $workspace = $request->user()->currentWorkspace;

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
            'created_by'    => $request->user()->id,
        ]);

        return redirect()->route('reports.client-reports.show', $report->id)->with('success', 'Client Report generated successfully.');
    }

    public function show(Request $request, ClientReport $clientReport): Response
    {
        $workspace = $request->user()->currentWorkspace;
        abort_if($clientReport->workspace_id !== $workspace->id, 403);

        $clientReport->load(['creator:id,name', 'workspace:id,name']);

        return Inertia::render('Reports/ClientReports/Show', [
            'report' => $clientReport,
        ]);
    }
}
