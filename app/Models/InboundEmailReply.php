<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundEmailReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'email_account_id',
        'lead_id',
        'campaign_id',
        'message_id',
        'in_reply_to',
        'sender_email',
        'sender_name',
        'recipient_email',
        'subject',
        'body_text',
        'body_html',
        'sentiment',
        'is_read',
        'notification_sent',
        'received_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'notification_sent' => 'boolean',
        'received_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
