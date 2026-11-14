<?php

namespace App\Http\Controllers\Automations;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationLog;
use App\Models\Campaign;
use App\Models\EmailAccount;
use App\Models\Lead;
use App\Models\Workspace;
use App\Services\WorkflowAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AutomationController extends Controller
{
    public function __construct(
        protected WorkflowAutomationService $automationService
    ) {}

    protected function getWorkspace(Request $request): ?Workspace
    {
        $user = $request->user();
        if (!$user) return null;

        $workspace = $user->currentWorkspace
            ?? $user->workspaces()->first()
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

        return $workspace;
    }

    public function index(Request $request): Response
    {
        $workspace = $this->getWorkspace($request);

        if (!$workspace) {
            return Inertia::render('Automations/Index', [
                'automations'   => [],
                'pendingLogs'   => [],
                'recentLogs'    => [],
                'campaigns'     => [],
                'emailAccounts' => [],
                'recentLeads'   => [],
                'stats'         => ['active' => 0, 'total_runs' => 0, 'pending' => 0],
            ]);
        }

        if (!Schema::hasTable('automations') || !Schema::hasTable('automation_logs')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::error('Automations auto-migration failed: ' . $e->getMessage());
            }
        }

        $automations = collect();
        $pendingLogs = collect();
        $recentLogs = collect();
        $campaigns = collect();
        $emailAccounts = collect();
        $recentLeads = collect();

        try {
            if (Schema::hasTable('automations')) {
                $automations = Automation::where('workspace_id', $workspace->id)
                    ->with(['creator:id,name'])
                    ->latest()
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed loading automations: ' . $e->getMessage());
        }

        try {
            if (Schema::hasTable('automation_logs')) {
                $pendingLogs = AutomationLog::where('workspace_id', $workspace->id)
                    ->where('status', 'pending_approval')
                    ->with([
                        'automation:id,name,action_type,trigger_event',
                        'lead:id,first_name,last_name,email,company_name',
                    ])
                    ->latest()
                    ->get();

                // Recent 50 execution runs for n8n Executions view
                $recentLogs = AutomationLog::where('workspace_id', $workspace->id)
                    ->with([
                        'automation:id,name,action_type,trigger_event',
                        'lead:id,first_name,last_name,email,company_name',
                    ])
                    ->latest()
                    ->limit(50)
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed loading automation logs: ' . $e->getMessage());
        }

        try {
            if (Schema::hasTable('campaigns')) {
                $campaigns = Campaign::where('workspace_id', $workspace->id)
                    ->select('id', 'name', 'status')
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed loading campaigns for automations: ' . $e->getMessage());
        }

        try {
            if (Schema::hasTable('email_accounts')) {
                $emailAccounts = EmailAccount::where('is_active', true)
                    ->select('id', 'name', 'email', 'from_name')
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed loading email accounts for automations: ' . $e->getMessage());
        }

        try {
            if (Schema::hasTable('leads')) {
                $recentLeads = Lead::where('workspace_id', $workspace->id)
                    ->select('id', 'first_name', 'last_name', 'email', 'company_name', 'score', 'pipeline_stage')
                    ->latest('id')
                    ->limit(20)
                    ->get()
                    ->map(fn($l) => [
                        'id' => $l->id,
                        'name' => trim(($l->first_name ?? '') . ' ' . ($l->last_name ?? '')) ?: 'Prospect',
                        'email' => $l->email,
                        'company' => $l->company_name ?? '',
                        'score' => $l->score ?? 0,
                        'stage' => $l->pipeline_stage ?? 'new',
                    ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed loading recent leads: ' . $e->getMessage());
        }

        $stats = [
            'active'     => $automations->where('is_active', true)->count(),
            'total_runs' => $recentLogs->count(),
            'pending'    => $pendingLogs->count(),
        ];

        return Inertia::render('Automations/Index', [
            'automations'   => $automations->values()->all(),
            'pendingLogs'   => $pendingLogs->values()->all(),
            'recentLogs'    => $recentLogs->values()->all(),
            'campaigns'     => $campaigns->values()->all(),
            'emailAccounts' => $emailAccounts->values()->all(),
            'recentLeads'   => $recentLeads->values()->all(),
            'stats'         => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'trigger_event'     => 'required|string',
            'trigger_config'    => 'nullable|array',
            'action_type'       => 'required|string',
            'action_config'     => 'nullable|array',
            'requires_approval' => 'boolean',
        ]);

        $workspace = $this->getWorkspace($request);
        abort_if(!$workspace, 403, 'No workspace found.');

        Automation::create([
            'workspace_id'      => $workspace->id,
            'created_by'        => $request->user()->id,
            'name'              => $request->name,
            'trigger_event'     => $request->trigger_event,
            'trigger_config'    => $request->trigger_config ?: [],
            'action_type'       => $request->action_type,
            'action_config'     => $request->action_config ?: [],
            'requires_approval' => $request->boolean('requires_approval', false),
            'is_active'         => true,
        ]);

        return back()->with('success', 'Workflow automation created successfully.');
    }

    public function update(Request $request, Automation $automation)
    {
        $workspace = $this->getWorkspace($request);
        abort_if(!$workspace || $automation->workspace_id !== $workspace->id, 403);

        $request->validate([
            'name'              => 'required|string|max:255',
            'trigger_event'     => 'required|string',
            'trigger_config'    => 'nullable|array',
            'action_type'       => 'required|string',
            'action_config'     => 'nullable|array',
            'requires_approval' => 'boolean',
            'is_active'         => 'boolean',
        ]);

        $automation->update([
            'name'              => $request->name,
            'trigger_event'     => $request->trigger_event,
            'trigger_config'    => $request->trigger_config ?: [],
            'action_type'       => $request->action_type,
            'action_config'     => $request->action_config ?: [],
            'requires_approval' => $request->boolean('requires_approval', false),
            'is_active'         => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Workflow automation updated successfully.');
    }

    public function testRun(Request $request, Automation $automation)
    {
        $workspace = $this->getWorkspace($request);
        abort_if(!$workspace || $automation->workspace_id !== $workspace->id, 403);

        $leadId = $request->input('lead_id');
        $lead = null;

        if ($leadId) {
            $lead = Lead::where('workspace_id', $workspace->id)->find($leadId);
        }

        if (!$lead) {
            $lead = Lead::where('workspace_id', $workspace->id)->latest('id')->first();
        }

        if (!$lead) {
            return back()->with('error', 'No lead found in workspace to test the automation with. Please create or import a lead first.');
        }

        try {
            $ok = $this->automationService->executeAction($automation, $lead, $request->user());
            if ($ok) {
                return back()->with('success', "Workflow executed successfully for test lead {$lead->email}!");
            }
            return back()->with('error', "Workflow test execution encountered an issue. Check execution logs.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Test run failed: ' . $e->getMessage());
        }
    }

    public function toggle(Request $request, Automation $automation)
    {
        $workspace = $this->getWorkspace($request);
        abort_if(!$workspace || $automation->workspace_id !== $workspace->id, 403);
        $automation->update(['is_active' => !$automation->is_active]);

        return back()->with('success', "Workflow is now " . ($automation->is_active ? 'Active' : 'Paused') . ".");
    }

    public function destroy(Request $request, Automation $automation)
    {
        $workspace = $this->getWorkspace($request);
        abort_if(!$workspace || $automation->workspace_id !== $workspace->id, 403);
        $automation->delete();

        return back()->with('success', 'Workflow deleted.');
    }

    public function approve(Request $request, AutomationLog $log)
    {
        $workspace = $this->getWorkspace($request);
        abort_if(!$workspace || $log->workspace_id !== $workspace->id, 403);

        if ($log->status !== 'pending_approval') {
            return back()->with('error', 'Log is not pending approval.');
        }

        $lead = $log->lead;
        if (!$lead) {
            $log->update(['status' => 'failed']);
            return back()->with('error', 'Associated lead no longer exists.');
        }

        $this->automationService->executeAction($log->automation, $lead, $request->user());
        $log->update([
            'status'      => 'executed',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Workflow action approved and executed.');
    }

    public function reject(Request $request, AutomationLog $log)
    {
        $workspace = $this->getWorkspace($request);
        abort_if(!$workspace || $log->workspace_id !== $workspace->id, 403);

        $log->update([
            'status'      => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Workflow action rejected.');
    }
}
