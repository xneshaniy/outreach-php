<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkedInJobApplication extends Model
{
    use HasFactory;

    protected $table = 'linkedin_job_applications';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'search_id',
        'job_title',
        'company',
        'location',
        'job_url',
        'salary_range',
        'job_description',
        'resume_customization',
        'screening_answers',
        'status',
        'applied_at',
    ];

    protected $casts = [
        'resume_customization' => 'array',
        'screening_answers' => 'array',
        'applied_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function search(): BelongsTo
    {
        return $this->belongsTo(LinkedInJobSearch::class, 'search_id');
    }
}
