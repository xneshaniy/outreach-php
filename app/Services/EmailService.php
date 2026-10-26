<?php

namespace App\Services;

use App\Models\CampaignRecipient;
use App\Models\EmailAccount;
use App\Models\Message;
use App\Models\Workspace;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;

class EmailService
{
    public function send(EmailAccount $account, array $to, string $subject, string $html, ?string $text = null, array $options = []): array
    {
        $transport = $this->createTransport($account);
        $mailer = new Mailer($transport);

        $message = (new Email())
            ->from($account->email, $options['from_name'] ?? $account->from_name ?? $account->name)
            ->to(...array_map(fn($t) => [$t['email'], $t['name'] ?? ''], $to))
            ->subject($subject)
            ->html($html);

        if ($text) {
            $message->text($text);
        }

        if (!empty($options['cc'])) {
            $message->cc(...array_map(fn($t) => [$t['email'], $t['name'] ?? ''], $options['cc']));
        }

        if (!empty($options['bcc'])) {
            $message->bcc(...array_map(fn($t) => [$t['email'], $t['name'] ?? ''], $options['bcc']));
        }

        if (!empty($options['reply_to'])) {
            $message->replyTo($options['reply_to']['email'], $options['reply_to']['name'] ?? '');
        } elseif ($account->reply_to) {
            $message->replyTo($account->reply_to);
        }

        if (!empty($options['headers'])) {
            foreach ($options['headers'] as $key => $value) {
                $message->getHeaders()->addTextHeader($key, $value);
            }
        }

        if (!empty($options['attachments'])) {
            foreach ($options['attachments'] as $attachment) {
                $message->attachFromPath($attachment['path'], $attachment['name'] ?? null, [
                    'mime_type' => $attachment['mime'] ?? null,
                ]);
            }
        }

        try {
            $mailer->send($message);
            return ['success' => true, 'message_id' => $message->getMessageId()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendUsingLaravelMailer(EmailAccount $account, Mailable $mailable, array $to): array
    {
        // Temporarily override mail config
        config([
            'mail.mailers.smtp.host' => $account->host,
            'mail.mailers.smtp.port' => $account->port,
            'mail.mailers.smtp.encryption' => $account->encryption,
            'mail.mailers.smtp.username' => $account->username,
            'mail.mailers.smtp.password' => $account->getPassword(),
            'mail.from.address' => $account->email,
            'mail.from.name' => $account->from_name ?? $account->name,
        ]);

        try {
            Mail::mailer('smtp')->to($to)->send($mailable);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function createTransport(EmailAccount $account): EsmtpTransport
    {
        $encryption = match ($account->encryption) {
            'ssl' => 'ssl',
            'tls' => 'tls',
            default => null,
        };

        return new EsmtpTransport(
            $account->host,
            $account->port,
            $encryption,
            $account->username,
            $account->getPassword()
        );
    }

    public function testConnection(EmailAccount $account): array
    {
        try {
            $transport = $this->createTransport($account);
            $mailer = new Mailer($transport);
            $mailer->getTransport()->start();
            $mailer->getTransport()->stop();
            return ['success' => true, 'message' => 'Connection successful'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function testCredentials(array $config): array
    {
        try {
            $encryption = match ($config['encryption'] ?? 'tls') {
                'ssl' => 'ssl',
                'tls' => 'tls',
                default => null,
            };

            $transport = new EsmtpTransport(
                $config['host'],
                (int)($config['port'] ?? 587),
                $encryption,
                $config['username'] ?? '',
                $config['password'] ?? ''
            );
            $mailer = new Mailer($transport);
            $mailer->getTransport()->start();
            $mailer->getTransport()->stop();

            return ['success' => true, 'message' => 'SMTP connection tested successfully!'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function buildTrackingPixelUrl(Message $message): string
    {
        return route('tracking.open', ['message' => $message->id, 'token' => $this->generateTrackingToken($message)]);
    }

    public function buildTrackingLinkUrl(Message $message, string $originalUrl): string
    {
        return route('tracking.click', [
            'message' => $message->id,
            'token' => $this->generateTrackingToken($message),
            'url' => base64_encode($originalUrl),
        ]);
    }

    public function buildUnsubscribeUrl(CampaignRecipient $recipient): string
    {
        $token = hash_hmac('sha256', $recipient->lead->email, config('app.key'));
        return route('unsubscribe', [
            'campaign' => $recipient->campaign_id,
            'lead' => $recipient->lead_id,
            'token' => $token,
        ]);
    }

    private function generateTrackingToken(Message $message): string
    {
        return hash_hmac('sha256', $message->id . $message->created_at->timestamp, config('app.key'));
    }

    public function verifyTrackingToken(Message $message, string $token): bool
    {
        $expected = $this->generateTrackingToken($message);
        return hash_equals($expected, $token);
    }

    public function injectTracking(string $html, Message $message): string
    {
        // Inject open tracking pixel
        $pixelUrl = $this->buildTrackingPixelUrl($message);
        $pixel = "<img src=\"{$pixelUrl}\" width=\"1\" height=\"1\" alt=\"\" style=\"display:none;\">";
        $html = str_ireplace('</body>', $pixel . '</body>', $html);

        // Inject click tracking
        $html = preg_replace_callback(
            '/<a\s+([^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*)>/i',
            function ($matches) use ($message) {
                $originalUrl = $matches[2];
                // Skip tracking for unsubscribe links and mailto:
                if (str_starts_with($originalUrl, 'mailto:') || str_contains($originalUrl, 'unsubscribe')) {
                    return $matches[0];
                }
                $trackedUrl = $this->buildTrackingLinkUrl($message, $originalUrl);
                return str_replace($originalUrl, $trackedUrl, $matches[0]);
            },
            $html
        );

        return $html;
    }
}