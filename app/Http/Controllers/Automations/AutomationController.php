<?php

namespace App\Http\Controllers\Automations;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationLog;
use App\Models\Campaign;
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
                'automations' => [],
                'pendingLogs' => [],
                'campaigns'   => [],
            ]);
        }

        // Auto-run migrations if automations tables are not yet created on this deployment
        if (!Schema::hasTable('automations') || !Schema::hasTable('automation_logs')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::error('Automations auto-migration failed: ' . $e->getMessage());
            }
        }

        $automations = collect();
        $pendingLogs = collect();
        $campaigns = collect();

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
                        'automation:id,name,action_type',
                        'lead:id,first_name,last_name,email',
                    ])
                    ->latest()
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed loading automation logs: ' . $e->getMessage());
        }

        try {
            if (Schema::hasTable('campaigns')) {
                $campaigns = Campaign::where('workspace_id', $workspace->id)->select('id', 'name')->get();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed loading campaigns for automations: ' . $e->getMessage());
        }

        return Inertia::render('Automations/Index', [
            'automations' => $automations->values()->all(),
            'pendingLogs' => $pendingLogs->values()->all(),
            'campaigns'   => $campaigns->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'trigger_event'     => 'required|in:lead_created,score_reached,reply_received',
            'trigger_config'    => 'nullable|array',
            'action_type'       => 'required|in:assign_campaign,create_task,notify_owner,update_stage',
            'action_config'     => 'nullable|array',
            'requires_approval' => 'boolean',
        ]);

        $workspace = $this->getWorkspace($request);
        abort_if(!$workspace, 403, 'No workspace found.');

        if (!Schema::hasTable('automations')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::error('Automations migration failed on store: ' . $e->getMessage());
            }
        }

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

        return back()->with('success', 'Automation workflow created successfully.');
    }

    public function toggle(Request $request, Automation $automation)
    {
        $workspace = $this->getWorkspace($request);
        abort_if(!$workspace || $automation->workspace_id !== $workspace->id, 403);
        $automation->update(['is_active' => !$automation->is_active]);

        return back()->with('success', "Automation is now " . ($automation->is_active ? 'Active' : 'Paused') . ".");
    }

    public function destroy(Request $request, Automation $automation)
    {
        $workspace = $this->getWorkspace($request);
        abort_if(!$workspace || $automation->workspace_id !== $workspace->id, 403);
        $automation->delete();

        return back()->with('success', 'Automation deleted.');
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

        return back()->with('success', 'Automation action approved and executed.');
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

        return back()->with('success', 'Automation action rejected.');
    }
}

