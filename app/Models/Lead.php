<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'company_id',
        'owner_id',
        'created_by',
        'website',
        'industry',
        'score',
        'category',
        'pipeline_stage',
        'email_verified',
        'email_verification_status',
        'assigned_campaign_id',
        'first_name',
        'last_name',
        'email',
        'normalized_email',
        'phone',
        'mobile',
        'job_title',
        'department',
        'seniority',
        'linkedin_url',
        'twitter_url',
        'country',
        'city',
        'state',
        'timezone',
        'source',
        'status',
        'tags',
        'notes',
        'custom_fields',
        'follow_up_at',
        'last_activity_at',
        'last_contacted_at',
        'converted_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'email_verified' => 'boolean',
        'tags' => 'array',
        'custom_fields' => 'array',
        'follow_up_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    protected $appends = [
        'full_name',
        'display_name',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (self $lead) {
            $lead->normalized_email = self::normalizeEmail($lead->email);
        });

        static::updating(function (self $lead) {
            if ($lead->isDirty('email')) {
                $lead->normalized_email = self::normalizeEmail($lead->email);
            }
        });
    }

    public static function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(LeadList::class, 'lead_list_memberships')
            ->withPivot('added_by')
            ->withTimestamps();
    }

    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function activeCampaignRecipients(): HasMany
    {
        return $this->campaignRecipients()
            ->whereIn('status', ['pending', 'active'])
            ->whereHas('campaign', fn($q) => $q->whereIn('status', ['active', 'paused']));
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'related');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function assignedCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'assigned_campaign_id');
    }

    public function research(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LeadResearch::class);
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest();
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: ($this->email ?: 'Unnamed Lead');
    }

    public function getInitialsAttribute(): string
    {
        return Str::upper(Str::substr($this->first_name ?? '', 0, 1) . Str::substr($this->last_name ?? '', 0, 1)) ?: '?';
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('company', 'like', "%{$term}%")
                ->orWhere('job_title', 'like', "%{$term}%");
        });
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('owner_id', $userId);
    }

    public function scopeNeedsFollowUp($query)
    {
        return $query->where('follow_up_at', '<=', now())
            ->where('status', '!=', 'converted')
            ->where('status', '!=', 'archived');
    }
}