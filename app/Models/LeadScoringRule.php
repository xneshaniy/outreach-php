<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadScoringRule extends Model
{
    use HasFactory;

    protected $table = 'lead_scoring_rules';

    protected $fillable = [
        'workspace_id',
        'name',
        'criteria_key',
        'points',
        'is_active',
        'conditions',
    ];

    protected $casts = [
        'points' => 'integer',
        'is_active' => 'boolean',
        'conditions' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
