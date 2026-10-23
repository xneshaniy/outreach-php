<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'email_account_id',
        'campaign_id',
        'campaign_step_id',
        'campaign_recipient_id',
        'lead_id',
        'message_id',
        'in_reply_to',
        'references',
        'subject',
        'body_html',
        'body_text',
        'headers',
        'attachments',
        'from_email',
        'from_name',
        'to_emails',
        'status',
        'provider',
        'provider_message_id',
        'queued_at',
        'processing_at',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'replied_at',
        'bounced_at',
        'failed_at',
        'error_message',
        'retry_count',
        'idempotency_key',
    ];

    protected $casts = [
        'headers' => 'array',
        'attachments' => 'array',
        'to_emails' => 'array',
        'queued_at' => 'datetime',
        'processing_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'replied_at' => 'datetime',
        'bounced_at' => 'datetime',
        'failed_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_FAILED = 'failed';
    public const STATUS_OPENED = 'opened';
    public const STATUS_CLICKED = 'clicked';
    public const STATUS_REPLIED = 'replied';

    public const STATUSES = [
        self::STATUS_QUEUED,
        self::STATUS_PROCESSING,
        self::STATUS_SENT,
        self::STATUS_DELIVERED,
        self::STATUS_BOUNCED,
        self::STATUS_FAILED,
        self::STATUS_OPENED,
        self::STATUS_CLICKED,
        self::STATUS_REPLIED,
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignStep(): BelongsTo
    {
        return $this->belongsTo(CampaignStep::class);
    }

    public function campaignRecipient(): BelongsTo
    {
        return $this->belongsTo(CampaignRecipient::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MessageEvent::class);
    }

    public function isTerminalStatus(): bool
    {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_BOUNCED,
            self::STATUS_FAILED,
            self::STATUS_REPLIED,
        ]);
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'processing_at' => now(),
        ]);
    }

    public function markSent(string $providerMessageId = null): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'provider_message_id' => $providerMessageId,
        ]);

        if ($this->campaignRecipient) {
            $this->campaignRecipient->recordSent();
        }
    }

    public function markDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    public function markBounced(string $error = null): void
    {
        $this->update([
            'status' => self::STATUS_BOUNCED,
            'bounced_at' => now(),
            'error_message' => $error,
        ]);

        if ($this->campaignRecipient) {
            $this->campaignRecipient->markBounced();
        }
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $error,
        ]);

        if ($this->campaignRecipient) {
            $this->campaignRecipient->markFailed();
        }
    }

    public function markOpened(): void
    {
        if (!in_array($this->status, [self::STATUS_OPENED, self::STATUS_CLICKED, self::STATUS_REPLIED])) {
            $this->update([
                'status' => self::STATUS_OPENED,
                'opened_at' => now(),
            ]);
        }

        if ($this->campaignRecipient) {
            $this->campaignRecipient->recordOpen();
        }
    }

    public function markClicked(): void
    {
        if (!in_array($this->status, [self::STATUS_CLICKED, self::STATUS_REPLIED])) {
            $this->update([
                'status' => self::STATUS_CLICKED,
                'clicked_at' => now(),
            ]);
        }

        if ($this->campaignRecipient) {
            $this->campaignRecipient->recordClick();
        }
    }

    public function markReplied(): void
    {
        $this->update([
            'status' => self::STATUS_REPLIED,
            'replied_at' => now(),
        ]);

        if ($this->campaignRecipient) {
            $this->campaignRecipient->markReplied();
        }

        // Add to suppression list
        if ($this->lead) {
            SuppressionList::firstOrCreate(
                [
                    'workspace_id' => $this->workspace_id,
                    'normalized_email' => Lead::normalizeEmail($this->lead->email),
                ],
                [
                    'email' => $this->lead->email,
                    'reason' => 'reply',
                    'source' => $this->campaign_id ? "campaign_{$this->campaign_id}" : 'reply',
                    'campaign_id' => $this->campaign_id,
                    'message_id' => $this->id,
                ]
            );
        }
    }

    public function getToEmailsFormatted(): string
    {
        return collect($this->to_emails)->pluck('email')->implode(', ');
    }

    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }
}