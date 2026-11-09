<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkedInNetworkInvite extends Model
{
    use HasFactory;

    protected $table = 'linkedin_network_invites';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'full_name',
        'headline',
        'company',
        'profile_url',
        'invite_note',
        'connection_type',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
