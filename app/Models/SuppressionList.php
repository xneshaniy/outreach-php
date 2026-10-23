<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SuppressionList extends Model
{
    use HasFactory;

    protected $table = 'suppression_list';

    protected $fillable = [
        'workspace_id',
        'email',
        'normalized_email',
        'reason',
        'source',
        'campaign_id',
        'message_id',
        'metadata',
        'suppressed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'suppressed_at' => 'datetime',
    ];

    public const REASON_UNSUBSCRIBE = 'unsubscribe';
    public const REASON_BOUNCE = 'bounce';
    public const REASON_COMPLAINT = 'complaint';
    public const REASON_MANUAL = 'manual';
    public const REASON_REPLY = 'reply';

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public static function isSuppressed(int $workspaceId, string $email): bool
    {
        return self::where('workspace_id', $workspaceId)
            ->where('normalized_email', Str::lower(trim($email)))
            ->exists();
    }

    public static function suppress(int $workspaceId, string $email, string $reason, array $metadata = []): self
    {
        return self::firstOrCreate(
            [
                'workspace_id' => $workspaceId,
                'normalized_email' => Str::lower(trim($email)),
            ],
            [
                'email' => $email,
                'reason' => $reason,
                'source' => $metadata['source'] ?? 'manual',
                'campaign_id' => $metadata['campaign_id'] ?? null,
                'message_id' => $metadata['message_id'] ?? null,
                'metadata' => $metadata,
                'suppressed_at' => now(),
            ]
        );
    }
}