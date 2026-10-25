<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AIConfiguration extends Model
{
    use HasFactory;

    protected $table = 'ai_configurations';

    protected $fillable = [
        'workspace_id',
        'provider',
        'api_key_encrypted',
        'model',
        'is_active',
        'daily_token_limit',
        'tokens_used_today',
        'last_usage_date',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'daily_token_limit' => 'integer',
        'tokens_used_today' => 'integer',
        'last_usage_date' => 'date',
        'last_tested_at' => 'datetime',
        'settings' => 'array',
    ];

    protected $hidden = [
        'api_key_encrypted',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function getApiKeyAttribute(): string
    {
        if (empty($this->api_key_encrypted)) {
            return '';
        }

        try {
            return Crypt::decryptString($this->api_key_encrypted);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function setApiKeyAttribute(?string $value): void
    {
        if (!empty($value)) {
            $this->attributes['api_key_encrypted'] = Crypt::encryptString($value);
        }
    }

    public function hasCapacity(): bool
    {
        if ($this->last_usage_date !== now()->toDateString()) {
            return true;
        }

        return $this->tokens_used_today < $this->daily_token_limit;
    }

    public function recordUsage(int $tokens): void
    {
        $today = now()->toDateString();
        if ($this->last_usage_date !== $today) {
            $this->update([
                'tokens_used_today' => $tokens,
                'last_usage_date' => $today,
            ]);
        } else {
            $this->increment('tokens_used_today', $tokens);
        }
    }
}
