<?php

namespace Database\Factories;

use App\Models\EmailAccount;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class EmailAccountFactory extends Factory
{
    protected $model = EmailAccount::class;

    public function definition(): array
    {
        $workspace = Workspace::inRandomOrder()->first() ?? Workspace::factory()->create();
        $user = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))->inRandomOrder()->first();
        
        $email = fake()->unique()->safeEmail();
        $providers = ['smtp', 'gmail', 'outlook', 'yahoo', 'custom'];
        $provider = fake()->randomElement($providers);

        $hostMap = [
            'gmail' => 'smtp.gmail.com',
            'outlook' => 'smtp.office365.com',
            'yahoo' => 'smtp.mail.yahoo.com',
            'smtp' => 'smtp.example.com',
            'custom' => 'smtp.custom.com',
        ];

        $portMap = [
            'gmail' => 587,
            'outlook' => 587,
            'yahoo' => 587,
            'smtp' => 587,
            'custom' => 587,
        ];

        return [
            'workspace_id' => $workspace->id,
            'user_id' => $user?->id ?? $workspace->users()->first()?->id,
            'name' => fake()->company() . ' Email',
            'email' => $email,
            'normalized_email' => Str::lower($email),
            'provider' => $provider,
            'host' => $hostMap[$provider],
            'port' => $portMap[$provider],
            'encryption' => 'tls',
            'username' => $email,
            'password_encrypted' => Crypt::encryptString(fake()->password(16)),
            'from_name' => fake()->company(),
            'reply_to' => fake()->optional()->safeEmail(),
            'signature_html' => '<p>Best regards,<br>' . fake()->name() . '</p>',
            'signature_text' => "Best regards,\n" . fake()->name(),
            'daily_send_limit' => 200,
            'sent_today' => 0,
            'last_sent_date' => null,
            'is_active' => true,
            'is_default' => false,
            'track_opens' => true,
            'track_clicks' => true,
            'last_tested_at' => null,
            'last_test_status' => null,
            'last_test_message' => null,
        ];
    }
}