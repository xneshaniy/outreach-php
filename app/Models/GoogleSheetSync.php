<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleSheetSync extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'google_integration_id',
        'spreadsheet_id',
        'spreadsheet_name',
        'worksheet_name',
        'column_mapping',
        'sync_mode',
        'last_synced_at',
        'last_status',
        'last_error',
        'records_imported',
        'records_updated',
    ];

    protected $casts = [
        'column_mapping' => 'array',
        'last_synced_at' => 'datetime',
        'records_imported' => 'integer',
        'records_updated' => 'integer',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(GoogleIntegration::class, 'google_integration_id');
    }
}
