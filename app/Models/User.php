<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'avatar_path',
        'phone',
        'job_title',
        'timezone',
        'locale',
        'password',
        'is_active',
        'is_super_admin',
        'last_login_at',
        'last_login_ip',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'is_super_admin' => 'boolean',
        'last_login_at' => 'datetime',
        'preferences' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active', 'is_super_admin'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot('role_id', 'is_owner', 'is_active', 'joined_at', 'last_accessed_at')
            ->withTimestamps();
    }

    public function ownedWorkspaces(): BelongsToMany
    {
        return $this->workspaces()->wherePivot('is_owner', true);
    }

    public function activeWorkspaces(): BelongsToMany
    {
        return $this->workspaces()->wherePivot('is_active', true);
    }

    public function currentWorkspace(): ?Workspace
    {
        // A user can be a member of several workspaces. The most recently
        // selected one is the current workspace; membership itself is tracked
        // by workspace_user.is_active.
        return $this->activeWorkspaces()
            ->where('workspaces.is_active', true)
            ->orderByDesc('workspace_user.last_accessed_at')
            ->first();
    }

    /**
     * Allow $user->currentWorkspace property access (used across middleware,
     * controllers and Inertia shared props).
     */
    public function getCurrentWorkspaceAttribute(): ?Workspace
    {
        return $this->currentWorkspace();
    }

    public function setCurrentWorkspace(Workspace $workspace): void
    {
        // Only remember which workspace was selected last. Membership of the
        // other workspaces must stay untouched so the user can switch back.
        $this->workspaces()->updateExistingPivot($workspace->id, [
            'is_active' => true,
            'last_accessed_at' => now(),
        ]);
    }

    public function hasAccessToWorkspace(Workspace $workspace): bool
    {
        return $this->workspaces()->where('workspace_id', $workspace->id)->wherePivot('is_active', true)->exists();
    }

    public function isOwnerOf(Workspace $workspace): bool
    {
        return $this->workspaces()->where('workspace_id', $workspace->id)->wherePivot('is_owner', true)->exists();
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'owner_id');
    }

    public function createdLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'created_by');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'created_by');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    public function emailAccounts(): HasMany
    {
        return $this->hasMany(EmailAccount::class, 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'owner_id');
    }
}