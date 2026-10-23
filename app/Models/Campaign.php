<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'created_by',
        'email_account_id',
        'lead_list_id',
        'name',
        'description',
        'status',
        'timezone',
        'sending_window',
        'daily_send_limit',
        'delay_between_emails',
        'scheduled_at',
        'started_at',
        'completed_at',
        'paused_at',
    ];

    protected $casts = [
        'sending_window' => 'array',
        'daily_send_limit' => 'integer',
        'delay_between_emails' => 'integer',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'paused_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SCHEDULED,
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function leadList(): BelongsTo
    {
        return $this->belongsTo(LeadList::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(CampaignStep::class)->orderBy('step_order');
    }

    public function activeSteps(): HasMany
    {
        return $this->steps()->where('is_active', true);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function pendingRecipients(): HasMany
    {
        return $this->recipients()->where('status', 'pending');
    }

    public function activeRecipients(): HasMany
    {
        return $this->recipients()->whereIn('status', ['pending', 'active']);
    }

    public function getNextSendAtAttribute(): ?Carbon
    {
        if ($this->status === self::STATUS_SCHEDULED && $this->scheduled_at) {
            return $this->scheduled_at;
        }
        return null;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canSend(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_SCHEDULED]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'started_at' => now(),
            'paused_at' => null,
        ]);
    }

    public function pause(): void
    {
        $this->update([
            'status' => self::STATUS_PAUSED,
            'paused_at' => now(),
        ]);
    }

    public function resume(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'paused_at' => null,
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function getStats(): array
    {
        $recipients = $this->recipients;
        return [
            'total' => $recipients->count(),
            'pending' => $recipients->where('status', 'pending')->count(),
            'active' => $recipients->where('status', 'active')->count(),
            'completed' => $recipients->where('status', 'completed')->count(),
            'bounced' => $recipients->where('status', 'bounced')->count(),
            'unsubscribed' => $recipients->where('status', 'unsubscribed')->count(),
            'replied' => $recipients->where('status', 'replied')->count(),
            'failed' => $recipients->where('status', 'failed')->count(),
            'suppressed' => $recipients->where('status', 'suppressed')->count(),
            'emails_sent' => $recipients->sum('emails_sent'),
            'emails_opened' => $recipients->sum('emails_opened'),
            'links_clicked' => $recipients->sum('links_clicked'),
        ];
    }
}