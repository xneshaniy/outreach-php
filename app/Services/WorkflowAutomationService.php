<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\AutomationLog;
use App\Models\Campaign;
use App\Models\CrmNotification;
use App\Models\EmailAccount;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            if (($lead->score ?? $lead->lead_quality_score ?? 0) < $minScore) {
                return;
            }
        } elseif ($automation->trigger_event === 'reply_received') {
            $sentiment = $payload['sentiment'] ?? $lead->reply_status;
            $requiredSentiment = $config['sentiment'] ?? null;
            if ($requiredSentiment && $requiredSentiment !== 'any' && $sentiment !== $requiredSentiment) {
                return;
            }
        } elseif ($automation->trigger_event === 'lead_created') {
            // Optional minimum score filter on new leads
            if (!empty($config['min_score'])) {
                if (($lead->score ?? $lead->lead_quality_score ?? 0) < (int)$config['min_score']) {
                    return;
                }
            }
        }

        // Additional filter / conditional check if specified
        if (!empty($config['condition_field'])) {
            $field = $config['condition_field'];
            $operator = $config['condition_operator'] ?? 'equals';
            $expectedVal = strtolower((string)($config['condition_value'] ?? ''));
            $actualVal = strtolower((string)($lead->{$field} ?? ''));

            $matched = match ($operator) {
                'equals' => $actualVal === $expectedVal,
                'not_equals' => $actualVal !== $expectedVal,
                'contains' => str_contains($actualVal, $expectedVal),
                'greater_than' => (float)$actualVal > (float)$expectedVal,
                'less_than' => (float)$actualVal < (float)$expectedVal,
                default => true,
            };

            if (!$matched) {
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
                    'lead_name'     => $lead->display_name ?? ($lead->first_name . ' ' . $lead->last_name),
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
                'message'      => "Action on lead {$lead->email} requires your review and approval.",
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
     * Execute the configured automation action on a lead. Supports multi-step pipelines.
     */
    public function executeAction(Automation $automation, Lead $lead, ?User $approver = null): bool
    {
        $config = $automation->action_config ?? [];
        $steps = $config['steps'] ?? [];

        try {
            $executedSteps = [];

            // If multi-step n8n pipeline is configured
            if (!empty($steps) && is_array($steps)) {
                foreach ($steps as $step) {
                    $stepType = $step['action_type'] ?? $step['type'] ?? null;
                    $stepConfig = $step['action_config'] ?? $step['config'] ?? [];
                    if ($stepType) {
                        $this->executeSingleAction($automation, $stepType, $stepConfig, $lead);
                        $executedSteps[] = $stepType;
                    }
                }
            } else {
                $this->executeSingleAction($automation, $automation->action_type, $config, $lead);
                $executedSteps[] = $automation->action_type;
            }

            // Log successful execution
            AutomationLog::create([
                'workspace_id'  => $automation->workspace_id,
                'automation_id' => $automation->id,
                'lead_id'       => $lead->id,
                'status'        => 'executed',
                'approved_by'   => $approver?->id,
                'approved_at'   => $approver ? now() : null,
                'details'       => [
                    'lead_name'      => $lead->display_name ?? ($lead->first_name . ' ' . $lead->last_name),
                    'lead_email'     => $lead->email,
                    'action_type'    => $automation->action_type,
                    'executed_steps' => $executedSteps,
                    'result'         => 'success',
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

    /**
     * Execute a discrete single action node.
     */
    protected function executeSingleAction(Automation $automation, string $action, array $config, Lead $lead): void
    {
        switch ($action) {
            case 'send_email':
                $accountId = $config['email_account_id'] ?? null;
                $account = null;
                if ($accountId) {
                    $account = EmailAccount::where('id', $accountId)->first();
                }
                if (!$account) {
                    $account = EmailAccount::where('is_active', true)->first();
                }

                if ($account && !empty($lead->email)) {
                    $rawSubject = $config['subject'] ?? "Quick update regarding {$lead->company_name}";
                    $rawBody = $config['body'] ?? "Hi {{first_name}},\n\nHope you are having a productive week.\n\nBest regards,\n{{sender_name}}";

                    // Interpolate variables
                    $senderName = $account->from_name ?? $account->name ?? 'Our Team';
                    $vars = [
                        '{{first_name}}'  => $lead->first_name ?: 'there',
                        '{{last_name}}'   => $lead->last_name ?: '',
                        '{{email}}'       => $lead->email,
                        '{{company}}'     => $lead->company_name ?: ($lead->company?->name ?: 'your team'),
                        '{{job_title}}'   => $lead->job_title ?: 'Professional',
                        '{{sender_name}}' => $senderName,
                    ];
                    $subject = str_replace(array_keys($vars), array_values($vars), $rawSubject);
                    $body = str_replace(array_keys($vars), array_values($vars), $rawBody);
                    $html = nl2br(e($body));

                    $emailService = app(EmailService::class);
                    $result = $emailService->send(
                        account: $account,
                        to: [['email' => $lead->email, 'name' => $lead->display_name ?? $lead->first_name]],
                        subject: $subject,
                        html: $html,
                        text: $body
                    );

                    Message::create([
                        'workspace_id'     => $automation->workspace_id,
                        'email_account_id' => $account->id,
                        'lead_id'          => $lead->id,
                        'message_id'       => $result['message_id'] ?? ('auto_' . Str::random(16)),
                        'subject'          => $subject,
                        'body_html'        => $html,
                        'body_text'        => $body,
                        'from_email'       => $account->email,
                        'from_name'        => $senderName,
                        'to_emails'        => [['email' => $lead->email, 'name' => $lead->display_name ?? $lead->first_name]],
                        'status'           => Message::STATUS_SENT,
                        'sent_at'          => now(),
                    ]);

                    $lead->update(['last_contacted_at' => now()]);
                }
                break;

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
                    'title'        => $config['task_title'] ?? "Follow up with " . ($lead->display_name ?? $lead->email),
                    'description'  => $config['task_description'] ?? "Auto-scheduled via n8n automation flow: {$automation->name}",
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
                    'title'        => $config['notification_title'] ?? "Automation Triggered: {$automation->name}",
                    'message'      => "Rule matched for " . ($lead->display_name ?? $lead->email) . ".",
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

            case 'ai_enrich':
                try {
                    $workspace = Workspace::find($automation->workspace_id);
                    if ($workspace) {
                        app(LeadService::class)->autoFillMissingLeadDetails($lead, $workspace);
                    }
                } catch (\Throwable $e) {
                    Log::warning("AI enrich failed in automation: " . $e->getMessage());
                }
                break;

            case 'tag_lead':
                $tag = $config['tag'] ?? null;
                if ($tag) {
                    $currentTags = is_array($lead->tags) ? $lead->tags : (json_decode($lead->tags ?? '[]', true) ?: []);
                    if (!in_array($tag, $currentTags)) {
                        $currentTags[] = $tag;
                        $lead->update(['tags' => $currentTags]);
                    }
                }
                break;

            case 'webhook_call':
                $url = $config['webhook_url'] ?? null;
                if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                    Http::timeout(5)->post($url, [
                        'event'      => $automation->trigger_event,
                        'automation' => $automation->name,
                        'lead'       => [
                            'id'         => $lead->id,
                            'name'       => $lead->display_name,
                            'email'      => $lead->email,
                            'company'    => $lead->company_name,
                            'stage'      => $lead->pipeline_stage,
                            'score'      => $lead->score,
                        ],
                        'timestamp'  => now()->toIso8601String(),
                    ]);
                }
                break;
        }
    }
}
