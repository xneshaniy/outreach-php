<?php

namespace App\Services;

use App\Models\EmailAccount;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HostingerMailService
{
    protected string $token;
    protected string $defaultPassword;

    public function __construct()
    {
        $this->token = config('services.hostinger.mail_token', env('HOSTINGER_MAIL_TOKEN', '7c2bf2c2cf66584ccdaf5c2193f3a2ef2b14dd378bd7067d9e7316986994fd95'));
        $this->defaultPassword = env('HOSTINGER_DEFAULT_PASSWORD', 'Outreach@123$');
    }

    /**
     * Fetch all mailboxes from Hostinger Mail API
     */
    public function fetchHostingerMailboxes(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Accept'        => 'application/json',
            ])->timeout(15)->get('https://api.mail.hostinger.com/api/v1/me');

            if (!$response->successful()) {
                Log::error('Hostinger Mail API /me error: ' . $response->body());
                return ['success' => false, 'error' => $response->body(), 'mailboxes' => []];
            }

            $data = $response->json('data.mailboxes', []);
            return ['success' => true, 'mailboxes' => $data];
        } catch (\Throwable $e) {
            Log::error('Hostinger Mail API exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'mailboxes' => []];
        }
    }

    /**
     * Synchronize all Hostinger mailboxes into CRM workspace email_accounts
     */
    public function syncMailboxes(int $workspaceId = 1): array
    {
        $res = $this->fetchHostingerMailboxes();
        if (!$res['success']) {
            return $res;
        }

        $mailboxes = $res['mailboxes'];
        $added = 0;
        $updated = 0;
        $encrypted = Crypt::encryptString($this->defaultPassword);
        $now = now()->toDateTimeString();

        foreach ($mailboxes as $mbox) {
            $email = strtolower(trim($mbox['address']));
            $name = ucfirst(explode('@', $email)[0]);

            $existing = DB::table('email_accounts')
                ->where('email', $email)
                ->where('workspace_id', $workspaceId)
                ->first();

            $accountData = [
                'workspace_id'            => $workspaceId,
                'name'                    => $name,
                'email'                   => $email,
                'normalized_email'        => $email,
                'provider'                => 'smtp',
                'host'                    => 'smtp.hostinger.com',
                'port'                    => 465,
                'encryption'              => 'ssl',
                'username'                => $email,
                'password_encrypted'      => $encrypted,
                'from_name'               => $name,
                'reply_to'                => $email,
                'daily_send_limit'        => 500,
                'is_active'               => 1,
                'track_opens'             => 1,
                'track_clicks'            => 1,
                'imap_host'               => 'imap.hostinger.com',
                'imap_port'               => 993,
                'imap_encryption'         => 'ssl',
                'imap_username'           => $email,
                'warmup_enabled'          => 1,
                'warmup_daily_limit'      => 5,
                'warmup_max_limit'        => 40,
                'warmup_step'             => 2,
                'warmup_status'           => 'warming',
                'warmup_reputation_score' => 100,
                'updated_at'              => $now,
            ];

            if ($existing) {
                $accountData['deleted_at'] = null;
                DB::table('email_accounts')->where('id', $existing->id)->update($accountData);
                $updated++;
            } else {
                $accountData['user_id'] = 1;
                $accountData['created_at'] = $now;
                $accountData['sent_today'] = 0;
                $accountData['bounced_count'] = 0;
                $accountData['failed_count'] = 0;
                $accountData['is_default'] = 0;
                $accountData['warmup_sent_today'] = 0;
                DB::table('email_accounts')->insert($accountData);
                $added++;
            }
        }

        return [
            'success'   => true,
            'total'     => count($mailboxes),
            'added'     => $added,
            'updated'   => $updated,
            'mailboxes' => $mailboxes,
        ];
    }
}
