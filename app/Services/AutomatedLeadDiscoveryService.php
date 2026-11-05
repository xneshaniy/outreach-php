<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutomatedLeadDiscoveryService
{
    public function __construct(
        protected GoogleMapsScraperService $mapsScraperService
    ) {}

    /**
     * Run lead discovery for a target country and industry, enrich websites,
     * detect country/language, score quality, and save qualified leads into CRM.
     */
    public function discoverAndSaveLeads(
        Workspace $workspace,
        string $country,
        string $industry,
        ?string $city = null,
        ?string $keywords = null,
        int $maxResults = 20,
        bool $enrichWebsites = true,
        bool $autoApprove = true
    ): array {
        $location = trim(($city ? "{$city}, " : "") . $country);
        $query = trim("{$industry} {$keywords}");

        Log::info("Starting automated lead discovery: '{$query}' in '{$location}' for workspace #{$workspace->id}");

        // 1. Fetch businesses using Google Maps Scraper + AI synthesis fallback
        $scrapeResult = $this->mapsScraperService->scrapeGoogleMaps(
            $workspace,
            $query,
            $location,
            min($maxResults, 100),
            $enrichWebsites
        );

        $items = $scrapeResult['data'] ?? [];
        $savedCount = 0;
        $duplicatesCount = 0;
        $savedLeads = [];

        foreach ($items as $item) {
            $companyName = trim($item['company_name'] ?? '');
            $email = strtolower(trim($item['email'] ?? ''));
            $website = trim($item['website'] ?? '');
            $phone = trim($item['phone'] ?? '');

            if (empty($companyName)) {
                continue;
            }

            // Detect country, ISO code and primary language
            $detection = CountryLanguageService::detect(
                $country ?: ($item['country'] ?? ''),
                $city ?: ($item['city'] ?? ''),
                $website,
                $item['address'] ?? ''
            );

            // Deduplication: check by email (if present) OR by company name within workspace
            $existing = Lead::where('workspace_id', $workspace->id)
                ->where(function ($q) use ($email, $companyName) {
                    if (!empty($email)) {
                        $q->where('normalized_email', $email);
                    }
                    if (!empty($companyName)) {
                        $q->orWhere('company_name', $companyName);
                    }
                })
                ->first();

            if ($existing) {
                $duplicatesCount++;
                continue;
            }

            // Quality score calculation (0 to 100)
            $score = 50;
            $verificationStatus = 'unverified';
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $score += 30;
                $verificationStatus = 'plausible';
            }
            if (!empty($website)) {
                $score += 10;
            }
            if (!empty($phone)) {
                $score += 10;
            }

            // Lead contact names
            $firstName = !empty($item['first_name']) ? $item['first_name'] : $companyName;
            $lastName = !empty($item['last_name']) ? $item['last_name'] : 'Lead';

            $fallbackEmail = !empty($email) ? $email : 'contact@' . (parse_url($website, PHP_URL_HOST) ?: 'example.com');

            $lead = Lead::create([
                'workspace_id'         => $workspace->id,
                'created_by'           => 1, // Default to admin
                'first_name'           => $firstName,
                'last_name'            => $lastName,
                'email'                => $fallbackEmail,
                'normalized_email'     => strtolower($fallbackEmail),
                'phone'                => $phone,
                'company_name'         => $companyName,
                'website'              => $website,
                'country'              => $detection['name'],
                'country_code'         => $detection['code'],
                'city'                 => $city ?: ($item['city'] ?? ''),
                'detected_language'    => $detection['lang'],
                'business_category'    => $industry,
                'source'               => 'google_maps_automated',
                'source_url'           => $item['website'] ?? null,
                'discovery_date'       => now(),
                'lead_quality_score'   => $score,
                'verification_status'  => $verificationStatus,
                'pipeline_stage'       => !empty($email) ? 'qualified' : 'new_lead',
                'approval_status'      => $autoApprove ? 'auto_approved' : 'pending_review',
                'status'               => 'new',
                'notes'                => "Discovered via automated search for '{$industry}' in {$location}.",
            ]);

            $savedLeads[] = $lead;
            $savedCount++;
        }

        // Log result in lead_discovery_logs
        DB::table('lead_discovery_logs')->insert([
            'workspace_id'       => $workspace->id,
            'query'              => $query,
            'country'            => $country,
            'city'               => $city,
            'results_count'      => count($items),
            'new_leads_saved'    => $savedCount,
            'duplicates_skipped' => $duplicatesCount,
            'status'             => 'success',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return [
            'success'            => true,
            'total_found'        => count($items),
            'new_leads_saved'    => $savedCount,
            'duplicates_skipped' => $duplicatesCount,
            'leads'              => $savedLeads,
        ];
    }
}
