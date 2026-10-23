<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadList extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'created_by',
        'name',
        'description',
        'is_dynamic',
        'filters',
    ];

    protected $casts = [
        'is_dynamic' => 'boolean',
        'filters' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'lead_list_memberships')
            ->withPivot('added_by')
            ->withTimestamps();
    }

    public function imports(): HasMany
    {
        return $this->hasMany(LeadImport::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'lead_list_id');
    }

    public function getLeadsCountAttribute(): int
    {
        return $this->leads()->count();
    }
}