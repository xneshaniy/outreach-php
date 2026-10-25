<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class CampaignRecipient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'lead_id',
        'lead_list_id',
        'email_account_id',
        'status',
        'current_step',
        'next_send_at',
        'last_sent_at',
        'replied_at',
        'unsubscribed_at',
        'bounced_at',
        'emails_sent',
        'emails_opened',
        'links_clicked',
        'retry_count',
        'error_message',
        'last_response',
        'personalization_data',
    ];

    protected $casts = [
        'personalization_data' => 'array',
        'current_step' => 'integer',
        'emails_sent' => 'integer',
        'emails_opened' => 'integer',
        'links_clicked' => 'integer',
        'retry_count' => 'integer',
        'next_send_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'replied_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_REPLIED = 'replied';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SUPPRESSED = 'suppressed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_BOUNCED,
        self::STATUS_UNSUBSCRIBED,
        self::STATUS_REPLIED,
        self::STATUS_FAILED,
        self::STATUS_SUPPRESSED,
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function leadList(): BelongsTo
    {
        return $this->belongsTo(LeadList::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(CampaignStep::class, 'current_step', 'step_order')
            ->where('campaign_id', $this->campaign_id);
    }

    public function nextStep(): ?CampaignStep
    {
        return $this->campaign->steps()
            ->where('step_order', '>', $this->current_step)
            ->where('is_active', true)
            ->first();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function hasReplied(): bool
    {
        return $this->status === self::STATUS_REPLIED || !is_null($this->replied_at);
    }

    public function isSuppressed(): bool
    {
        return $this->status === self::STATUS_SUPPRESSED;
    }

    public function canReceiveEmails(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACTIVE]);
    }

    public function scheduleNextStep(?Carbon $at = null): void
    {
        $step = $this->nextStep();
        if (!$step) {
            $this->complete();
            return;
        }

        $delay = $step->getTotalDelayMinutes();
        $sendAt = ($at ?? now())->addMinutes($delay);

        // Apply sending window if configured
        $window = $this->campaign->sending_window;
        if ($window && isset($window['start'], $window['end'])) {
            $sendAt = $this->applySendingWindow($sendAt, $window);
        }

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'current_step' => $step->step_order,
            'next_send_at' => $sendAt,
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'next_send_at' => null,
        ]);
    }

    public function markReplied(): void
    {
        $this->update([
            'status' => self::STATUS_REPLIED,
            'replied_at' => now(),
            'next_send_at' => null,
        ]);
    }

    public function markBounced(): void
    {
        $this->update([
            'status' => self::STATUS_BOUNCED,
            'bounced_at' => now(),
            'next_send_at' => null,
        ]);
    }

    public function markUnsubscribed(): void
    {
        $this->update([
            'status' => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
            'next_send_at' => null,
        ]);
    }

    public function markFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'next_send_at' => null,
        ]);
    }

    public function markSuppressed(): void
    {
        $this->update([
            'status' => self::STATUS_SUPPRESSED,
            'next_send_at' => null,
        ]);
    }

    public function recordSent(): void
    {
        $this->increment('emails_sent');
        $this->update(['last_sent_at' => now()]);
    }

    public function recordOpen(): void
    {
        $this->increment('emails_opened');
    }

    public function recordClick(): void
    {
        $this->increment('links_clicked');
    }

    private function applySendingWindow(Carbon $dateTime, array $window): Carbon
    {
        $timezone = $this->campaign->timezone ?? 'UTC';
        $dateTime->setTimezone($timezone);

        $start = Carbon::parse($window['start'], $timezone);
        $end = Carbon::parse($window['end'], $timezone);
        $allowedDays = $window['days'] ?? [1,2,3,4,5]; // Mon-Fri default

        // If before start time, move to start time
        if ($dateTime->format('H:i') < $start->format('H:i')) {
            $dateTime->setTime($start->hour, $start->minute);
        }

        // If after end time, move to next allowed day's start time
        if ($dateTime->format('H:i') > $end->format('H:i')) {
            $dateTime->addDay()->setTime($start->hour, $start->minute);
        }

        // If not an allowed day, move to next allowed day
        while (!in_array($dateTime->dayOfWeekIso, $allowedDays)) {
            $dateTime->addDay()->setTime($start->hour, $start->minute);
        }

        return $dateTime->setTimezone('UTC');
    }
}