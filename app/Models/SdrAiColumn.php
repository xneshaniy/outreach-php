<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdrAiColumn extends Model
{
    use HasFactory;

    protected $table = 'sdr_ai_columns';

    protected $fillable = [
        'workspace_id',
        'lead_list_id',
        'name',
        'prompt_template',
        'model',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function leadList(): BelongsTo
    {
        return $this->belongsTo(LeadList::class);
    }
}
