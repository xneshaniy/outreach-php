<?php

namespace App\Http\Controllers\Automations;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationLog;
use App\Models\Campaign;
use App\Services\WorkflowAutomationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AutomationController extends Controller
{
    public function __construct(
        protected WorkflowAutomationService $automationService
    ) {}

    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $automations = Automation::where('workspace_id', $workspace->id)
            ->with(['creator:id,name'])
            ->latest()
            ->get();

        $pendingLogs = AutomationLog::where('workspace_id', $workspace->id)
            ->where('status', 'pending_approval')
            ->with(['automation:id,name,action_type', 'lead:id,first_name,last_name,email,company_name'])
            ->latest()
            ->get();

        $campaigns = Campaign::where('workspace_id', $workspace->id)->select('id', 'name')->get();

        return Inertia::render('Automations/Index', [
            'automations' => $automations,
            'pendingLogs' => $pendingLogs,
            'campaigns'   => $campaigns,
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

        $workspace = $request->user()->currentWorkspace;

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

    public function toggle(Automation $automation)
    {
        $this->authorize('update', $automation);
        $automation->update(['is_active' => !$automation->is_active]);

        return back()->with('success', "Automation is now " . ($automation->is_active ? 'Active' : 'Paused') . ".");
    }

    public function destroy(Automation $automation)
    {
        $this->authorize('delete', $automation);
        $automation->delete();

        return back()->with('success', 'Automation deleted.');
    }

    public function approve(Request $request, AutomationLog $log)
    {
        $workspace = $request->user()->currentWorkspace;
        abort_if($log->workspace_id !== $workspace->id, 403);

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
        $workspace = $request->user()->currentWorkspace;
        abort_if($log->workspace_id !== $workspace->id, 403);

        $log->update([
            'status'      => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Automation action rejected.');
    }
}
