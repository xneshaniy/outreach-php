<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class EmailAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'name',
        'email',
        'normalized_email',
        'provider',
        'host',
        'port',
        'encryption',
        'username',
        'password_encrypted',
        'from_name',
        'reply_to',
        'signature_html',
        'signature_text',
        'daily_send_limit',
        'sent_today',
        'bounced_count',
        'failed_count',
        'last_sent_date',
        'is_active',
        'is_default',
        'track_opens',
        'track_clicks',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    protected $casts = [
        'port' => 'integer',
        'daily_send_limit' => 'integer',
        'sent_today' => 'integer',
        'bounced_count' => 'integer',
        'failed_count' => 'integer',
        'last_sent_date' => 'date',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'track_opens' => 'boolean',
        'track_clicks' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    protected $hidden = ['password_encrypted'];

    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'paused';
        }
        if ($this->last_test_status === 'failed') {
            return 'error';
        }
        return 'active';
    }

    public function getDailyLimitAttribute(): int
    {
        return (int) $this->daily_send_limit;
    }

    public function getSmtpHostAttribute(): ?string
    {
        return $this->host;
    }

    public function getSmtpPortAttribute(): ?int
    {
        return $this->port;
    }

    public function getSmtpEncryptionAttribute(): ?string
    {
        return $this->encryption;
    }

    public function getLastErrorAttribute(): ?string
    {
        return $this->last_test_message;
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function assignedCampaigns(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_email_accounts')
            ->withPivot(['allocation_count', 'sent_count', 'failed_count'])
            ->withTimestamps();
    }

    public function campaignSteps(): HasMany
    {
        return $this->hasMany(CampaignStep::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function getPasswordAttribute(): string
    {
        try {
            return Crypt::decryptString($this->password_encrypted);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password_encrypted'] = Crypt::encryptString($value);
    }

    public function getSmtpConfig(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => 30,
        ];
    }

    public function canSendToday(): bool
    {
        if ($this->last_sent_date !== now()->toDateString()) {
            return true;
        }
        return $this->sent_today < $this->daily_send_limit;
    }

    public function getRemainingToday(): int
    {
        if ($this->last_sent_date !== now()->toDateString()) {
            return $this->daily_send_limit;
        }
        return max(0, $this->daily_send_limit - $this->sent_today);
    }

    public function incrementSent(): void
    {
        $today = now()->toDateString();
        if ($this->last_sent_date !== $today) {
            $this->update([
                'sent_today' => 1,
                'last_sent_date' => $today,
            ]);
        } else {
            $this->increment('sent_today');
        }
    }

    public function testConnection(): array
    {
        try {
            $tls = ($this->encryption === 'ssl' || (int)$this->port === 465) ? true : ($this->encryption === 'tls' ? false : null);
            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $this->host,
                (int)$this->port,
                $tls
            );

            if (!empty($this->username)) {
                $transport->setUsername($this->username);
            }

            $password = $this->getPassword();
            if (!empty($password)) {
                $transport->setPassword($password);
            }

            $transport->start();
            $transport->stop();

            $this->update([
                'last_tested_at' => now(),
                'last_test_status' => 'success',
                'last_test_message' => 'Connection successful',
            ]);

            return ['success' => true, 'message' => 'Connection successful'];
        } catch (\Throwable $e) {
            $this->update([
                'last_tested_at' => now(),
                'last_test_status' => 'failed',
                'last_test_message' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}