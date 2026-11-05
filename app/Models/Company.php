<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'created_by',
        'name',
        'email',
        'company_emails',
        'domain',
        'website',
        'industry',
        'size',
        'country',
        'city',
        'state',
        'postal_code',
        'address',
        'phone',
        'linkedin_url',
        'twitter_url',
        'description',
        'enrichment_status',
        'enrichment_source',
        'verification_status',
        'source_url',
        'custom_fields',
        'last_contacted_at',
    ];

    protected $casts = [
        'company_emails' => 'array',
        'custom_fields' => 'array',
        'last_contacted_at' => 'datetime',
    ];

    /**
     * Add a company email address without duplicates.
     */
    public function addCompanyEmail(string $email): void
    {
        $email = strtolower(trim($email));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        if (empty($this->email)) {
            $this->email = $email;
        }

        $emails = is_array($this->company_emails) ? $this->company_emails : [];
        if (!in_array($email, $emails, true)) {
            $emails[] = $email;
            $this->company_emails = $emails;
            $this->save();
        }
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'related');
    }

    public function getDisplayName(): string
    {
        return $this->name;
    }
}