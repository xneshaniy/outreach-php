<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdrEnrichmentProvider extends Model
{
    use HasFactory;

    protected $table = 'sdr_enrichment_providers';

    protected $fillable = [
        'workspace_id',
        'provider_name',
        'api_key',
        'is_active',
        'priority',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'priority' => 'integer',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
