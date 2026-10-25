<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Models\CampaignStep;
use App\Models\EmailAccount;
use App\Models\Message;
use App\Models\Workspace;
use App\Services\CampaignService;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 300; // 5 minutes
    public int $timeout = 60;

    public function __construct(
        public CampaignRecipient $recipient,
        public CampaignStep $step,
        public string $idempotencyKey,
        public ?int $emailAccountId = null
    ) {}

    public function handle(EmailService $emailService, CampaignService $campaignService): void
    {
        $recipient = $this->recipient->fresh();
        $step = $this->step->fresh();

        if (!$recipient->canReceiveEmails()) {
            Log::info("Recipient {$recipient->id} can no longer receive emails");
            return;
        }

        // Check idempotency
        if (Message::where('idempotency_key', $this->idempotencyKey)->exists()) {
            Log::info("Email already sent for idempotency key: {$this->idempotencyKey}");
            $recipient->scheduleNextStep();
            return;
        }

        // Check campaign status
        $campaign = $recipient->campaign()->first();
        if (!$campaign || !$campaign->canSend()) {
            Log::info("Campaign {$campaign?->id} cannot send");
            return;
        }

        // Check email account
        $emailAccount = null;
        if ($this->emailAccountId) {
            $emailAccount = EmailAccount::find($this->emailAccountId);
        }
        if (!$emailAccount) {
            $emailAccount = $step->emailAccount ?? $campaign->emailAccount;
        }
        if (!$emailAccount || !$emailAccount->is_active || !$emailAccount->canSendToday()) {
            // Re-queue for later
            $this->release(300);
            return;
        }

        // Attach email_account_id to recipient for tracking
        $recipient->update(['email_account_id' => $emailAccount->id]);

        // Check workspace daily limit
        $workspace = Workspace::find($campaign->workspace_id);
        $dailyLimit = $workspace->getSetting('daily_send_limit', 2000);
        $sentToday = Message::where('workspace_id', $campaign->workspace_id)
            ->whereDate('sent_at', today())
            ->where('status', '!=', 'failed')
            ->count();

        if ($sentToday >= $dailyLimit) {
            $this->release(3600); // Try again in an hour
            return;
        }

        // Build personalized content
        $personalization = $this->buildPersonalization($recipient);
        $subject = $this->replaceTokens($step->subject, $personalization);
        $bodyHtml = $this->replaceTokens($step->body_html, $personalization);
        $bodyText = $step->body_text ? $this->replaceTokens($step->body_text, $personalization) : null;

        // Create message record
        $message = Message::create([
            'workspace_id' => $campaign->workspace_id,
            'email_account_id' => $emailAccount->id,
            'campaign_id' => $campaign->id,
            'campaign_step_id' => $step->id,
            'campaign_recipient_id' => $recipient->id,
            'lead_id' => $recipient->lead_id,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'from_email' => $emailAccount->email,
            'from_name' => $emailAccount->from_name ?? $emailAccount->name,
            'to_emails' => [
                ['email' => $recipient->lead->email, 'name' => $recipient->lead->full_name, 'type' => 'to']
            ],
            'status' => Message::STATUS_QUEUED,
            'idempotency_key' => $this->idempotencyKey,
        ]);

        try {
            // Inject tracking
            $trackedHtml = $emailService->injectTracking($bodyHtml, $message);

            // Add unsubscribe link
            $unsubscribeUrl = $emailService->buildUnsubscribeUrl($recipient);
            $trackedHtml = str_ireplace('</body>', "<p style=\"font-size:12px;color:#999;\"><a href=\"{$unsubscribeUrl}\">Unsubscribe</a></p></body>", $trackedHtml);

            // Send email
            $result = $emailService->send(
                $emailAccount,
                [['email' => $recipient->lead->email, 'name' => $recipient->lead->full_name]],
                $subject,
                $trackedHtml,
                $bodyText,
                [
                    'reply_to' => $emailAccount->reply_to ? ['email' => $emailAccount->reply_to] : null,
                    'headers' => [
                        'X-Campaign-ID' => $campaign->id,
                        'X-Campaign-Step-ID' => $step->id,
                        'X-Recipient-ID' => $recipient->id,
                        'X-Message-ID' => $message->id,
                        'List-Unsubscribe' => "<{$unsubscribeUrl}>",
                    ],
                ]
            );

            if ($result['success']) {
                $message->markSent($result['message_id'] ?? null);
                $emailAccount->incrementSent();

                // Increment campaign-account pivot sent count
                \App\Models\CampaignEmailAccount::where('campaign_id', $campaign->id)
                    ->where('email_account_id', $emailAccount->id)
                    ->increment('sent_count');

                $recipient->update([
                    'error_message' => null,
                    'last_response' => 'Sent successfully (ID: ' . ($result['message_id'] ?? 'OK') . ')',
                ]);

                // Update campaign recipient
                $campaignService->processStepCompletion($recipient, true);
            } else {
                throw new \Exception($result['error']);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send campaign email", [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            $message->markFailed($e->getMessage());

            // Track account failures
            $emailAccount->increment('failed_count');
            \App\Models\CampaignEmailAccount::where('campaign_id', $campaign->id)
                ->where('email_account_id', $emailAccount->id)
                ->increment('failed_count');

            $recipient->increment('retry_count');
            $recipient->update([
                'error_message' => $e->getMessage(),
                'last_response' => 'SMTP Failed: ' . substr($e->getMessage(), 0, 200),
            ]);

            // Retry logic
            if ($this->attempts() < $this->tries) {
                throw $e; // Will trigger retry
            }

            // Max retries exceeded
            $campaignService->processStepCompletion($recipient, false);
        }
    }

    private function buildPersonalization(CampaignRecipient $recipient): array
    {
        $lead = $recipient->lead;
        $company = $lead->company;

        $data = [
            'first_name' => $lead->first_name ?? '',
            'last_name' => $lead->last_name ?? '',
            'full_name' => $lead->full_name ?? '',
            'email' => $lead->email ?? '',
            'job_title' => $lead->job_title ?? '',
            'company_name' => $company->name ?? '',
            'company_domain' => $company->domain ?? '',
            'company_website' => $company->website ?? '',
            'company_industry' => $company->industry ?? '',
            'phone' => $lead->phone ?? '',
            'city' => $lead->city ?? '',
            'country' => $lead->country ?? '',
        ];

        // Add custom fields
        if ($lead->custom_fields) {
            foreach ($lead->custom_fields as $key => $value) {
                $data["custom.{$key}"] = $value;
            }
        }

        // Add campaign recipient personalization data
        if ($recipient->personalization_data) {
            $data = array_merge($data, $recipient->personalization_data);
        }

        // Add fallbacks
        $fallbacks = config('crm.token_fallbacks', [
            'first_name' => 'there',
            'company_name' => 'your company',
        ]);

        foreach ($fallbacks as $key => $fallback) {
            if (empty($data[$key])) {
                $data[$key] = $fallback;
            }
        }

        return $data;
    }

    private function replaceTokens(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function ($matches) use ($data) {
                $key = $matches[1];
                return $data[$key] ?? "{{$key}}";
            },
            $template
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error("SendCampaignEmailJob failed permanently", [
            'recipient_id' => $this->recipient->id,
            'step_id' => $this->step->id,
            'error' => $exception->getMessage(),
        ]);

        $this->recipient->markFailed();
    }
}