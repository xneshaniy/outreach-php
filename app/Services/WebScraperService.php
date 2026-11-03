<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebScraperService
{
    public function __construct(
        protected AIService $aiService
    ) {}

    /**
     * Normalize and prepare a website URL.
     */
    public function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }
        return rtrim($url, '/');
    }

    /**
     * Scrape a website: fetches homepage + contact/about pages, extracts emails, phone numbers,
     * social links, company details, and runs AI to identify contact names, job title, industry, and country.
     */
    public function scrapeWebsite(Workspace $workspace, string $url): array
    {
        $targetUrl = $this->normalizeUrl($url);
        $domain = parse_url($targetUrl, PHP_URL_HOST) ?? $targetUrl;
        $domain = preg_replace('/^www\./i', '', $domain);

        // 1. Fetch Homepage
        $homeHtml = $this->fetchHtml($targetUrl);
        if (!$homeHtml) {
            return [
                'success' => false,
                'error' => "Unable to connect to {$targetUrl}. Please check the URL and try again.",
            ];
        }

        // 2. Discover and fetch contact/about page if present
        $additionalHtml = '';
        $subLinks = $this->extractInternalContactLinks($homeHtml, $targetUrl);
        foreach (array_slice($subLinks, 0, 2) as $subLink) {
            $subHtml = $this->fetchHtml($subLink);
            if ($subHtml) {
                $additionalHtml .= "\n" . $subHtml;
            }
        }

        $combinedHtml = $homeHtml . "\n" . $additionalHtml;

        // 3. Regex Pattern Extractions
        $emails = $this->extractEmails($combinedHtml, $domain);
        $phones = $this->extractPhones($combinedHtml);
        $linkedinUrl = $this->extractSocialLink($combinedHtml, 'linkedin.com');
        $twitterUrl = $this->extractSocialLink($combinedHtml, 'twitter.com', 'x.com');
        $title = $this->extractPageTitle($homeHtml);
        $metaDesc = $this->extractMetaDescription($homeHtml);

        // 4. Clean text content for AI enrichment
        $cleanText = $this->htmlToCleanText($homeHtml . "\n" . $additionalHtml, 2500);

        // 5. AI Enrichment for Company Name, Country, Industry, and Person Name
        $aiData = $this->runAiEnrichment($workspace, $domain, $title, $metaDesc, $cleanText, $emails, $phones);

        $companyName = $aiData['company_name'] ?? $this->fallbackCompanyName($domain, $title);
        $country = $aiData['country'] ?? '';
        $industry = $aiData['industry'] ?? '';
        $city = $aiData['city'] ?? '';
        $firstName = $aiData['first_name'] ?? '';
        $lastName = $aiData['last_name'] ?? '';
        $jobTitle = $aiData['job_title'] ?? 'Contact';
        $summary = $aiData['description'] ?? $metaDesc;

        $primaryEmail = !empty($emails) ? $emails[0] : ($aiData['email'] ?? '');
        $primaryPhone = !empty($phones) ? $phones[0] : '';

        // If no first/last name could be found, provide a clean default
        if (empty($firstName) && empty($lastName)) {
            if (!empty($primaryEmail) && str_contains($primaryEmail, '@')) {
                $localPart = explode('@', $primaryEmail)[0];
                $parts = explode('.', str_replace(['_', '-'], '.', $localPart));
                $firstName = ucfirst($parts[0] ?? 'Sales');
                $lastName = ucfirst($parts[1] ?? 'Team');
            } else {
                $firstName = $companyName ?: 'Business';
                $lastName = 'Lead';
            }
        }

        return [
            'success' => true,
            'data' => [
                'company_name'   => $companyName,
                'domain'         => $domain,
                'website'        => $targetUrl,
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'email'          => $primaryEmail,
                'all_emails'     => $emails,
                'phone'          => $primaryPhone,
                'all_phones'     => $phones,
                'job_title'      => $jobTitle,
                'country'        => $country,
                'city'           => $city,
                'industry'       => $industry,
                'description'    => $summary,
                'linkedin_url'   => $linkedinUrl,
                'twitter_url'    => $twitterUrl,
            ],
        ];
    }

    /**
     * Download page HTML with user agent and redirection support.
     */
    protected function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(15)->withoutVerifying()->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable $e) {
            Log::warning("WebScraper fetch error for {$url}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Extract internal contact or about links from homepage HTML.
     */
    protected function extractInternalContactLinks(string $html, string $baseUrl): array
    {
        $links = [];
        $parsedBase = parse_url($baseUrl);
        $host = $parsedBase['host'] ?? '';

        if (preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\']/i', $html, $matches)) {
            foreach ($matches[1] as $href) {
                if (preg_match('/(contact|about|team|support|reach)/i', $href)) {
                    if (str_starts_with($href, 'http')) {
                        if (str_contains($href, $host)) {
                            $links[] = $href;
                        }
                    } elseif (str_starts_with($href, '/')) {
                        $scheme = $parsedBase['scheme'] ?? 'https';
                        $links[] = "{$scheme}://{$host}{$href}";
                    }
                }
            }
        }

        return array_values(array_unique($links));
    }

    /**
     * Extract valid public email addresses.
     */
    protected function extractEmails(string $html, string $domain): array
    {
        $emails = [];

        // 1. Check mailto: links
        if (preg_match_all('/mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $html, $matches)) {
            foreach ($matches[1] as $e) {
                $emails[] = strtolower(trim($e));
            }
        }

        // 2. Scan text with regex
        $clean = strip_tags($html);
        if (preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $clean, $matches)) {
            foreach ($matches[0] as $e) {
                $emails[] = strtolower(trim($e));
            }
        }

        // Filter invalid and non-contact emails
        $filtered = [];
        $blacklist = ['wixpress.com', 'sentry.io', 'example.com', 'domain.com', 'yourcompany.com', 'bootstrap.com', 'wordpress.org', 'schema.org'];
        foreach (array_unique($emails) as $email) {
            $emailDomain = substr(strrchr($email, '@'), 1);
            if (in_array($emailDomain, $blacklist)) {
                continue;
            }
            if (preg_match('/\.(png|jpg|jpeg|gif|webp|svg|css|js)$/i', $email)) {
                continue;
            }
            // Prioritize emails that match target company domain
            if ($emailDomain === $domain || str_ends_with($emailDomain, '.' . $domain)) {
                array_unshift($filtered, $email);
            } else {
                $filtered[] = $email;
            }
        }

        return array_values(array_unique($filtered));
    }

    /**
     * Extract phone numbers.
     */
    protected function extractPhones(string $html): array
    {
        $phones = [];

        // 1. Check tel: links
        if (preg_match_all('/tel:([+0-9\s().-]{7,25})/i', $html, $matches)) {
            foreach ($matches[1] as $p) {
                $phones[] = trim($p);
            }
        }

        // 2. Regex for international or standard numbers
        $clean = strip_tags($html);
        if (preg_match_all('/(?:\+?\d{1,3}[\s-]?)?\(?\d{2,4}\)?[\s.-]?\d{3,4}[\s.-]?\d{3,4}/', $clean, $matches)) {
            foreach ($matches[0] as $p) {
                $trimmed = trim($p);
                if (strlen(preg_replace('/[^0-9]/', '', $trimmed)) >= 7) {
                    $phones[] = $trimmed;
                }
            }
        }

        return array_values(array_slice(array_unique($phones), 0, 3));
    }

    /**
     * Extract social media link.
     */
    protected function extractSocialLink(string $html, string ...$domains): ?string
    {
        foreach ($domains as $d) {
            if (preg_match('/href=["\'](https?:\/\/(?:www\.)?' . preg_quote($d, '/') . '\/[^"\'>\s]+)["\']/i', $html, $match)) {
                return $match[1];
            }
        }
        return null;
    }

    /**
     * Extract title tag.
     */
    protected function extractPageTitle(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match)) {
            return trim(html_entity_decode(strip_tags($match[1])));
        }
        return '';
    }

    /**
     * Extract meta description.
     */
    protected function extractMetaDescription(string $html): string
    {
        if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i', $html, $match)) {
            return trim(html_entity_decode($match[1]));
        }
        return '';
    }

    /**
     * Clean raw HTML to concise text for AI context.
     */
    protected function htmlToCleanText(string $html, int $limit = 2500): string
    {
        // Strip script, style, navigation, footer tags
        $clean = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $clean = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $clean);
        $clean = strip_tags($clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return trim(substr($clean, 0, $limit));
    }

    /**
     * Derive a fallback company name from domain or title.
     */
    protected function fallbackCompanyName(string $domain, string $title): string
    {
        if (!empty($title)) {
            $parts = explode('|', $title);
            $parts2 = explode('-', $parts[0]);
            $candidate = trim($parts2[0]);
            if (strlen($candidate) >= 2 && strlen($candidate) <= 50) {
                return $candidate;
            }
        }

        $base = explode('.', $domain)[0];
        return ucfirst($base);
    }

    /**
     * Use AIService to analyze the scraped website text and extract clean lead metadata.
     */
    protected function runAiEnrichment(
        Workspace $workspace,
        string $domain,
        string $title,
        string $metaDesc,
        string $cleanText,
        array $emails,
        array $phones
    ): array {
        $config = $this->aiService->getConfig($workspace);
        if (!$config || empty($config->api_key)) {
            return [
                'company_name' => $this->fallbackCompanyName($domain, $title),
                'country'      => '',
                'industry'     => 'Business Services',
                'description'  => $metaDesc,
            ];
        }

        $systemPrompt = "You are an automated lead intelligence extractor. Analyze the website text and return a JSON object with:
'company_name' (official clean company name string),
'country' (country name or location mentioned, string),
'city' (city string if mentioned, else empty),
'industry' (primary industry or niche, e.g. SaaS, Marketing, Real Estate),
'first_name' (first name of key founder, contact or executive if visible, otherwise empty),
'last_name' (last name if visible, otherwise empty),
'job_title' (title or 'Founder / Representative'),
'description' (1-sentence summary of what this company does).
Return pure JSON with no markdown wrapping.";

        $userPrompt = "Website Domain: {$domain}
Page Title: {$title}
Meta Description: {$metaDesc}
Scraped Emails: " . implode(', ', $emails) . "
Scraped Phone Numbers: " . implode(', ', $phones) . "
Website Excerpt:
{$cleanText}";

        try {
            $response = $this->aiService->callModel($config, $systemPrompt, $userPrompt, 600);
            if ($response['success']) {
                $parsed = $this->aiService->parseJson($response['content']);
                if (is_array($parsed)) {
                    return $parsed;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("AI Scraping enrichment failed: " . $e->getMessage());
        }

        return [];
    }
}
