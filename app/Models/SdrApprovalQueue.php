<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdrApprovalQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'lead_id',
        'user_id',
        'channel',
        'action_type',
        'recipient_name',
        'recipient_contact',
        'subject',
        'proposed_content',
        'ai_intent',
        'context_data',
        'status',
        'approved_by',
        'sent_at',
    ];

    protected $casts = [
        'context_data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
