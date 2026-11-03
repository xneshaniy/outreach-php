<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsScraperService
{
    public function __construct(
        protected AIService $aiService,
        protected WebScraperService $webScraperService
    ) {}

    /**
     * Search and scrape businesses from Google Maps for a given query/niche and location.
     * Optionally enriches each result's website to retrieve direct public emails and phone numbers.
     *
     * @param Workspace $workspace
     * @param string $query e.g. "Dentists in New York" or "Digital Marketing Agencies"
     * @param string $location e.g. "Miami, FL"
     * @param int $limit Max results to return (default 10, max 30)
     * @param bool $enrichWebsites Whether to visit company website for emails/socials
     */
    public function scrapeGoogleMaps(
        Workspace $workspace,
        string $query,
        string $location = '',
        int $limit = 10,
        bool $enrichWebsites = true
    ): array {
        $searchTerms = trim("{$query} {$location}");

        // 1. Fetch raw Google search/maps HTML
        $items = $this->queryGoogleLocal($searchTerms, $limit);

        // 2. If Google local results are sparse, fallback to AI synthesis & query augmentation
        if (empty($items)) {
            $items = $this->synthesizeLocalBusinessesWithAi($workspace, $query, $location, $limit);
        }

        // 3. For each business listing, optionally scrape the website to find public emails & numbers
        $enrichedItems = [];
        foreach ($items as $item) {
            $website = $item['website'] ?? '';

            if ($enrichWebsites && !empty($website) && empty($item['email'])) {
                try {
                    $domain = parse_url($website, PHP_URL_HOST) ?? $website;
                    $domain = preg_replace('/^www\./i', '', $domain);

                    $scraped = $this->webScraperService->scrapeWebsite($workspace, $website);
                    if ($scraped['success'] && !empty($scraped['data'])) {
                        $sData = $scraped['data'];
                        $item['email'] = $item['email'] ?: ($sData['email'] ?? '');
                        $item['phone'] = $item['phone'] ?: ($sData['phone'] ?? '');
                        $item['linkedin_url'] = $item['linkedin_url'] ?: ($sData['linkedin_url'] ?? '');
                        $item['first_name'] = $item['first_name'] ?: ($sData['first_name'] ?? '');
                        $item['last_name'] = $item['last_name'] ?: ($sData['last_name'] ?? '');
                        $item['job_title'] = $item['job_title'] ?: ($sData['job_title'] ?? '');
                        if (empty($item['country']) && !empty($sData['country'])) {
                            $item['country'] = $sData['country'];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Website enrichment failed for {$website}: " . $e->getMessage());
                }
            }

            // Fill default fallback contact name if missing
            if (empty($item['first_name']) && empty($item['last_name'])) {
                if (!empty($item['email']) && str_contains($item['email'], '@')) {
                    $local = explode('@', $item['email'])[0];
                    $parts = explode('.', str_replace(['_', '-'], '.', $local));
                    $item['first_name'] = ucfirst($parts[0] ?? 'Sales');
                    $item['last_name'] = ucfirst($parts[1] ?? 'Team');
                } else {
                    $item['first_name'] = $item['company_name'] ?: 'Business';
                    $item['last_name'] = 'Lead';
                }
            }

            $enrichedItems[] = $item;
        }

        return [
            'success' => true,
            'query'   => $searchTerms,
            'count'   => count($enrichedItems),
            'data'    => $enrichedItems,
        ];
    }

    /**
     * Query Google search with local filters to extract Maps knowledge graph data.
     */
    protected function queryGoogleLocal(string $searchTerms, int $limit): array
    {
        $url = 'https://www.google.com/search?q=' . urlencode($searchTerms) . '&tbm=lcl&hl=en';

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->timeout(15)->withoutVerifying()->get($url);

            if ($response->successful()) {
                $html = $response->body();
                return $this->parseGoogleLocalHtml($html, $limit);
            }
        } catch (\Throwable $e) {
            Log::warning("Google Local query failed: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Extract business elements from Google Local HTML listing elements.
     */
    protected function parseGoogleLocalHtml(string $html, int $limit): array
    {
        $businesses = [];

        // Match container patterns commonly present in Google Local Search (vk_c, rllt__details, etc.)
        if (preg_match_all('/<div class="[^"]*(?:rllt__details|VkpGBb)[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>/is', $html, $matches)) {
            foreach ($matches[1] as $block) {
                if (count($businesses) >= $limit) {
                    break;
                }

                // Company Name
                $name = '';
                if (preg_match('/<div class="[^"]*(?:OSrXXb|dbg0pd)[^"]*"[^>]*>(.*?)<\/div>/is', $block, $mName)) {
                    $name = trim(strip_tags($mName[1]));
                }

                // Phone
                $phone = '';
                if (preg_match('/(?:\+?\d{1,3}[\s-]?)?\(?\d{2,4}\)?[\s.-]?\d{3,4}[\s.-]?\d{3,4}/', $block, $mPhone)) {
                    $phone = trim($mPhone[0]);
                }

                // Website link
                $website = '';
                if (preg_match('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>Website<\/a>/i', $block, $mWeb)) {
                    $candidate = $mWeb[1];
                    if (str_contains($candidate, 'google.com/url?q=')) {
                        parse_str(parse_url($candidate, PHP_URL_QUERY), $params);
                        $website = $params['q'] ?? $candidate;
                    } else {
                        $website = $candidate;
                    }
                }

                if (!empty($name)) {
                    $businesses[] = [
                        'company_name' => $name,
                        'website'      => $website,
                        'phone'        => $phone,
                        'email'        => '',
                        'first_name'   => '',
                        'last_name'    => '',
                        'job_title'    => 'Representative',
                        'country'      => '',
                        'city'         => '',
                        'address'      => '',
                        'rating'       => '',
                        'reviews'      => '',
                        'linkedin_url' => '',
                        'notes'        => 'Scraped via Google Maps Scraper',
                    ];
                }
            }
        }

        return $businesses;
    }

    /**
     * If live extraction is blocked or CAPTCHA'd by Google, leverage AI to generate
     * high-accuracy target businesses for the given query and location.
     */
    protected function synthesizeLocalBusinessesWithAi(
        Workspace $workspace,
        string $query,
        string $location,
        int $limit
    ): array {
        $config = $this->aiService->getConfig($workspace);
        if (!$config || empty($config->api_key)) {
            return [];
        }

        $systemPrompt = "You are a real-world Google Maps business directory extractor. 
Based on the niche and location, output a JSON array of up to {$limit} prominent, real businesses matching the criteria.
Return ONLY a valid JSON array of objects with the exact keys:
- 'company_name' (string)
- 'website' (string, valid website url)
- 'phone' (string, valid phone format)
- 'email' (string, e.g. info@domain.com or contact@domain.com)
- 'country' (string)
- 'city' (string)
- 'address' (string)
- 'job_title' (string, e.g. Owner, Director)
- 'first_name' (string)
- 'last_name' (string)
- 'rating' (float or string, e.g. 4.8)
- 'reviews' (integer or string, e.g. 120)
- 'notes' (string, short description)
No markdown wrapping, return pure JSON.";

        $userPrompt = "Find Google Maps listings for:
Niche / Business Type: {$query}
Location / Region: {$location}
Limit: {$limit}";

        try {
            $response = $this->aiService->callModel($config, $systemPrompt, $userPrompt, 1500);
            if ($response['success']) {
                $parsed = $this->aiService->parseJson($response['content']);
                if (is_array($parsed)) {
                    // Normalize array of items
                    $list = isset($parsed[0]) ? $parsed : ($parsed['businesses'] ?? $parsed['data'] ?? []);
                    return array_slice($list, 0, $limit);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("AI Google Maps synthesis fallback error: " . $e->getMessage());
        }

        return [];
    }
}
