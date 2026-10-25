<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CampaignStep;
use App\Models\EmailAccount;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\Message;
use App\Models\SuppressionList;
use App\Models\Workspace;
use App\Jobs\SendCampaignEmailJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampaignService
{
    public function createCampaign(array $data): Campaign
    {
        return DB::transaction(function () use ($data) {
            $campaign = Campaign::create([
                'workspace_id' => $data['workspace_id'],
                'created_by' => $data['created_by'],
                'email_account_id' => $data['email_account_id'] ?? null,
                'lead_list_id' => $data['lead_list_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => Campaign::STATUS_DRAFT,
                'distribution_strategy' => $data['distribution_strategy'] ?? 'round_robin',
                'start_date' => $data['start_date'] ?? null,
                'start_time' => $data['start_time'] ?? null,
                'timezone' => $data['timezone'] ?? 'UTC',
                'sending_window' => $data['sending_window'] ?? null,
                'daily_send_limit' => $data['daily_send_limit'] ?? 200,
                'delay_between_emails' => $data['delay_between_emails'] ?? 60,
            ]);

            // Sync multiple email accounts
            $accountIds = $data['email_account_ids'] ?? [];
            if (empty($accountIds) && !empty($data['email_account_id'])) {
                $accountIds = [$data['email_account_id']];
            }

            if (!empty($accountIds)) {
                $allocations = $data['allocations'] ?? [];
                $pivotData = [];
                foreach ($accountIds as $accId) {
                    $pivotData[$accId] = [
                        'allocation_count' => $allocations[$accId] ?? null,
                        'sent_count' => 0,
                        'failed_count' => 0,
                    ];
                }
                $campaign->emailAccounts()->sync($pivotData);

                // Set primary email_account_id for fallback
                if (empty($campaign->email_account_id)) {
                    $campaign->update(['email_account_id' => $accountIds[0]]);
                }
            }

            if (!empty($data['steps'])) {
                foreach ($data['steps'] as $index => $stepData) {
                    $this->createStep($campaign, $index + 1, $stepData);
                }
            }

            return $campaign->load('steps');
        });
    }

    public function updateCampaign(Campaign $campaign, array $data): Campaign
    {
        return DB::transaction(function () use ($campaign, $data) {
            $campaign->update($data);

            if (isset($data['email_account_ids'])) {
                $accountIds = $data['email_account_ids'];
                $allocations = $data['allocations'] ?? [];
                $pivotData = [];
                foreach ($accountIds as $accId) {
                    $pivotData[$accId] = [
                        'allocation_count' => $allocations[$accId] ?? null,
                    ];
                }
                $campaign->emailAccounts()->sync($pivotData);

                if (!empty($accountIds) && (empty($campaign->email_account_id) || !in_array($campaign->email_account_id, $accountIds))) {
                    $campaign->update(['email_account_id' => $accountIds[0]]);
                }
            }

            return $campaign->load(['steps', 'emailAccounts']);
        });
    }

    public function duplicateCampaign(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign) {
            $newCampaign = $campaign->replicate([
                'status', 'scheduled_at', 'started_at', 'completed_at', 'paused_at',
            ]);
            $newCampaign->name = $campaign->name . ' (Copy)';
            $newCampaign->status = Campaign::STATUS_DRAFT;
            $newCampaign->save();

            // Duplicate steps
            foreach ($campaign->steps as $step) {
                $newStep = $step->replicate();
                $newStep->campaign_id = $newCampaign->id;
                $newStep->save();
            }

            // Duplicate attached email accounts
            $pivotData = [];
            foreach ($campaign->campaignEmailAccounts as $pivot) {
                $pivotData[$pivot->email_account_id] = [
                    'allocation_count' => $pivot->allocation_count,
                    'sent_count' => 0,
                    'failed_count' => 0,
                ];
            }
            if (!empty($pivotData)) {
                $newCampaign->emailAccounts()->sync($pivotData);
            }

            return $newCampaign->load(['steps', 'emailAccounts']);
        });
    }

    public function createStep(Campaign $campaign, int $order, array $data): CampaignStep
    {
        return CampaignStep::create([
            'campaign_id' => $campaign->id,
            'email_account_id' => $data['email_account_id'] ?? $campaign->email_account_id,
            'step_order' => $order,
            'name' => $data['name'],
            'subject' => $data['subject'],
            'body_html' => $data['body_html'],
            'body_text' => $data['body_text'] ?? null,
            'delay_days' => $data['delay_days'] ?? 0,
            'delay_hours' => $data['delay_hours'] ?? 0,
            'delay_minutes' => $data['delay_minutes'] ?? 0,
            'conditions' => $data['conditions'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function addRecipients(Campaign $campaign, array $leadIds): int
    {
        $added = 0;

        DB::transaction(function () use ($campaign, $leadIds, &$added) {
            $existingLeadIds = $campaign->recipients()->pluck('lead_id')->toArray();
            $newLeadIds = array_diff($leadIds, $existingLeadIds);

            foreach (array_chunk($newLeadIds, 100) as $chunk) {
                $records = [];
                foreach ($chunk as $leadId) {
                    $records[] = [
                        'campaign_id' => $campaign->id,
                        'lead_id' => $leadId,
                        'status' => CampaignRecipient::STATUS_PENDING,
                        'current_step' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($records) {
                    CampaignRecipient::insert($records);
                    $added += count($records);
                }
            }
        });

        return $added;
    }

    public function addRecipientsFromList(Campaign $campaign, LeadList $list): int
    {
        $leadIds = $list->leads()->pluck('id')->toArray();
        return $this->addRecipients($campaign, $leadIds);
    }

    public function scheduleCampaign(Campaign $campaign, ?Carbon $at = null): void
    {
        $campaign->update([
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => $at ?? now(),
        ]);
    }

    public function activateCampaign(Campaign $campaign): void
    {
        // Allow activation if there are multi-account pivot accounts OR a single email_account_id
        $hasMultiAccounts = $campaign->emailAccounts()->where('is_active', true)->exists();
        $hasSingleAccount = $campaign->emailAccount && $campaign->emailAccount->is_active;

        if (!$hasMultiAccounts && !$hasSingleAccount) {
            throw new \Exception('Campaign requires at least one active email account. Please add an email account before starting.');
        }

        if (!$campaign->steps()->where('is_active', true)->exists()) {
            throw new \Exception('Campaign must have at least one active step (email sequence).');
        }

        // Recipients are optional at activation time: a campaign can be turned
        // on and will simply have nothing to send until recipients are added.
        $campaign->activate();

        // Schedule first step for all pending recipients
        $this->scheduleFirstStep($campaign);
    }

    public function scheduleFirstStep(Campaign $campaign): void
    {
        $firstStep = $campaign->steps()->where('is_active', true)->orderBy('step_order')->first();
        if (!$firstStep) return;

        $delay = $firstStep->getTotalDelayMinutes();
        $sendAt = now()->addMinutes($delay);

        // Apply sending window
        $window = $campaign->sending_window;
        if ($window && isset($window['start'], $window['end'])) {
            $sendAt = $this->applySendingWindow($sendAt, $window, $campaign->timezone);
        }

        $campaign->pendingRecipients()->update([
            'status' => CampaignRecipient::STATUS_ACTIVE,
            'current_step' => $firstStep->step_order,
            'next_send_at' => $sendAt,
        ]);
    }

    public function processDueEmails(Campaign $campaign, int $batchSize = 50): int
    {
        if (!$campaign->canSend()) {
            return 0;
        }

        // Check daily limit for campaign
        $sentToday = $campaign->recipients()
            ->whereDate('last_sent_at', today())
            ->sum('emails_sent');

        if ($sentToday >= $campaign->daily_send_limit) {
            return 0;
        }

        $remainingLimit = $campaign->daily_send_limit - $sentToday;
        $batchSize = min($batchSize, $remainingLimit);

        // Get assigned active email accounts
        $emailAccounts = $campaign->emailAccounts()
            ->where('is_active', true)
            ->get();

        if ($emailAccounts->isEmpty() && $campaign->emailAccount && $campaign->emailAccount->is_active) {
            $emailAccounts = collect([$campaign->emailAccount]);
        }

        $availableAccounts = $emailAccounts->filter(fn($acc) => $acc->canSendToday())->values();
        if ($availableAccounts->isEmpty()) {
            return 0;
        }

        $recipients = $campaign->recipients()
            ->whereIn('status', [CampaignRecipient::STATUS_PENDING, CampaignRecipient::STATUS_ACTIVE])
            ->where('next_send_at', '<=', now())
            ->whereHas('lead', fn($q) => $q->where('status', '!=', 'archived'))
            ->limit($batchSize)
            ->get();

        $sent = 0;
        $roundRobinIndex = 0;
        $strategy = $campaign->distribution_strategy ?? 'round_robin';

        foreach ($recipients as $recipient) {
            // Check suppression
            if (SuppressionList::isSuppressed($campaign->workspace_id, $recipient->lead->email)) {
                $recipient->markSuppressed();
                continue;
            }

            // Pick account using distribution strategy
            $account = $this->selectEmailAccountForRecipient(
                $campaign,
                $availableAccounts,
                $strategy,
                $roundRobinIndex
            );

            if (!$account) {
                break;
            }

            $roundRobinIndex++;

            // Dispatch send job with chosen account
            $this->dispatchSendJob($recipient, $account->id);
            $sent++;

            if (!$account->canSendToday()) {
                $availableAccounts = $availableAccounts->filter(fn($a) => $a->id !== $account->id)->values();
                if ($availableAccounts->isEmpty()) {
                    break;
                }
            }
        }

        return $sent;
    }

    public function selectEmailAccountForRecipient(
        Campaign $campaign,
        \Illuminate\Support\Collection $availableAccounts,
        string $strategy,
        int $index
    ): ?EmailAccount {
        if ($availableAccounts->isEmpty()) {
            return null;
        }

        switch ($strategy) {
            case 'account_based':
                foreach ($availableAccounts as $acc) {
                    $pivot = $campaign->campaignEmailAccounts()->where('email_account_id', $acc->id)->first();
                    $allocation = $pivot?->allocation_count;
                    if ($allocation === null || ($pivot?->sent_count < $allocation)) {
                        return $acc;
                    }
                }
                return $availableAccounts->sortByDesc(fn($a) => $a->getRemainingToday())->first();

            case 'capacity_based':
                return $availableAccounts->sortByDesc(fn($a) => $a->getRemainingToday())->first();

            case 'round_robin':
            default:
                $count = $availableAccounts->count();
                return $availableAccounts[$index % $count];
        }
    }

    public function dispatchSendJob(CampaignRecipient $recipient, ?int $emailAccountId = null): void
    {
        $step = $recipient->campaign->steps()
            ->where('step_order', $recipient->current_step)
            ->where('is_active', true)
            ->first();

        if (!$step) {
            $recipient->complete();
            return;
        }

        $idempotencyKey = "campaign_{$recipient->campaign_id}_lead_{$recipient->lead_id}_step_{$step->step_order}";

        // Check if already sent (idempotency)
        if (Message::where('idempotency_key', $idempotencyKey)->exists()) {
            $recipient->scheduleNextStep();
            return;
        }

        SendCampaignEmailJob::dispatch($recipient, $step, $idempotencyKey, $emailAccountId)
            ->onQueue('emails');
    }

    public function processStepCompletion(CampaignRecipient $recipient, bool $success): void
    {
        if (!$success) {
            $recipient->markFailed();
            return;
        }

        $recipient->recordSent();
        $nextStep = $recipient->nextStep();

        if (!$nextStep) {
            $recipient->complete();
            return;
        }

        $delay = $nextStep->getTotalDelayMinutes();
        $sendAt = now()->addMinutes($delay);

        $window = $recipient->campaign->sending_window;
        if ($window && isset($window['start'], $window['end'])) {
            $sendAt = $this->applySendingWindow($sendAt, $window, $recipient->campaign->timezone);
        }

        $recipient->update([
            'current_step' => $nextStep->step_order,
            'next_send_at' => $sendAt,
        ]);
    }

    public function handleReply(CampaignRecipient $recipient, string $replyContent = ''): void
    {
        $recipient->markReplied();
        $lead = $recipient->lead;
        $workspace = $recipient->campaign->workspace;

        $classification = 'replied';
        if (!empty($replyContent)) {
            $analysis = app(\App\Services\AIService::class)->classifyReply($workspace, $replyContent);
            $classification = $analysis['classification'] ?? 'replied';

            // Store classified message record if relevant
            if ($recipient->campaign && $lead) {
                \App\Models\Message::create([
                    'workspace_id' => $workspace->id,
                    'email_account_id' => $recipient->email_account_id ?: $recipient->campaign->email_account_id,
                    'campaign_id' => $recipient->campaign_id,
                    'lead_id' => $lead->id,
                    'subject' => "Re: " . ($recipient->campaign->name ?? 'Campaign outreach'),
                    'body_html' => nl2br(e($replyContent)),
                    'body_text' => $replyContent,
                    'from_email' => $lead->email,
                    'from_name' => $lead->full_name,
                    'to_emails' => [['email' => $recipient->campaign->emailAccount?->email ?? 'admin@outreach.click']],
                    'status' => 'delivered',
                    'replied_at' => now(),
                    'reply_classification' => $classification,
                    'classification_reason' => $analysis['reason'] ?? null,
                    'return_date' => $analysis['return_date'] ?? null,
                ]);
            }
        }

        // Action on classifications
        if ($classification === 'unsubscribe') {
            $this->handleUnsubscribe($recipient);
            return;
        }

        if (in_array($classification, ['interested', 'positive', 'meeting_requested'])) {
            $lead->update([
                'status' => 'qualified',
                'pipeline_stage' => $classification === 'meeting_requested' ? 'meeting_scheduled' : 'interested',
            ]);
            app(\App\Services\LeadScoreService::class)->calculateScore($lead);

            // Notify admin
            app(\App\Services\NotificationService::class)->notify(
                $workspace,
                $classification === 'meeting_requested' ? 'meeting_requested' : 'positive_reply',
                $classification === 'meeting_requested' ? "Meeting Requested by {$lead->full_name}" : "Positive Reply from {$lead->full_name}",
                "Lead {$lead->full_name} ({$lead->email}) responded positively to campaign '{$recipient->campaign->name}'.",
                ['lead_id' => $lead->id, 'campaign_id' => $recipient->campaign_id]
            );
        } else {
            $lead->update([
                'status' => 'contacted',
                'pipeline_stage' => 'replied',
            ]);
            app(\App\Services\LeadScoreService::class)->calculateScore($lead);
        }

        // Add to suppression list for this campaign
        SuppressionList::suppress(
            $recipient->campaign->workspace_id,
            $recipient->lead->email,
            SuppressionList::REASON_REPLY,
            [
                'source' => "campaign_{$recipient->campaign_id}",
                'campaign_id' => $recipient->campaign_id,
            ]
        );
    }

    public function handleBounce(CampaignRecipient $recipient): void
    {
        $recipient->markBounced();

        SuppressionList::suppress(
            $recipient->campaign->workspace_id,
            $recipient->lead->email,
            SuppressionList::REASON_BOUNCE,
            [
                'source' => "campaign_{$recipient->campaign_id}",
                'campaign_id' => $recipient->campaign_id,
            ]
        );
    }

    public function handleUnsubscribe(CampaignRecipient $recipient): void
    {
        $recipient->markUnsubscribed();

        SuppressionList::suppress(
            $recipient->campaign->workspace_id,
            $recipient->lead->email,
            SuppressionList::REASON_UNSUBSCRIBE,
            [
                'source' => "campaign_{$recipient->campaign_id}",
                'campaign_id' => $recipient->campaign_id,
            ]
        );

        app(\App\Services\NotificationService::class)->notify(
            $recipient->campaign->workspace,
            'unsubscribe',
            "New Unsubscribe: {$recipient->lead->email}",
            "{$recipient->lead->full_name} ({$recipient->lead->email}) opted out of future emails.",
            ['lead_id' => $recipient->lead->id, 'campaign_id' => $recipient->campaign_id]
        );
    }

    public function applySendingWindow(Carbon $dateTime, array $window, string $timezone): Carbon
    {
        $dateTime->setTimezone($timezone);

        $start = Carbon::parse($window['start'], $timezone);
        $end = Carbon::parse($window['end'], $timezone);
        $allowedDays = $window['days'] ?? [1, 2, 3, 4, 5];

        if ($dateTime->format('H:i') < $start->format('H:i')) {
            $dateTime->setTime($start->hour, $start->minute);
        }

        if ($dateTime->format('H:i') > $end->format('H:i')) {
            $dateTime->addDay()->setTime($start->hour, $start->minute);
        }

        while (!in_array($dateTime->dayOfWeekIso, $allowedDays)) {
            $dateTime->addDay()->setTime($start->hour, $start->minute);
        }

        return $dateTime->setTimezone('UTC');
    }

    public function pauseCampaign(Campaign $campaign): void
    {
        $campaign->pause();
    }

    public function resumeCampaign(Campaign $campaign): void
    {
        $campaign->resume();
        $this->scheduleFirstStep($campaign);
    }

    public function cancelCampaign(Campaign $campaign): void
    {
        $campaign->cancel();
        $campaign->activeRecipients()->update([
            'status' => CampaignRecipient::STATUS_FAILED,
            'next_send_at' => null,
        ]);
    }

    public function testSend(Campaign $campaign, array $testEmails): array
    {
        $firstStep = $campaign->steps()->where('is_active', true)->orderBy('step_order')->first();
        if (!$firstStep) {
            throw new \Exception('No active steps found');
        }

        $results = [];
        foreach ($testEmails as $email) {
            try {
                $lead = Lead::firstOrCreate(
                    [
                        'workspace_id' => $campaign->workspace_id,
                        'email' => $email,
                    ],
                    [
                        'first_name' => 'Test',
                        'last_name' => 'Recipient',
                        'normalized_email' => Str::lower(trim($email)),
                        'status' => 'new',
                        'created_by' => $campaign->created_by,
                    ]
                );

                $recipient = CampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'lead_id' => $lead->id,
                    'status' => CampaignRecipient::STATUS_ACTIVE,
                    'current_step' => $firstStep->step_order,
                    'next_send_at' => now(),
                ]);

                $idempotencyKey = "test_campaign_{$campaign->id}_lead_{$lead->id}_step_{$firstStep->step_order}";
                SendCampaignEmailJob::dispatchSync($recipient, $firstStep, $idempotencyKey);

                $results[$email] = ['success' => true];
            } catch (\Exception $e) {
                $results[$email] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }
}