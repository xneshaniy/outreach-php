<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UnifiedMatchingService
{
    // Free email providers — classified as personal, not company
    private const FREEMAIL_DOMAINS = [
        'gmail.com', 'googlemail.com', 'outlook.com', 'hotmail.com', 'hotmail.co.uk',
        'yahoo.com', 'yahoo.co.uk', 'yahoo.fr', 'yahoo.de', 'yahoo.es',
        'icloud.com', 'me.com', 'mac.com', 'protonmail.com', 'proton.me',
        'yandex.com', 'yandex.ru', 'aol.com', 'live.com', 'live.co.uk',
        'msn.com', 'fastmail.com', 'fastmail.fm', 'zoho.com', 'gmx.com',
        'gmx.de', 'web.de', 'laposte.net', 'free.fr', 'wanadoo.fr',
        'sfr.fr', 'orange.fr', 'bbox.fr', 'bouyguestelecom.fr',
        'mail.com', 'inbox.com', 'rocketmail.com', 'att.net', 'sbcglobal.net',
    ];

    // Local-parts that indicate a company/role email (not personal)
    private const COMPANY_PREFIXES = [
        'info', 'sales', 'contact', 'support', 'hello', 'admin', 'office',
        'billing', 'noreply', 'no-reply', 'team', 'newsletter', 'enquiries',
        'enquiry', 'help', 'service', 'services', 'orders', 'reception',
        'accounts', 'accounting', 'finance', 'hr', 'jobs', 'careers', 'pr',
        'press', 'media', 'marketing', 'mail', 'post', 'general', 'business',
        'hire', 'recruiting', 'recruitment', 'solutions', 'webmaster',
    ];

    // Suffixes to strip when normalising company name for matching
    private const COMPANY_SUFFIXES = [
        'ltd', 'limited', 'llc', 'inc', 'incorporated', 'corp', 'corporation',
        'gmbh', 'sarl', 'sas', 'srl', 'bv', 'nv', 'ag', 'sa', 'co', 'co.',
        'plc', 'pty', 'pvt', 'pty ltd', 'pte ltd',
    ];

    // -------------------------------------------------------------------------
    // COMPANY MATCHING
    // -------------------------------------------------------------------------

    /**
     * Find an existing company or create one for the given lead data.
     * Returns null when there is not enough data to reliably identify/create.
     */
    public function matchOrCreateCompany(Workspace $workspace, array $leadData, User $creator): ?Company
    {
        $name   = trim($leadData['company'] ?? $leadData['company_name'] ?? '');
        $email  = trim($leadData['email'] ?? '');
        $web    = trim($leadData['website'] ?? $leadData['company_website'] ?? '');
        $domain = $leadData['company_domain'] ?? $this->extractDomain($web ?: $email);

        // Try matching in this order: domain > exact name > fuzzy name
        $company = $this->matchByDomain($workspace, $domain)
            ?? $this->matchByName($workspace, $name)
            ?? $this->fuzzyMatchByName($workspace, $name);

        if ($company) {
            // Enrich company with any new non-null data from lead
            $this->mergeIntoCompany($company, $leadData);
            return $company;
        }

        // Not found — create if we have at least a name
        if (empty($name)) {
            return null;
        }

        return $this->createCompany($workspace, $creator, $leadData);
    }

    private function matchByDomain(Workspace $workspace, string $domain): ?Company
    {
        if (empty($domain) || in_array($domain, self::FREEMAIL_DOMAINS, true)) {
            return null;
        }
        return Company::where('workspace_id', $workspace->id)
            ->where('domain', $domain)
            ->first();
    }

    private function matchByName(Workspace $workspace, string $name): ?Company
    {
        if (empty($name)) {
            return null;
        }
        $normalized = $this->normalizeCompanyName($name);
        return Company::where('workspace_id', $workspace->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
            ->first();
    }

    private function fuzzyMatchByName(Workspace $workspace, string $name): ?Company
    {
        if (empty($name)) {
            return null;
        }
        $normalized = $this->normalizeCompanyName($name);

        $companies = Company::where('workspace_id', $workspace->id)->get();
        foreach ($companies as $candidate) {
            $candidateNorm = $this->normalizeCompanyName($candidate->name ?? '');
            similar_text($normalized, $candidateNorm, $percent);
            if ($percent >= 85) {
                return $candidate;
            }
        }
        return null;
    }

    private function createCompany(Workspace $workspace, User $creator, array $leadData): Company
    {
        $name   = trim($leadData['company'] ?? $leadData['company_name'] ?? 'Unknown');
        $domain = $leadData['company_domain']
            ?? $this->extractDomain($leadData['website'] ?? $leadData['email'] ?? '');

        $company = Company::create([
            'workspace_id'      => $workspace->id,
            'created_by'        => $creator->id,
            'name'              => $name,
            'domain'            => $domain ?: null,
            'website'           => $leadData['website'] ?? null,
            'industry'          => $leadData['industry'] ?? null,
            'country'           => $leadData['country'] ?? null,
            'city'              => $leadData['city'] ?? null,
            'phone'             => $leadData['phone'] ?? null,
            'enrichment_status' => 'pending',
        ]);

        return $company;
    }

    /**
     * Merge non-null lead data into an existing company (never overwrite verified data).
     */
    private function mergeIntoCompany(Company $company, array $leadData): void
    {
        $skip = ($company->verification_status === 'verified' || $company->enrichment_status === 'verified');
        $dirty = false;

        $fields = [
            'domain'  => $leadData['company_domain'] ?? $this->extractDomain($leadData['website'] ?? $leadData['email'] ?? ''),
            'website' => $leadData['website'] ?? null,
            'industry'=> $leadData['industry'] ?? null,
            'country' => $leadData['country'] ?? null,
            'city'    => $leadData['city'] ?? null,
            'phone'   => $leadData['phone'] ?? null,
        ];

        foreach ($fields as $field => $value) {
            if (!empty($value) && empty($company->$field) && !$skip) {
                $company->$field = $value;
                $dirty = true;
            }
        }

        if ($dirty) {
            $company->save();
        }
    }

    // -------------------------------------------------------------------------
    // EMAIL CLASSIFICATION
    // -------------------------------------------------------------------------

    /**
     * Classify an email address.
     * Returns: 'company', 'personal', or 'unknown'
     */
    public function classifyEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'unknown';
        }

        [$local, $domain] = explode('@', $email, 2);

        // Freemail → definitely personal
        if (in_array($domain, self::FREEMAIL_DOMAINS, true)) {
            return 'personal';
        }

        // Known company prefix → company email
        if (in_array($local, self::COMPANY_PREFIXES, true)) {
            return 'company';
        }

        // If the local part looks like a person (name.name) on a business domain → personal
        if (preg_match('/^[a-z]+[._\-][a-z]+$/', $local)) {
            return 'personal';
        }

        // Business domain but no other signals → assume company
        return 'company';
    }

    // -------------------------------------------------------------------------
    // LEAD + COMPANY ENRICHMENT BRIDGE
    // -------------------------------------------------------------------------

    /**
     * Classify the lead's email and link to a company.
     */
    public function enrichLeadWithCompany(Lead $lead, User $creator): void
    {
        $workspace = $lead->workspace;

        // Classify the lead's own email
        $emailType = $this->classifyEmail($lead->email ?? '');

        if ($emailType === 'company' && empty($lead->company_id)) {
            $domain = $this->extractDomain($lead->email ?? '');
            $company = $this->matchByDomain($workspace, $domain);
            if ($company) {
                $lead->company_id = $company->id;
                $lead->company_match_status = 'linked';
                $lead->save();
                return;
            }
        }

        // Try matching by company name in lead data
        $data = [
            'company'      => $lead->company_name,
            'email'        => $lead->email,
            'website'      => $lead->website,
            'industry'     => $lead->industry,
            'country'      => $lead->country,
            'city'         => $lead->city,
            'phone'        => $lead->phone,
        ];

        if (!empty($lead->company_name)) {
            $company = $this->matchOrCreateCompany($workspace, $data, $creator);
            if ($company) {
                $lead->company_id = $company->id;
                $lead->company_match_status = 'linked';
            } else {
                $lead->company_match_status = 'flagged_for_review';
            }
        } else {
            $lead->company_match_status = $lead->company_id ? 'linked' : 'unlinked';
        }

        $lead->save();
    }

    /**
     * Find an existing lead by email or phone.
     */
    public function matchExistingLead(Workspace $workspace, string $email, ?string $phone = null): ?Lead
    {
        $normalized = Str::lower(trim($email));
        $lead = Lead::where('workspace_id', $workspace->id)
            ->where('normalized_email', $normalized)
            ->first();

        if (!$lead && $phone) {
            $lead = Lead::where('workspace_id', $workspace->id)
                ->where(function ($q) use ($phone) {
                    $q->where('phone', $phone)->orWhere('mobile', $phone);
                })->first();
        }

        return $lead;
    }

    /**
     * Process a batch of raw records from any source (CSV, Sheets, etc.)
     * Returns stats array: imported, updated, matched_companies, created_companies, errors.
     */
    public function processNewData(Workspace $workspace, array $records, string $source, User $creator): array
    {
        $stats = [
            'imported'          => 0,
            'updated'           => 0,
            'matched_companies' => 0,
            'created_companies' => 0,
            'errors'            => [],
        ];

        $leadService = app(LeadService::class);

        foreach ($records as $index => $record) {
            try {
                $email = trim($record['email'] ?? '');
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                // Check for existing lead
                $existing = $this->matchExistingLead($workspace, $email, $record['phone'] ?? null);

                if ($existing) {
                    // Update if enriching
                    $stats['updated']++;
                } else {
                    // Create via LeadService (which also handles company)
                    $record['source'] = $record['source'] ?? $source;
                    $leadService->createLead($record, $workspace, $creator, false);
                    $stats['imported']++;
                }

                // Company matching
                $companyBefore = $existing ? $existing->company_id : null;
                $matchedCompany = $this->matchOrCreateCompany($workspace, $record, $creator);

                if ($matchedCompany) {
                    if ($matchedCompany->wasRecentlyCreated) {
                        $stats['created_companies']++;
                    } else {
                        $stats['matched_companies']++;
                    }
                }
            } catch (\Exception $e) {
                $stats['errors'][] = ['row' => $index + 1, 'error' => $e->getMessage()];
            }
        }

        return $stats;
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    /**
     * Extract a normalised domain from a URL or email address.
     */
    public function extractDomain(string $urlOrEmail): string
    {
        $str = trim($urlOrEmail);
        if (empty($str)) {
            return '';
        }

        // Email
        if (str_contains($str, '@')) {
            [, $domain] = explode('@', $str, 2);
            return strtolower(trim($domain));
        }

        // URL
        if (!preg_match('~^https?://~i', $str)) {
            $str = 'https://' . $str;
        }

        $host = parse_url($str, PHP_URL_HOST) ?? '';
        $host = preg_replace('/^www\./i', '', $host);
        return strtolower(trim($host));
    }

    /**
     * Normalize company name for comparison:
     * lowercase, strip legal suffixes, collapse whitespace.
     */
    public function normalizeCompanyName(string $name): string
    {
        $name = strtolower(trim($name));
        // Build regex from suffixes
        $pattern = '/[\s,]+(' . implode('|', array_map('preg_quote', self::COMPANY_SUFFIXES)) . ')\.?\s*$/u';
        $name = preg_replace($pattern, '', $name);
        return preg_replace('/\s+/', ' ', trim($name));
    }
}
