<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_path',
        'primary_color',
        'secondary_color',
        'settings',
        'is_active',
        'trial_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role_id', 'is_owner', 'is_active', 'joined_at', 'last_accessed_at')
            ->withTimestamps();
    }

    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('is_owner', true);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function emailAccounts(): HasMany
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function leadLists(): HasMany
    {
        return $this->hasMany(LeadList::class);
    }

    public function pipelines(): HasMany
    {
        return $this->hasMany(Pipeline::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(WorkspaceSetting::class);
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings()->where('key', $key)->first();
        return $setting?->value ?? $default;
    }

    public function setSetting(string $key, mixed $value, string $type = 'string', bool $isPublic = false): void
    {
        $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'is_public' => $isPublic]
        );
    }
}