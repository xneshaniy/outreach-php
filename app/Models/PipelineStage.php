<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'name',
        'color',
        'order',
        'probability',
        'is_won',
        'is_lost',
        'is_closed',
    ];

    protected $casts = [
        'order' => 'integer',
        'probability' => 'integer',
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
        'is_closed' => 'boolean',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'stage_id');
    }

    public function isOpen(): bool
    {
        return !$this->is_closed;
    }

    public function isWon(): bool
    {
        return $this->is_won;
    }

    public function isLost(): bool
    {
        return $this->is_lost;
    }
}