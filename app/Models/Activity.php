<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected $fillable = [
        'workspace_id',
        'user_id',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'description',
        'event',
        'properties',
        'log_name',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $activity) {
            if (auth()->check()) {
                $user = auth()->user();
                $workspace = $user->currentWorkspace;
                if ($workspace) {
                    $activity->workspace_id = $workspace->id;
                }
            }
        });
    }
}