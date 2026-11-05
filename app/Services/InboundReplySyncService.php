<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\InboundEmailReply;
use App\Models\Lead;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InboundReplySyncService
{
    /**
     * Poll incoming mail for all active email accounts via Hostinger IMAP.
     * Hostinger shared hosting handles standard PHP streams & sockets.
     */
    public function syncAllAccounts(): array
    {
        $accounts = EmailAccount::where('is_active', true)->get();
        $stats = [
            'accounts_checked' => 0,
            'replies_found'    => 0,
            'errors'           => 0,
        ];

        foreach ($accounts as $account) {
            try {
                $count = $this->syncAccountReplies($account);
                $stats['replies_found'] += $count;
                $stats['accounts_checked']++;
            } catch (\Throwable $e) {
                Log::error("Failed to sync replies for account {$account->email}: " . $e->getMessage());
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Ingest and match replies for a specific email account
     */
    public function syncAccountReplies(EmailAccount $account): int
    {
        $host = $account->imap_host ?: 'imap.hostinger.com';
        $port = (int) ($account->imap_port ?: 993);
        $username = $account->imap_username ?: $account->email;
        $password = $account->getPassword();

        if (empty($password)) {
            return 0;
        }

        // Open IMAP mailbox connection with SSL
        $mailbox = "{" . "{$host}:{$port}/imap/ssl}INBOX";
        
        // Suppress warnings in case of temporary handshake timeout
        $mbox = @imap_open($mailbox, $username, $password, OP_READONLY, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
        if (!$mbox) {
            return 0;
        }

        // Search for unread or recent messages in INBOX
        $messageNumbers = @imap_search($mbox, 'UNSEEN', SE_UID);
        if (!$messageNumbers) {
            // Also check RECENT if unseen returns empty
            $messageNumbers = @imap_search($mbox, 'SINCE ' . date('d-M-Y', strtotime('-2 days')), SE_UID);
        }

        if (empty($messageNumbers)) {
            imap_close($mbox);
            return 0;
        }

        $newRepliesCount = 0;

        foreach ($messageNumbers as $uid) {
            $header = @imap_headerinfo($mbox, @imap_msgno($mbox, $uid));
            if (!$header) {
                continue;
            }

            $senderEmail = strtolower($header->from[0]->mailbox . '@' . $header->from[0]->host);
            $senderName = $header->from[0]->personal ?? explode('@', $senderEmail)[0];
            $subject = $header->subject ?? '(No Subject)';
            $messageId = $header->message_id ?? null;
            $inReplyTo = $header->in_reply_to ?? null;
            $date = isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : now();

            // Ignore warm-up simulation loopbacks
            if (str_contains(strtolower($subject), 'warmup') || str_contains($subject, 'Quick sync regarding') || str_contains($subject, 'Update on the partnership schedule')) {
                continue;
            }

            // Deduplication: check if reply already saved
            $alreadySaved = InboundEmailReply::where('workspace_id', $account->workspace_id)
                ->where(function ($q) use ($messageId, $senderEmail, $date) {
                    if (!empty($messageId)) {
                        $q->where('message_id', $messageId);
                    } else {
                        $q->where('sender_email', $senderEmail)->where('received_at', $date);
                    }
                })->exists();

            if ($alreadySaved) {
                continue;
            }

            // Fetch email body text
            $body = @imap_body($mbox, @imap_msgno($mbox, $uid));

            // Categorize sentiment
            $sentiment = $this->classifyReplySentiment($subject . ' ' . $body);

            // Match with Lead in workspace
            $lead = Lead::where('workspace_id', $account->workspace_id)
                ->where('normalized_email', $senderEmail)
                ->first();

            $reply = InboundEmailReply::create([
                'workspace_id'      => $account->workspace_id,
                'email_account_id'  => $account->id,
                'lead_id'           => $lead?->id,
                'message_id'        => $messageId,
                'in_reply_to'       => $inReplyTo,
                'sender_email'      => $senderEmail,
                'sender_name'       => $senderName,
                'recipient_email'   => $account->email,
                'subject'           => $subject,
                'body_text'         => mb_substr(strip_tags($body), 0, 5000),
                'body_html'         => mb_substr($body, 0, 10000),
                'sentiment'         => $sentiment,
                'is_read'           => false,
                'notification_sent' => true,
                'received_at'       => $date,
            ]);

            // Update Lead pipeline stage & reply timestamps if lead matched
            if ($lead) {
                $stage = 'replied';
                if ($sentiment === 'interested') {
                    $stage = 'interested';
                } elseif ($sentiment === 'not_interested') {
                    $stage = 'not_interested';
                } elseif ($sentiment === 'unsubscribe') {
                    $stage = 'unsubscribed';
                }

                $lead->update([
                    'reply_status'     => $sentiment,
                    'pipeline_stage'   => $stage,
                    'last_replied_at'  => $date,
                    'last_activity_at' => now(),
                ]);
            }

            // Create In-App Notification
            DB::table('notifications')->insert([
                'id'             => (string) \Illuminate\Support\Str::uuid(),
                'type'           => 'App\Notifications\NewLeadReplyNotification',
                'notifiable_type'=> 'App\Models\User',
                'notifiable_id'  => $account->user_id ?: 1,
                'data'           => json_encode([
                    'reply_id'     => $reply->id,
                    'lead_id'      => $lead?->id,
                    'sender_email' => $senderEmail,
                    'sender_name'  => $senderName,
                    'company_name' => $lead?->company_name ?? 'Prospect',
                    'subject'      => $subject,
                    'sentiment'    => $sentiment,
                    'preview'      => mb_substr(strip_tags($body), 0, 150),
                    'received_at'  => $date,
                ]),
                'read_at'        => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $newRepliesCount++;
        }

        imap_close($mbox);
        return $newRepliesCount;
    }

    /**
     * Categorize reply sentiment (Interested, Not Interested, Follow-up, Out of Office, Unsubscribe, Unknown)
     */
    protected function classifyReplySentiment(string $text): string
    {
        $lower = strtolower($text);

        if (str_contains($lower, 'unsubscribe') || str_contains($lower, 'abmelden') || str_contains($lower, 'désinscription') || str_contains($lower, 'stop')) {
            return 'unsubscribe';
        }
        if (str_contains($lower, 'out of office') || str_contains($lower, 'abwesend') || str_contains($lower, 'congé') || str_contains($lower, 'vacances')) {
            return 'out_of_office';
        }
        if (str_contains($lower, 'interested') || str_contains($lower, 'call') || str_contains($lower, 'demo') || str_contains($lower, 'discuter') || str_contains($lower, 'échange') || str_contains($lower, 'gespräch') || str_contains($lower, 'termin')) {
            return 'interested';
        }
        if (str_contains($lower, 'not interested') || str_contains($lower, 'pas intéressé') || str_contains($lower, 'kein interesse') || str_contains($lower, 'merci mais non')) {
            return 'not_interested';
        }
        if (str_contains($lower, 'later') || str_contains($lower, 'next quarter') || str_contains($lower, 'recontact') || str_contains($lower, 'plus tard')) {
            return 'follow_up';
        }

        return 'unknown';
    }
}
