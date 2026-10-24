<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pipeline extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'created_by',
        'name',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('order');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function getDefaultStage(): ?PipelineStage
    {
        return $this->stages()->orderBy('order')->first();
    }

    /**
     * Deals require a stage, so create the first ("New") stage when a pipeline
     * was created without one (for example through a seeder or the API).
     */
    public function ensureDefaultStage(): PipelineStage
    {
        if ($stage = $this->getDefaultStage()) {
            return $stage;
        }

        return $this->stages()->create([
            'name' => 'New',
            'color' => '#6366f1',
            'order' => 1,
            'probability' => 10,
            'is_won' => false,
            'is_lost' => false,
            'is_closed' => false,
        ]);
    }

    public function getWonStage(): ?PipelineStage
    {
        return $this->stages()->where('is_won', true)->first();
    }

    public function getLostStage(): ?PipelineStage
    {
        return $this->stages()->where('is_lost', true)->first();
    }
}