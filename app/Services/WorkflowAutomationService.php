<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\AutomationLog;
use App\Models\Campaign;
use App\Models\CrmNotification;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;

class WorkflowAutomationService
{
    /**
     * Dispatch an event to all active automations matching the trigger in the workspace.
     */
    public function handleTrigger(Workspace $workspace, string $event, Lead $lead, array $payload = []): void
    {
        $automations = Automation::where('workspace_id', $workspace->id)
            ->where('trigger_event', $event)
            ->where('is_active', true)
            ->get();

        foreach ($automations as $automation) {
            $this->evaluateAndExecute($automation, $lead, $payload);
        }
    }

    /**
     * Evaluate trigger criteria and execute or queue for approval.
     */
    public function evaluateAndExecute(Automation $automation, Lead $lead, array $payload = []): void
    {
        $config = $automation->trigger_config ?? [];

        // Check conditions
        if ($automation->trigger_event === 'score_reached') {
            $minScore = (int) ($config['min_score'] ?? 50);
            if (($lead->score ?? 0) < $minScore) {
                return;
            }
        } elseif ($automation->trigger_event === 'reply_received') {
            $sentiment = $payload['sentiment'] ?? $lead->reply_status;
            $requiredSentiment = $config['sentiment'] ?? null;
            if ($requiredSentiment && $requiredSentiment !== 'any' && $sentiment !== $requiredSentiment) {
                return;
            }
        }

        // Check if manual approval is required
        if ($automation->requires_approval) {
            AutomationLog::create([
                'workspace_id'  => $automation->workspace_id,
                'automation_id' => $automation->id,
                'lead_id'       => $lead->id,
                'status'        => 'pending_approval',
                'details'       => [
                    'lead_name'     => $lead->display_name,
                    'lead_email'    => $lead->email,
                    'trigger_event' => $automation->trigger_event,
                    'action_type'   => $automation->action_type,
                    'action_config' => $automation->action_config,
                ],
            ]);

            CrmNotification::create([
                'workspace_id' => $automation->workspace_id,
                'user_id'      => $automation->created_by,
                'title'        => "Automation Approval Required: {$automation->name}",
                'message'      => "Action on lead {$lead->display_name} requires your review and approval.",
                'type'         => 'automation_approval',
                'action_url'   => route('automations.index'),
                'is_read'      => false,
            ]);
            return;
        }

        // Execute action directly
        $this->executeAction($automation, $lead);
    }

    /**
     * Execute the configured automation action on a lead.
     */
    public function executeAction(Automation $automation, Lead $lead, ?User $approver = null): bool
    {
        $action = $automation->action_type;
        $config = $automation->action_config ?? [];

        try {
            switch ($action) {
                case 'assign_campaign':
                    $campaignId = $config['campaign_id'] ?? null;
                    if ($campaignId) {
                        $campaign = Campaign::where('workspace_id', $automation->workspace_id)->find($campaignId);
                        if ($campaign) {
                            $lead->update(['assigned_campaign_id' => $campaign->id]);
                            app(CampaignService::class)->addRecipients($campaign, [$lead->id]);
                        }
                    }
                    break;

                case 'create_task':
                    Task::create([
                        'workspace_id' => $automation->workspace_id,
                        'user_id'      => $lead->owner_id ?: $automation->created_by,
                        'title'        => $config['task_title'] ?? "Follow up with {$lead->display_name}",
                        'description'  => $config['task_description'] ?? "Auto-scheduled via automation rule: {$automation->name}",
                        'due_date'     => now()->addDays((int) ($config['due_days'] ?? 1)),
                        'priority'     => $config['priority'] ?? 'high',
                        'status'       => 'pending',
                        'related_type' => Lead::class,
                        'related_id'   => $lead->id,
                    ]);
                    break;

                case 'notify_owner':
                    CrmNotification::create([
                        'workspace_id' => $automation->workspace_id,
                        'user_id'      => $lead->owner_id ?: $automation->created_by,
                        'title'        => $config['notification_title'] ?? "Automation Alert: {$automation->name}",
                        'message'      => "Rule matched for {$lead->display_name} ({$lead->email}).",
                        'type'         => 'lead_alert',
                        'action_url'   => route('leads.show', $lead->id),
                        'is_read'      => false,
                    ]);
                    break;

                case 'update_stage':
                    $stage = $config['pipeline_stage'] ?? null;
                    if ($stage) {
                        $lead->update(['pipeline_stage' => $stage]);
                    }
                    break;
            }

            // Log execution
            AutomationLog::create([
                'workspace_id'  => $automation->workspace_id,
                'automation_id' => $automation->id,
                'lead_id'       => $lead->id,
                'status'        => 'executed',
                'approved_by'   => $approver?->id,
                'approved_at'   => $approver ? now() : null,
                'details'       => [
                    'lead_name'   => $lead->display_name,
                    'action_type' => $action,
                    'result'      => 'success',
                ],
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error("WorkflowAutomationService execution failed: " . $e->getMessage());
            AutomationLog::create([
                'workspace_id'  => $automation->workspace_id,
                'automation_id' => $automation->id,
                'lead_id'       => $lead->id,
                'status'        => 'failed',
                'details'       => ['error' => $e->getMessage()],
            ]);
            return false;
        }
    }
}
