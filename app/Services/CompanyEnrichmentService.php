<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;

class CompanyEnrichmentService
{
    public function __construct(
        protected AIService $aiService,
        protected WebScraperService $webScraperService
    ) {}

    /**
     * Enrich a company from its domain / website using scraping + AI.
     * Returns true if any new data was saved.
     */
    public function enrichFromDomain(Company $company, Workspace $workspace): bool
    {
        // Skip if already verified — never overwrite verified data
        if ($company->enrichment_status === 'verified') {
            return false;
        }

        $url = $company->website ?: ($company->domain ? 'https://' . $company->domain : null);
        if (!$url) {
            return false;
        }

        try {
            $scraped = $this->webScraperService->scrapeWebsite($workspace, $url);

            if (!($scraped['success'] ?? false)) {
                $company->enrichment_status = 'failed';
                $company->save();
                return false;
            }

            $dirty = false;
            $data  = $scraped['data'] ?? $scraped;

            $map = [
                'phone'       => $data['phone'] ?? null,
                'city'        => $data['city'] ?? null,
                'country'     => $data['country'] ?? null,
                'address'     => $data['address'] ?? null,
                'industry'    => $data['industry'] ?? null,
                'description' => $data['description'] ?? null,
                'linkedin_url'=> $data['linkedin_url'] ?? null,
                'twitter_url' => $data['twitter_url'] ?? null,
            ];

            foreach ($map as $field => $value) {
                if (!empty($value) && empty($company->$field)) {
                    $company->$field = $value;
                    $dirty = true;
                }
            }

            // Handle emails from scraping
            if (!empty($data['emails']) && is_array($data['emails'])) {
                $matchingService = app(UnifiedMatchingService::class);
                foreach ($data['emails'] as $em) {
                    $type = $matchingService->classifyEmail($em);
                    if ($type === 'company') {
                        $company->addCompanyEmail($em);
                        $dirty = true;
                    }
                }
            }

            if ($dirty) {
                $company->enrichment_status = 'ai_suggested';
                $company->enrichment_source = 'web_scrape';
                $company->source_url        = $url;
                $company->save();
            }

            return $dirty;
        } catch (\Exception $e) {
            Log::warning("CompanyEnrichmentService: failed to enrich company {$company->id}: " . $e->getMessage());
            $company->enrichment_status = 'failed';
            $company->save();
            return false;
        }
    }

    /**
     * Enrich a lead's missing info from its company (or its own domain).
     */
    public function enrichLead(Lead $lead, Workspace $workspace): bool
    {
        if ($lead->company && $lead->company->website) {
            $url = $lead->company->website;
        } elseif ($lead->website) {
            $url = $lead->website;
        } else {
            return false;
        }

        try {
            $scraped = $this->webScraperService->scrapeWebsite($workspace, $url);
            if (!($scraped['success'] ?? false)) {
                return false;
            }

            $data  = $scraped['data'] ?? $scraped;
            $dirty = false;

            $map = [
                'industry' => $data['industry'] ?? null,
                'country'  => $data['country'] ?? null,
                'city'     => $data['city'] ?? null,
                'phone'    => $data['phone'] ?? null,
            ];

            foreach ($map as $field => $value) {
                if (!empty($value) && empty($lead->$field)) {
                    $lead->$field = $value;
                    $dirty = true;
                }
            }

            if ($dirty) {
                $lead->enrichment_source = 'web_scrape';
                $lead->save();
            }

            return $dirty;
        } catch (\Exception $e) {
            Log::warning("CompanyEnrichmentService: failed to enrich lead {$lead->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enrich up to $limit unenriched companies for a workspace.
     */
    public function enrichBatch(Workspace $workspace, int $limit = 10): array
    {
        $companies = Company::where('workspace_id', $workspace->id)
            ->whereIn('enrichment_status', ['pending', 'failed'])
            ->whereNotNull('domain')
            ->limit($limit)
            ->get();

        $enriched = 0;
        $failed   = 0;

        foreach ($companies as $company) {
            if ($this->enrichFromDomain($company, $workspace)) {
                $enriched++;
            } else {
                $failed++;
            }
        }

        return ['enriched' => $enriched, 'failed' => $failed];
    }
}
