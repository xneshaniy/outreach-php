<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadImport;
use App\Models\LeadImportError;
use App\Models\LeadList;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Statement;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;

class ImportService
{
    /**
     * Comprehensive alias and synonym dictionary for CRM fields.
     */
    protected array $fieldAliases = [
        'email' => [
            'email', 'email_address', 'e_mail', 'mail', 'contact_email', 'work_email',
            'business_email', 'corporate_email', 'primary_email', 'electronic_mail',
        ],
        'first_name' => [
            'first_name', 'firstname', 'fname', 'first', 'given_name', 'forename',
            'contact_first_name', 'lead_first_name',
        ],
        'last_name' => [
            'last_name', 'lastname', 'lname', 'last', 'surname', 'family_name',
            'contact_last_name', 'lead_last_name',
        ],
        'phone' => [
            'phone', 'phone_number', 'telephone', 'tel', 'phone_no', 'office_phone',
            'work_phone', 'business_phone', 'contact_number', 'contact_phone',
        ],
        'mobile' => [
            'mobile', 'mobile_phone', 'cell', 'cellphone', 'cell_number', 'mobile_number',
        ],
        'job_title' => [
            'job_title', 'title', 'position', 'role', 'designation', 'job',
            'occupation', 'professional_title', 'headline',
        ],
        'company' => [
            'company', 'company_name', 'organization', 'organisation', 'org',
            'business', 'business_name', 'firm', 'employer', 'account', 'account_name',
            'agency', 'corp', 'corporation',
        ],
        'company_domain' => [
            'domain', 'company_domain', 'website_domain', 'email_domain', 'corp_domain',
        ],
        'website' => [
            'website', 'url', 'site', 'web', 'homepage', 'company_website',
            'web_address', 'link',
        ],
        'industry' => [
            'industry', 'sector', 'vertical', 'niche', 'business_category', 'category',
        ],
        'country' => [
            'country', 'nation', 'country_name', 'location_country', 'geo_country',
        ],
        'city' => [
            'city', 'town', 'municipality', 'location_city', 'metro',
        ],
        'state' => [
            'state', 'province', 'region', 'state_province', 'location_state',
        ],
        'address' => [
            'address', 'street', 'street_address', 'location', 'full_address',
        ],
        'linkedin_url' => [
            'linkedin', 'linkedin_url', 'linkedin_profile', 'linkedin_link',
            'social_linkedin', 'profile_url',
        ],
        'source' => [
            'source', 'lead_source', 'origin', 'acquisition_source', 'campaign',
        ],
        'tags' => [
            'tags', 'tag', 'labels', 'categories', 'groups', 'segment',
        ],
        'notes' => [
            'notes', 'note', 'comments', 'comment', 'description', 'details', 'remarks',
        ],
    ];

    /**
     * Converts an uploaded Excel file (.xlsx or .xls) to a CSV in local storage.
     */
    public function convertExcelToCsv(string $inputPath, ?string $destinationPath = null): string
    {
        $spreadsheet = IOFactory::load($inputPath);

        if (!$destinationPath) {
            $destinationPath = 'imports/' . Str::random(40) . '.csv';
        }

        $fullDestPath = Storage::disk('local')->path($destinationPath);
        $destDir = dirname($fullDestPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $writer = new CsvWriter($spreadsheet);
        $writer->setSheetIndex($spreadsheet->getActiveSheetIndex());
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $writer->setLineEnding("\r\n");
        $writer->save($fullDestPath);

        // Sanitize the resulting CSV
        $this->sanitizeCsvFile($fullDestPath);

        return $destinationPath;
    }

    public function processImport(LeadImport $import, array $options = []): void
    {
        @set_time_limit(300);
        $import->markAsProcessing();

        try {
            $path = Storage::disk('local')->path($import->stored_path);
            $this->sanitizeCsvFile($path);
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(0);

            $records = Statement::create()->process($csv);
            $totalRows = iterator_count($records);

            $import->update(['total_rows' => $totalRows]);

            // Reset reader
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(0);
            $records = Statement::create()->process($csv);

            $leadList = $import->lead_list_id ? LeadList::find($import->lead_list_id) : null;
            $workspace = $import->workspace;
            $creator = $import->creator ?? $workspace?->users()->first() ?? User::first();
            $mapping = $import->column_mapping ?? $this->getDefaultMapping();
            unset($mapping['_options']);

            $batchSize = 100;
            $batch = [];

            $skipDuplicates = ($options['duplicate_handling'] ?? 'skip') !== 'update';
            $updateExisting = ($options['duplicate_handling'] ?? 'skip') === 'update';

            foreach ($records as $index => $record) {
                $rowNumber = $index + 2; // +2 because header is row 1

                try {
                    $leadData = $this->mapRow($record, $mapping);
                    $leadData['source'] = $leadData['source'] ?? 'import';

                    $batch[] = $leadData;

                    if (count($batch) >= $batchSize) {
                        $this->processBatch($batch, $workspace, $creator, $leadList, $import, $rowNumber - count($batch) + 1, $skipDuplicates, $updateExisting);
                        $batch = [];
                    }
                } catch (\Throwable $e) {
                    $this->recordError($import, $rowNumber, $record, $e->getMessage());
                    $import->incrementProcessed(0, 1);
                }
            }

            // Process remaining batch
            if (!empty($batch)) {
                $this->processBatch($batch, $workspace, $creator, $leadList, $import, $totalRows - count($batch) + 1, $skipDuplicates, $updateExisting);
            }

            $import->markAsCompleted();
        } catch (\Throwable $e) {
            $import->markAsFailed($e->getMessage());
        }
    }

    private function processBatch(
        array $batch,
        Workspace $workspace,
        ?User $creator,
        ?LeadList $list,
        LeadImport $import,
        int $startRow,
        bool $skipDuplicates = true,
        bool $updateExisting = false
    ): void {
        $results = app(LeadService::class)->bulkCreateLeads($batch, $workspace, $creator, [
            'skip_duplicates' => $skipDuplicates,
            'update_existing' => $updateExisting,
        ]);

        $import->incrementProcessed(
            $results['created'],
            $results['failed'] ?? 0,
            $results['duplicates']
        );

        foreach ($results['errors'] as $error) {
            $this->recordError($import, $startRow + $error['row'] - 1, $batch[$error['row'] - 1] ?? [], $error['error']);
        }

        // Attach newly imported leads to the target LeadList if provided
        if ($list && $creator) {
            try {
                $emailsInBatch = array_filter(array_map(fn($item) => strtolower(trim($item['email'] ?? '')), $batch));
                if (!empty($emailsInBatch)) {
                    $leadsInBatch = Lead::where('workspace_id', $workspace->id)
                        ->whereIn('normalized_email', $emailsInBatch)
                        ->get();
                    foreach ($leadsInBatch as $bLead) {
                        try {
                            $list->leads()->syncWithoutDetaching([
                                $bLead->id => ['added_by' => $creator->id],
                            ]);
                        } catch (\Throwable $e) {
                            // Non-fatal
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }

        // Run company matching & enrichment on newly created leads
        if ($results['created'] > 0 && $creator) {
            try {
                $matchingService = app(\App\Services\UnifiedMatchingService::class);
                $query = Lead::where('workspace_id', $workspace->id);
                if (\Illuminate\Support\Facades\Schema::hasColumn('leads', 'company_match_status')) {
                    $query->where(function ($q) {
                        $q->whereNull('company_match_status')
                          ->orWhere('company_match_status', 'unlinked');
                    });
                }
                $recentLeads = $query->latest()
                    ->limit($results['created'])
                    ->get();

                foreach ($recentLeads as $lead) {
                    try {
                        $matchingService->enrichLeadWithCompany($lead, $creator);
                    } catch (\Throwable $e) {
                        // Non-fatal: continue
                    }
                }
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }
    }

    public function mapRow(array $record, array $mapping): array
    {
        $data = [];
        foreach ($mapping as $field => $column) {
            if ($field === '_options' || !is_string($column) || trim($column) === '') {
                continue;
            }
            if (isset($record[$column])) {
                $value = trim((string)$record[$column]);
                if ($value !== '') {
                    $data[$field] = $value;
                }
            }
        }
        return $data;
    }

    public function getDefaultMapping(): array
    {
        return [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'email' => 'email',
            'phone' => 'phone',
            'mobile' => 'mobile',
            'job_title' => 'job_title',
            'company' => 'company',
            'company_domain' => 'company_domain',
            'website' => 'website',
            'industry' => 'industry',
            'country' => 'country',
            'city' => 'city',
            'state' => 'state',
            'address' => 'address',
            'linkedin_url' => 'linkedin_url',
            'source' => 'source',
            'tags' => 'tags',
            'notes' => 'notes',
        ];
    }

    public function detectColumns(string $path): array
    {
        $this->sanitizeCsvFile($path);
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();

        return is_array($header) ? array_values($header) : [];
    }

    public function previewImport(string $path, int $rows = 5): array
    {
        $this->sanitizeCsvFile($path);
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        $records = Statement::create()->offset(0)->limit($rows)->process($csv);

        return array_values(iterator_to_array($records));
    }

    /**
     * Advanced auto-mapping matching algorithm.
     * Uses exact match, normalized alphanumeric match, alias/synonym dictionary, and similarity scoring.
     */
    public function suggestMappingWithConfidence(array $csvColumns): array
    {
        $mapping = [];
        $confidences = [];
        $usedColumns = [];

        foreach ($this->fieldAliases as $field => $aliases) {
            $bestMatch = null;
            $bestScore = 0;
            $matchType = 'none';

            foreach ($csvColumns as $column) {
                if (in_array($column, $usedColumns, true)) {
                    continue;
                }

                $cleanCol = strtolower(trim((string)$column));
                $normCol = preg_replace('/[^a-z0-9]/', '', $cleanCol);

                // 1. Exact field name match
                if ($cleanCol === strtolower($field) || $normCol === preg_replace('/[^a-z0-9]/', '', strtolower($field))) {
                    $bestMatch = $column;
                    $bestScore = 100;
                    $matchType = 'exact';
                    break;
                }

                // 2. Exact alias match
                foreach ($aliases as $alias) {
                    $cleanAlias = strtolower($alias);
                    $normAlias = preg_replace('/[^a-z0-9]/', '', $cleanAlias);

                    if ($cleanCol === $cleanAlias || $normCol === $normAlias) {
                        if ($bestScore < 95) {
                            $bestMatch = $column;
                            $bestScore = 95;
                            $matchType = 'alias';
                        }
                    } elseif (str_contains($cleanCol, $cleanAlias)) {
                        if ($bestScore < 85) {
                            $bestMatch = $column;
                            $bestScore = 85;
                            $matchType = 'contains';
                        }
                    } else {
                        // Levenshtein fuzzy similarity
                        similar_text($cleanCol, $cleanAlias, $percent);
                        if ($percent > 80 && $percent > $bestScore) {
                            $bestMatch = $column;
                            $bestScore = (int)$percent;
                            $matchType = 'fuzzy';
                        }
                    }
                }
            }

            if ($bestMatch && $bestScore >= 75) {
                $mapping[$field] = $bestMatch;
                $confidences[$field] = [
                    'column' => $bestMatch,
                    'confidence' => $bestScore,
                    'match_type' => $matchType,
                ];
                $usedColumns[] = $bestMatch;
            } else {
                $mapping[$field] = '';
                $confidences[$field] = null;
            }
        }

        return [
            'mapping' => $mapping,
            'details' => $confidences,
        ];
    }

    public function suggestMapping(array $csvColumns): array
    {
        $result = $this->suggestMappingWithConfidence($csvColumns);
        return $result['mapping'];
    }

    /**
     * Generates a live mapped preview for the first 10-20 rows with validations:
     * - Email validity
     * - Duplicate check in workspace
     * - Company matching & verified protection status
     * - Statistics
     */
    public function generateLivePreview(string $path, array $mapping, Workspace $workspace, int $limit = 20): array
    {
        $this->sanitizeCsvFile($path);
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        // Read total count
        $allRecords = Statement::create()->process($csv);
        $totalRows = iterator_count($allRecords);

        // Read sample records
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        $records = Statement::create()->offset(0)->limit($limit)->process($csv);

        $mappedRows = [];
        $sampleValid = 0;
        $sampleDuplicates = 0;
        $sampleInvalidEmails = 0;
        $sampleCompanyMatches = 0;
        $sampleVerifiedCompanies = 0;

        foreach ($records as $index => $record) {
            $rowNumber = $index + 1;
            $leadData = $this->mapRow($record, $mapping);

            $email = isset($leadData['email']) ? strtolower(trim($leadData['email'])) : '';
            $companyName = $leadData['company'] ?? '';
            $companyDomain = $leadData['company_domain'] ?? '';

            // 1. Email validation
            $hasEmail = !empty($email);
            $isValidEmail = $hasEmail && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

            // 2. Duplicate lead check
            $isDuplicate = false;
            $existingLeadId = null;
            if ($isValidEmail) {
                $existingLead = Lead::where('workspace_id', $workspace->id)
                    ->where('normalized_email', $email)
                    ->select(['id', 'first_name', 'last_name', 'email'])
                    ->first();

                if ($existingLead) {
                    $isDuplicate = true;
                    $existingLeadId = $existingLead->id;
                }
            }

            // 3. Company matching & verified data check
            $matchedCompany = null;
            $isVerifiedCompany = false;

            if (!empty($companyDomain) || !empty($companyName)) {
                $query = Company::where('workspace_id', $workspace->id);
                if (!empty($companyDomain)) {
                    $cleanDomain = strtolower(preg_replace('#^https?://#i', '', rtrim($companyDomain, '/')));
                    $query->where(function ($q) use ($cleanDomain, $companyName) {
                        $q->where('domain', $cleanDomain);
                        if (!empty($companyName)) {
                            $q->orWhere('name', $companyName);
                        }
                    });
                } else {
                    $query->where('name', $companyName);
                }

                $cols = ['id', 'name', 'domain'];
                if (\Illuminate\Support\Facades\Schema::hasColumn('companies', 'verification_status')) {
                    $cols[] = 'verification_status';
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('companies', 'enrichment_status')) {
                    $cols[] = 'enrichment_status';
                }
                $foundCompany = $query->first($cols);
                if ($foundCompany) {
                    $isVerifiedCompany = (
                        (($foundCompany->verification_status ?? null) === 'verified') ||
                        (($foundCompany->enrichment_status ?? null) === 'verified')
                    );
                    $matchedCompany = [
                        'id' => $foundCompany->id,
                        'name' => $foundCompany->name,
                        'domain' => $foundCompany->domain,
                        'is_verified' => $isVerifiedCompany,
                    ];
                    $sampleCompanyMatches++;
                    if ($isVerifiedCompany) {
                        $sampleVerifiedCompanies++;
                    }
                }
            }

            // Status determination
            $status = 'valid';
            $statusLabel = 'Valid Lead';
            if (!$hasEmail) {
                $status = 'missing_email';
                $statusLabel = 'Missing Email';
                $sampleInvalidEmails++;
            } elseif (!$isValidEmail) {
                $status = 'invalid_email';
                $statusLabel = 'Invalid Email';
                $sampleInvalidEmails++;
            } elseif ($isDuplicate) {
                $status = 'duplicate';
                $statusLabel = 'Duplicate Lead';
                $sampleDuplicates++;
            } else {
                $sampleValid++;
            }

            $mappedRows[] = [
                'row_number' => $rowNumber,
                'raw_record' => $record,
                'data' => $leadData,
                'status' => $status,
                'status_label' => $statusLabel,
                'is_valid' => $isValidEmail && !$isDuplicate,
                'is_duplicate' => $isDuplicate,
                'existing_lead_id' => $existingLeadId,
                'matched_company' => $matchedCompany,
                'is_verified_company' => $isVerifiedCompany,
            ];
        }

        return [
            'total_rows' => $totalRows,
            'preview_rows' => $mappedRows,
            'stats' => [
                'total_rows' => $totalRows,
                'sample_size' => count($mappedRows),
                'sample_valid' => $sampleValid,
                'sample_duplicates' => $sampleDuplicates,
                'sample_invalid_emails' => $sampleInvalidEmails,
                'sample_company_matches' => $sampleCompanyMatches,
                'sample_verified_companies' => $sampleVerifiedCompanies,
            ],
        ];
    }

    /**
     * Sanitize and deduplicate CSV header row to prevent League\Csv SyntaxError:
     * "The header record contains duplicate column names."
     */
    public function sanitizeCsvFile(string $path): void
    {
        if (!file_exists($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (empty($lines)) {
            return;
        }

        $firstLine = $lines[0];
        // Strip UTF-8 BOM if present
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);

        // Detect delimiter: comma, semicolon, tab, pipe
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        $headers = str_getcsv($firstLine, $bestDelimiter);
        if (empty($headers)) {
            return;
        }

        $seen = [];
        $sanitized = [];
        $changed = false;

        foreach ($headers as $idx => $header) {
            $clean = trim((string)$header);
            $clean = trim($clean, "\"' \t\n\r\0\x0B");

            if ($clean === '') {
                $clean = 'Column_' . ($idx + 1);
                $changed = true;
            }

            if (isset($seen[$clean])) {
                $seen[$clean]++;
                $clean = $clean . '_' . $seen[$clean];
                $changed = true;
            } else {
                $seen[$clean] = 1;
            }

            $sanitized[] = $clean;
        }

        if ($changed || $bestDelimiter !== ',') {
            $out = fopen('php://memory', 'r+');
            fputcsv($out, $sanitized);
            rewind($out);
            $newFirstLine = rtrim(stream_get_contents($out), "\r\n");
            fclose($out);

            $lines[0] = $newFirstLine;
            file_put_contents($path, implode("\n", $lines));
        }
    }

    private function recordError(LeadImport $import, int $rowNumber, array $rowData, string $error): void
    {
        LeadImportError::create([
            'lead_import_id' => $import->id,
            'row_number' => $rowNumber,
            'row_data' => $rowData,
            'error_message' => $error,
        ]);
    }
}
