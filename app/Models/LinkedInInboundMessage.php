<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkedInInboundMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'sender_name',
        'sender_profile',
        'message_text',
        'classification',
        'ai_suggested_reply',
        'status',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
