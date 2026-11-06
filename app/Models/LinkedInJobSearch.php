<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LinkedInJobSearch extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'query',
        'location',
        'job_type',
        'experience_level',
        'results_count',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LinkedInJobApplication::class, 'search_id');
    }
}
