<?php

namespace App\Services;

use App\Models\EmailAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Mailer;

class EmailWarmupService
{
    /**
     * Positive email subject templates for simulated warm-up traffic
     */
    protected array $subjects = [
        'Quick sync regarding project milestone',
        'Update on the partnership schedule',
        'Follow-up on our morning discussion',
        'Quarterly review agenda check',
        'Notes from the product alignment meeting',
        'Feedback on the latest draft proposal',
        'Resource confirmation for next week',
        'Checking in on the roadmap deliverables',
    ];

    /**
     * Professional, friendly bodies to generate high deliverability and simulate human correspondence
     */
    protected array $bodies = [
        "Hi there,\n\nI hope you're having a productive week. Just wanted to quickly circle back on the discussion from earlier and confirm everything is on track for our planned timeline.\n\nBest regards,\nOutreach Team",
        "Hello,\n\nThanks for sending over the details. I reviewed everything and it looks well-aligned with our objectives. Let's touch base again tomorrow.\n\nWarm regards,\nOutreach Team",
        "Hi,\n\nFollowing up on our agenda notes. All action items on our side are progressing smoothly. Please let me know if there's anything else required before Friday.\n\nCheers,\nOutreach Team",
        "Greetings,\n\nThank you for the update. We appreciate the prompt turnaround on this initiative. Looking forward to our next collaboration step.\n\nBest,\nOutreach Team",
    ];

    /**
     * Run daily warmup round across all enabled email accounts in the system.
     */
    public function runDailyWarmup(): array
    {
        $accounts = EmailAccount::where('warmup_enabled', true)
            ->where('is_active', true)
            ->get();

        $stats = [
            'accounts_processed' => 0,
            'emails_sent'        => 0,
            'failed'             => 0,
        ];

        if ($accounts->isEmpty()) {
            return $stats;
        }

        $today = now()->toDateString();

        foreach ($accounts as $account) {
            // Check & advance daily quota logic
            if ($account->warmup_last_date !== $today) {
                // If it's a new day, advance the daily limit incrementally up to warmup_max_limit
                $newLimit = min(
                    $account->warmup_max_limit ?: 40,
                    ($account->warmup_daily_limit ?: 5) + ($account->warmup_step ?: 2)
                );

                $account->update([
                    'warmup_daily_limit' => $newLimit,
                    'warmup_sent_today'  => 0,
                    'warmup_last_date'   => $today,
                    'warmup_status'      => 'warming',
                ]);
            }

            $remainingWarmup = max(0, ($account->warmup_daily_limit ?: 5) - ($account->warmup_sent_today ?: 0));

            // Don't send all at once: send in batches of 1 to 3 per scheduled run
            $sendCountThisRound = min($remainingWarmup, rand(1, 3));

            if ($sendCountThisRound <= 0) {
                continue;
            }

            // Pick peer accounts from pool to receive warmup mail
            $peerAccounts = EmailAccount::where('id', '!=', $account->id)
                ->where('is_active', true)
                ->get();

            if ($peerAccounts->isEmpty()) {
                continue;
            }

            for ($i = 0; $i < $sendCountThisRound; $i++) {
                $recipient = $peerAccounts->random();
                $subject = $this->subjects[array_rand($this->subjects)];
                $body = $this->bodies[array_rand($this->bodies)];

                $success = $this->sendWarmupMessage($account, $recipient->email, $subject, $body);

                if ($success) {
                    $account->increment('warmup_sent_today');
                    $stats['emails_sent']++;
                } else {
                    $stats['failed']++;
                }

                // Random delay between sends (1 - 3 seconds)
                usleep(rand(1000000, 3000000));
            }

            $stats['accounts_processed']++;
        }

        return $stats;
    }

    /**
     * Send an individual warm-up email directly via the account's configured SMTP credentials
     */
    public function sendWarmupMessage(EmailAccount $account, string $recipientEmail, string $subject, string $body): bool
    {
        try {
            $port = (int) ($account->port ?: 465);
            $tls = ($account->encryption === 'ssl' || $port === 465) ? true : ($account->encryption === 'tls' ? false : null);

            $transport = new EsmtpTransport(
                $account->host ?: 'smtp.hostinger.com',
                $port,
                $tls
            );

            if (!empty($account->username)) {
                $transport->setUsername($account->username);
            }

            $password = $account->getPassword();
            if (!empty($password)) {
                $transport->setPassword($password);
            }

            $mailer = new Mailer($transport);

            $fromName = $account->from_name ?: $account->name ?: 'Outreach Team';
            $email = (new Email())
                ->from(new \Symfony\Component\Mime\Address($account->email, $fromName))
                ->to($recipientEmail)
                ->subject($subject)
                ->text($body);

            // Add custom warm-up header so it can be identified & auto-archived/replied
            $email->getHeaders()->addTextHeader('X-Outreach-Warmup', 'true');

            $mailer->send($email);

            // Log in email_warmup_logs
            DB::table('email_warmup_logs')->insert([
                'email_account_id' => $account->id,
                'sender_email'     => $account->email,
                'recipient_email'  => $recipientEmail,
                'subject'          => $subject,
                'status'           => 'sent',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            Log::info("Warmup email successfully sent from {$account->email} to {$recipientEmail}");
            return true;
        } catch (\Throwable $e) {
            Log::error("Warmup email failed from {$account->email} to {$recipientEmail}: " . $e->getMessage());

            DB::table('email_warmup_logs')->insert([
                'email_account_id' => $account->id,
                'sender_email'     => $account->email,
                'recipient_email'  => $recipientEmail,
                'subject'          => $subject,
                'status'           => 'failed',
                'error_message'    => substr($e->getMessage(), 0, 500),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            return false;
        }
    }
}
