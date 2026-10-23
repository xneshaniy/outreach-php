<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadImportError extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_import_id',
        'row_number',
        'row_data',
        'error_message',
    ];

    protected $casts = [
        'row_data' => 'array',
    ];

    public function leadImport(): BelongsTo
    {
        return $this->belongsTo(LeadImport::class);
    }
}