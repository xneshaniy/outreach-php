<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'created_by',
        'name',
        'trigger_event',
        'trigger_config',
        'action_type',
        'action_config',
        'requires_approval',
        'is_active',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'action_config' => 'array',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class);
    }
}
