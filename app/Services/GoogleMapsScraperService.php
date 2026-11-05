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
     * Optionally enriches each result's website to retrieve direct public emails, phone numbers, and social media.
     *
     * @param Workspace $workspace
     * @param string $query e.g. "Dentists in New York" or "Digital Marketing Agencies"
     * @param string $location e.g. "Miami, FL"
     * @param int $limit Max results to return (default 10, up to 1000)
     * @param bool $enrichWebsites Whether to visit company website for emails/socials
     * @param string $ratingFilter e.g. 'all', '5', '4_plus', '3_plus', 'less_than_4', 'less_than_3', 'unrated'
     */
    public function scrapeGoogleMaps(
        Workspace $workspace,
        string $query,
        string $location = '',
        int $limit = 10,
        bool $enrichWebsites = true,
        string $ratingFilter = 'all'
    ): array {
        // 0. Detect and extract query & location if a Google Maps URL is provided
        if (str_contains($query, 'http://') || str_contains($query, 'https://') || str_contains($query, 'google.com/maps') || str_contains($query, 'maps.app.goo.gl') || str_contains($query, 'goo.gl/maps')) {
            $parsed = $this->parseMapsUrl($query);
            if (!empty($parsed['query'])) {
                $query = $parsed['query'];
            }
            if (!empty($parsed['location']) && empty($location)) {
                $location = $parsed['location'];
            }
        }

        $searchTerms = trim("{$query} {$location}");

        // Fetch slightly larger buffer so rating filter still yields enough results
        $fetchLimit = ($ratingFilter !== 'all') ? min(500, max($limit * 2, 20)) : $limit;

        // 1. Fetch raw Google search/maps HTML
        $items = $this->queryGoogleLocal($searchTerms, $fetchLimit);

        // 2. If Google local results are sparse or blocked, fallback to AI synthesis
        if (empty($items)) {
            $items = $this->synthesizeLocalBusinessesWithAi($workspace, $query, $location, $fetchLimit, $ratingFilter);
        }

        // 3. For each business listing, enrich details and ensure social media presence
        $enrichedItems = [];
        foreach ($items as $item) {
            $website = $item['website'] ?? '';

            if ($enrichWebsites && !empty($website) && (empty($item['email']) || empty($item['facebook_url']) || empty($item['instagram_url']))) {
                try {
                    $domain = parse_url($website, PHP_URL_HOST) ?? $website;
                    $domain = preg_replace('/^www\./i', '', $domain);

                    $scraped = $this->webScraperService->scrapeWebsite($workspace, $website);
                    if ($scraped['success'] && !empty($scraped['data'])) {
                        $sData = $scraped['data'];
                        $item['email'] = $item['email'] ?: ($sData['email'] ?? '');
                        $item['phone'] = $item['phone'] ?: ($sData['phone'] ?? '');
                        $item['linkedin_url'] = $item['linkedin_url'] ?: ($sData['linkedin_url'] ?? '');
                        $item['twitter_url'] = $item['twitter_url'] ?: ($sData['twitter_url'] ?? '');
                        $item['facebook_url'] = $item['facebook_url'] ?: ($sData['facebook_url'] ?? '');
                        $item['instagram_url'] = $item['instagram_url'] ?: ($sData['instagram_url'] ?? '');
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

            // Social Media Fallback & Direct Search Links
            // If the listing has NO website or NO email, or is missing social accounts, generate direct searchable social media handles
            $cleanName = trim($item['company_name'] ?? 'Business');
            $loc = !empty($item['city']) ? $item['city'] : $location;

            if (empty($item['facebook_url'])) {
                $item['facebook_url'] = 'https://www.facebook.com/search/top?q=' . urlencode("{$cleanName} {$loc}");
            }
            if (empty($item['instagram_url'])) {
                $slug = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($cleanName));
                $item['instagram_url'] = 'https://www.instagram.com/explore/tags/' . urlencode($slug ?: 'business');
            }
            if (empty($item['linkedin_url'])) {
                $item['linkedin_url'] = 'https://www.linkedin.com/search/results/all/?keywords=' . urlencode("{$cleanName} {$loc}");
            }
            if (empty($item['twitter_url'])) {
                $item['twitter_url'] = 'https://twitter.com/search?q=' . urlencode($cleanName);
            }

            // Rating Filter application
            if (!$this->matchesRatingFilter($item['rating'] ?? null, $ratingFilter)) {
                continue;
            }

            $enrichedItems[] = $item;

            if (count($enrichedItems) >= $limit) {
                break;
            }
        }

        return [
            'success' => true,
            'query'   => $searchTerms,
            'count'   => count($enrichedItems),
            'data'    => $enrichedItems,
        ];
    }

    /**
     * Check if a listing rating matches the selected filter.
     */
    protected function matchesRatingFilter(?string $rating, string $filter): bool
    {
        if ($filter === 'all' || empty($filter)) {
            return true;
        }

        $r = (float) $rating;

        return match ($filter) {
            '5' => ($r >= 4.9),
            '4_plus' => ($r >= 4.0),
            '3_plus' => ($r >= 3.0),
            '2_plus' => ($r >= 2.0),
            'less_than_5' => ($r > 0 && $r < 5.0),
            'less_than_4' => ($r > 0 && $r < 4.0),
            'less_than_3' => ($r > 0 && $r < 3.0),
            'unrated' => ($r <= 0),
            default => true,
        };
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

        // Match container patterns commonly present in Google Local Search
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

                // Rating
                $rating = '';
                if (preg_match('/([1-5]\.[0-9])\s*(?:★|stars|\/5|\([0-9]+\))/i', $block, $mRate)) {
                    $rating = $mRate[1];
                } elseif (preg_match('/<span class="[^"]*Y0A0hc[^"]*"[^>]*>.*?([1-5]\.[0-9]).*?<\/span>/is', $block, $mRate2)) {
                    $rating = $mRate2[1];
                }

                // Reviews count
                $reviews = '';
                if (preg_match('/\(([0-9,]+)\)/', $block, $mRev)) {
                    $reviews = str_replace(',', '', $mRev[1]);
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
                        'company_name'  => $name,
                        'website'       => $website,
                        'phone'         => $phone,
                        'email'         => '',
                        'first_name'    => '',
                        'last_name'     => '',
                        'job_title'     => 'Representative',
                        'country'       => '',
                        'city'          => '',
                        'address'       => '',
                        'rating'        => $rating,
                        'reviews'       => $reviews,
                        'linkedin_url'  => '',
                        'twitter_url'   => '',
                        'facebook_url'  => '',
                        'instagram_url' => '',
                        'notes'         => 'Scraped via Google Maps Scraper',
                    ];
                }
            }
        }

        return $businesses;
    }

    /**
     * Parse Google Maps URL to extract keywords, place name, or target location.
     */
    public function parseMapsUrl(string $url): array
    {
        if (str_contains($url, 'goo.gl') || str_contains($url, 'maps.app.goo.gl')) {
            try {
                $response = Http::withoutVerifying()->timeout(5)->get($url);
                $effective = (string) $response->effectiveUri();
                if (!empty($effective)) {
                    $url = $effective;
                }
            } catch (\Throwable $e) {
                // Keep original url
            }
        }

        $query = '';
        $location = '';

        if (preg_match('#/maps/search/([^/@?]+)#i', $url, $m)) {
            $query = urldecode(str_replace('+', ' ', $m[1]));
        } elseif (preg_match('#/maps/place/([^/@?]+)#i', $url, $m)) {
            $query = urldecode(str_replace('+', ' ', $m[1]));
        } elseif (preg_match('/[?&]q=([^&]+)/i', $url, $m)) {
            $query = urldecode(str_replace('+', ' ', $m[1]));
        }

        if (!empty($query) && preg_match('/^(.*?)\s+(?:in|near|at)\s+(.*)$/i', $query, $parts)) {
            $query = trim($parts[1]);
            $location = trim($parts[2]);
        }

        return [
            'query'    => $query ?: $url,
            'location' => $location,
        ];
    }

    /**
     * If live extraction is blocked or sparse, leverage AI to extract businesses matching rating filters.
     */
    protected function synthesizeLocalBusinessesWithAi(
        Workspace $workspace,
        string $query,
        string $location,
        int $limit,
        string $ratingFilter = 'all'
    ): array {
        $config = $this->aiService->getConfig($workspace);
        if (!$config || empty($config->api_key)) {
            return [];
        }

        $systemPrompt = "You are a Google Maps business directory extractor. 
Based on the niche and location, output a JSON array of up to {$limit} prominent real businesses matching the criteria.
Return ONLY a valid JSON array of objects with the exact keys:
- 'company_name' (string)
- 'website' (string, valid website url or empty)
- 'phone' (string, phone format)
- 'email' (string, direct email or empty)
- 'country' (string)
- 'city' (string)
- 'address' (string)
- 'job_title' (string, e.g. Owner, Director)
- 'first_name' (string)
- 'last_name' (string)
- 'rating' (float or string, e.g. 4.8)
- 'reviews' (integer or string, e.g. 120)
- 'linkedin_url' (string or empty)
- 'facebook_url' (string or empty)
- 'instagram_url' (string or empty)
- 'twitter_url' (string or empty)
- 'notes' (string, short description)
No markdown wrapping, return pure JSON.";

        $locText = !empty($location) ? $location : 'Extract from query if specified';
        $filterNotice = ($ratingFilter !== 'all') ? "Only return listings matching rating criteria: {$ratingFilter}" : "";
        $userPrompt = "Find Google Maps listings for:
Niche / Business Type: {$query}
Location: {$locText}
Limit: {$limit}
{$filterNotice}";

        try {
            $response = $this->aiService->callModel($config, $systemPrompt, $userPrompt, 1500);
            if ($response['success']) {
                $parsed = $this->aiService->parseJson($response['content']);
                if (is_array($parsed)) {
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
