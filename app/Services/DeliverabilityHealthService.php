<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class DeliverabilityHealthService
{
    /**
     * Common DKIM selectors to probe if custom selector is not specified
     */
    private const COMMON_DKIM_SELECTORS = ['default', 'google', 'k1', 'mail', 'smtp', 'hostinger', 's1', 's2048'];

    /**
     * Inspect DNS records for an EmailAccount's sending domain.
     */
    public function checkAccountHealth(EmailAccount $account): array
    {
        $email = strtolower(trim($account->email));
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return [
                'success' => false,
                'error' => 'Invalid email address',
            ];
        }

        $domain = $parts[1];

        // 1. Check MX Record
        $hasMx = checkdnsrr($domain, 'MX');

        // 2. Check SPF (TXT records on root domain containing v=spf1)
        $spfStatus = 'missing';
        $spfRecord = null;
        $txtRecords = @dns_get_record($domain, DNS_TXT) ?: [];
        foreach ($txtRecords as $rec) {
            $txt = $rec['txt'] ?? ($rec['entries'][0] ?? '');
            if (stripos($txt, 'v=spf1') !== false) {
                $spfStatus = 'valid';
                $spfRecord = $txt;
                break;
            }
        }

        // 3. Check DMARC (TXT record at _dmarc.<domain>)
        $dmarcStatus = 'missing';
        $dmarcRecord = null;
        $dmarcRecords = @dns_get_record("_dmarc.{$domain}", DNS_TXT) ?: [];
        foreach ($dmarcRecords as $rec) {
            $txt = $rec['txt'] ?? ($rec['entries'][0] ?? '');
            if (stripos($txt, 'v=DMARC1') !== false) {
                $dmarcStatus = 'valid';
                $dmarcRecord = $txt;
                break;
            }
        }

        // 4. Check DKIM
        $dkimStatus = 'unknown';
        $dkimSelectorFound = null;
        foreach (self::COMMON_DKIM_SELECTORS as $selector) {
            $dkimHost = "{$selector}._domainkey.{$domain}";
            $dkimRecords = @dns_get_record($dkimHost, DNS_TXT) ?: [];
            foreach ($dkimRecords as $rec) {
                $txt = $rec['txt'] ?? ($rec['entries'][0] ?? '');
                if (stripos($txt, 'v=DKIM1') !== false || stripos($txt, 'p=') !== false) {
                    $dkimStatus = 'valid';
                    $dkimSelectorFound = $selector;
                    break 2;
                }
            }
        }

        if ($dkimStatus === 'unknown') {
            // Check if MX exists and domain is active - default to warning rather than failed
            $dkimStatus = $hasMx ? 'unverified' : 'missing';
        }

        // 5. Calculate Health Score (0–100%)
        $score = 100;
        if (!$hasMx) $score -= 40;
        if ($spfStatus !== 'valid') $score -= 30;
        if ($dmarcStatus !== 'valid') $score -= 20;
        if ($dkimStatus === 'missing') $score -= 10;

        // Factor in historical bounce rate for this account
        $totalSent = Message::where('email_account_id', $account->id)->whereNotNull('sent_at')->count();
        $totalBounced = Message::where('email_account_id', $account->id)->where(function ($q) {
            $q->whereNotNull('bounced_at')->orWhere('status', 'bounced');
        })->count();

        $bounceRate = $totalSent > 0 ? ($totalBounced / $totalSent) * 100 : 0;
        if ($bounceRate > 5.0) {
            $score = max(20, $score - 30); // Penalty for high bounce rate
        }

        $score = max(0, min(100, $score));

        // Update model
        $account->update([
            'spf_status'           => $spfStatus,
            'dkim_status'          => $dkimStatus,
            'dmarc_status'         => $dmarcStatus,
            'health_score'         => $score,
            'last_health_check_at' => now(),
        ]);

        return [
            'success'          => true,
            'domain'           => $domain,
            'has_mx'           => $hasMx,
            'spf_status'       => $spfStatus,
            'spf_record'       => $spfRecord,
            'dmarc_status'     => $dmarcStatus,
            'dmarc_record'     => $dmarcRecord,
            'dkim_status'      => $dkimStatus,
            'dkim_selector'    => $dkimSelectorFound,
            'health_score'     => $score,
            'bounce_rate'      => round($bounceRate, 2),
            'high_risk'        => $bounceRate > 5.0 || $score < 60,
        ];
    }
}
