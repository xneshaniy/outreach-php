<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'google_account_id',
        'google_email',
        'google_name',
        'google_avatar',
        'scopes',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sheetSyncs(): HasMany
    {
        return $this->hasMany(GoogleSheetSync::class);
    }

    public function isExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }
        return $this->token_expires_at->isPast();
    }
}
