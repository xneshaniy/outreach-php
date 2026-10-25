<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadResearch extends Model
{
    use HasFactory;

    protected $table = 'lead_researches';

    protected $fillable = [
        'workspace_id',
        'lead_id',
        'created_by',
        'company_overview',
        'industry',
        'potential_needs',
        'relevant_services',
        'outreach_angle',
        'suggested_subject',
        'suggested_opening',
        'raw_analysis',
        'verified',
    ];

    protected $casts = [
        'raw_analysis' => 'array',
        'verified' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
