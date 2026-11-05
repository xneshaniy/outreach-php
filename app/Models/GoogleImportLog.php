<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'source_type',
        'source_identifier',
        'status',
        'imported_count',
        'updated_count',
        'matched_companies_count',
        'created_companies_count',
        'error_details',
    ];

    protected $casts = [
        'error_details' => 'array',
        'imported_count' => 'integer',
        'updated_count' => 'integer',
        'matched_companies_count' => 'integer',
        'created_companies_count' => 'integer',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
