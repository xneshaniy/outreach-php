<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'pipeline_id',
        'stage_id',
        'lead_id',
        'company_id',
        'owner_id',
        'created_by',
        'name',
        'value',
        'currency',
        'expected_close_date',
        'actual_close_date',
        'status',
        'notes',
        'custom_fields',
        'last_activity_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
        'custom_fields' => 'array',
        'last_activity_at' => 'datetime',
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
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

    public function tasks(): HasMany
    {
        return $this->morphMany(Task::class, 'related');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isWon(): bool
    {
        return $this->status === self::STATUS_WON;
    }

    public function isLost(): bool
    {
        return $this->status === self::STATUS_LOST;
    }

    public function isClosed(): bool
    {
        return $this->isWon() || $this->isLost();
    }

    public function win(): void
    {
        $wonStage = $this->pipeline->getWonStage();
        $this->update([
            'status' => self::STATUS_WON,
            'stage_id' => $wonStage?->id,
            'actual_close_date' => now()->toDateString(),
        ]);
    }

    public function lose(): void
    {
        $lostStage = $this->pipeline->getLostStage();
        $this->update([
            'status' => self::STATUS_LOST,
            'stage_id' => $lostStage?->id,
            'actual_close_date' => now()->toDateString(),
        ]);
    }

    public function reopen(): void
    {
        $defaultStage = $this->pipeline->getDefaultStage();
        $this->update([
            'status' => self::STATUS_OPEN,
            'stage_id' => $defaultStage?->id,
            'actual_close_date' => null,
        ]);
    }

    public function getWeightedValue(): float
    {
        return $this->value * ($this->stage->probability / 100);
    }

    public function getDaysUntilClose(): ?int
    {
        if (!$this->expected_close_date || $this->isClosed()) {
            return null;
        }
        return now()->diffInDays($this->expected_close_date, false);
    }

    public function isOverdue(): bool
    {
        return $this->expected_close_date
            && $this->expected_close_date->isPast()
            && $this->isOpen();
    }
}