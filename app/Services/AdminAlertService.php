<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\CrmNotification;
use App\Models\EmailAccount;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminAlertService
{
    /**
     * Alert admin and workspace users about a mailbox disconnection or IMAP failure.
     */
    public function alertMailboxDisconnected(EmailAccount $account, string $reason): void
    {
        $title = "Mailbox Disconnected: {$account->email}";
        $message = "The mailbox {$account->email} failed connection check: {$reason}. Sending and sync have been safely paused for this account.";

        $this->recordAndNotify($account->workspace_id, 'mailbox_error', $title, $message, [
            'email_account_id' => $account->id,
            'email' => $account->email,
            'reason' => $reason,
        ]);
    }

    /**
     * Alert admin about SMTP error or sending failure.
     */
    public function alertSmtpFailure(EmailAccount $account, string $error): void
    {
        $title = "SMTP Failure on {$account->email}";
        $message = "Mailbox {$account->email} encountered an SMTP error: {$error}.";

        $this->recordAndNotify($account->workspace_id, 'smtp_error', $title, $message, [
            'email_account_id' => $account->id,
            'email' => $account->email,
            'error' => $error,
        ]);
    }

    /**
     * Alert admin about third-party API failures (Hostinger Mail API, Gemini AI API, etc.).
     */
    public function alertApiFailure(string $service, string $errorMessage, ?int $workspaceId = null): void
    {
        $title = "API Failure: {$service}";
        $message = "Integration with {$service} failed with message: {$errorMessage}.";

        $this->recordAndNotify($workspaceId ?? 1, 'api_failure', $title, $message, [
            'service' => $service,
            'error'   => $errorMessage,
        ]);
    }

    /**
     * Alert admin about high bounce rates.
     */
    public function alertExcessiveBounces(EmailAccount $account, int $bounces, float $rate): void
    {
        $title = "High Bounce Rate Alert: {$account->email}";
        $message = "Mailbox {$account->email} has recorded {$bounces} bounces (bounce rate: " . number_format($rate, 1) . "%).";

        $this->recordAndNotify($account->workspace_id, 'high_bounce', $title, $message, [
            'email_account_id' => $account->id,
            'email' => $account->email,
            'bounces' => $bounces,
            'rate' => $rate,
        ]);
    }

    /**
     * Helper to write CrmNotification and send email if enabled in AdminSettings.
     */
    protected function recordAndNotify(int $workspaceId, string $type, string $title, string $message, array $data = []): void
    {
        Log::warning("[AdminAlert] {$title}: {$message}", $data);

        // Find admin user for the workspace or primary system admin
        $adminUser = User::whereHas('role', function ($q) {
            $q->where('name', 'admin');
        })->first() ?? User::first();

        // 1. Create in-app notification in crm_notifications
        try {
            CrmNotification::create([
                'workspace_id' => $workspaceId,
                'user_id'      => $adminUser?->id,
                'type'         => $type,
                'title'        => $title,
                'message'      => $message,
                'data'         => $data,
                'read_at'      => null,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to create CrmNotification: " . $e->getMessage());
        }

        // 2. Dispatch email notification if admin notifications enabled
        try {
            $settings = AdminSetting::get('admin_notifications', []);
            $adminEmail = $settings['admin_email'] ?? config('mail.from.address');
            $notifySmtp = $settings['notify_smtp_failed'] ?? true;

            if ($adminEmail && $notifySmtp && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                // Send plain text email notification without throwing on fail
                Mail::raw($message, function ($mail) use ($adminEmail, $title) {
                    $mail->to($adminEmail)->subject("[Outreach.click Alert] {$title}");
                });
            }
        } catch (\Throwable $e) {
            Log::warning("Could not dispatch email alert: " . $e->getMessage());
        }
    }
}
