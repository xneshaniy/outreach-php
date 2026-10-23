<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'email_account_id',
        'step_order',
        'name',
        'subject',
        'body_html',
        'body_text',
        'delay_days',
        'delay_hours',
        'delay_minutes',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'delay_days' => 'integer',
        'delay_hours' => 'integer',
        'delay_minutes' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function getTotalDelayMinutes(): int
    {
        return ($this->delay_days * 24 * 60) + ($this->delay_hours * 60) + $this->delay_minutes;
    }

    public function getDelayDescription(): string
    {
        $parts = [];
        if ($this->delay_days) $parts[] = "{$this->delay_days} day(s)";
        if ($this->delay_hours) $parts[] = "{$this->delay_hours} hour(s)";
        if ($this->delay_minutes) $parts[] = "{$this->delay_minutes} minute(s)";
        return $parts ? implode(', ', $parts) : 'Immediate';
    }
}