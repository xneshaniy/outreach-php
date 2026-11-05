<?php

namespace App\Services;

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

class ImportService
{
    public function processImport(LeadImport $import): void
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
            $creator = $import->creator;
            $mapping = $import->column_mapping ?? $this->getDefaultMapping();
            $leadService = app(LeadService::class);

            $batchSize = 100;
            $batch = [];

            foreach ($records as $index => $record) {
                $rowNumber = $index + 2; // +2 because header is row 1, 0-indexed

                try {
                    $leadData = $this->mapRow($record, $mapping);
                    $leadData['source'] = $leadData['source'] ?? 'import';

                    $batch[] = $leadData;

                    if (count($batch) >= $batchSize) {
                        $this->processBatch($batch, $workspace, $creator, $leadList, $import, $rowNumber - count($batch) + 1);
                        $batch = [];
                    }
                } catch (\Exception $e) {
                    $this->recordError($import, $rowNumber, $record, $e->getMessage());
                    $import->incrementProcessed(0, 1);
                }
            }

            // Process remaining batch
            if (!empty($batch)) {
                $this->processBatch($batch, $workspace, $creator, $leadList, $import, $totalRows - count($batch) + 1);
            }

            $import->markAsCompleted();
        } catch (\Exception $e) {
            $import->markAsFailed($e->getMessage());
        }
    }

    private function processBatch(array $batch, Workspace $workspace, User $creator, ?LeadList $list, LeadImport $import, int $startRow): void
    {
        $results = app(LeadService::class)->bulkCreateLeads($batch, $workspace, $creator, [
            'skip_duplicates' => true,
            'update_existing' => false,
        ]);

        $import->incrementProcessed(
            $results['created'],
            $results['failed'] ?? 0,
            $results['duplicates']
        );

        foreach ($results['errors'] as $error) {
            $this->recordError($import, $startRow + $error['row'] - 1, $batch[$error['row'] - 1] ?? [], $error['error']);
        }

        // Run company matching on newly created leads (batch from last N created)
        if ($results['created'] > 0) {
            $matchingService = app(\App\Services\UnifiedMatchingService::class);
            $recentLeads = \App\Models\Lead::where('workspace_id', $workspace->id)
                ->whereNull('company_match_status')
                ->orWhere('company_match_status', 'unlinked')
                ->latest()
                ->limit($results['created'])
                ->get();
            foreach ($recentLeads as $lead) {
                try {
                    $matchingService->enrichLeadWithCompany($lead, $creator);
                } catch (\Exception $e) {
                    // Non-fatal: continue even if enrichment fails
                }
            }
        }

        // Add created leads to list if specified
        if ($list && $results['created'] > 0) {
            // Note: In a real implementation, you'd track which leads were created
            // This is simplified - you'd need to return the created lead IDs
        }
    }

    private function mapRow(array $record, array $mapping): array
    {
        $data = [];
        foreach ($mapping as $field => $column) {
            if (isset($record[$column])) {
                $value = trim($record[$column]);
                if ($value !== '') {
                    $data[$field] = $value;
                }
            }
        }
        return $data;
    }

    private function getDefaultMapping(): array
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

        return $header ?? [];
    }

    public function previewImport(string $path, int $rows = 5): array
    {
        $this->sanitizeCsvFile($path);
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        $records = Statement::create()->offset(0)->limit($rows)->process($csv);

        return iterator_to_array($records);
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

    public function suggestMapping(array $csvColumns): array
    {
        $fieldAliases = [
            'first_name' => ['first_name', 'firstname', 'fname', 'given_name'],
            'last_name' => ['last_name', 'lastname', 'lname', 'surname', 'family_name'],
            'email' => ['email', 'email_address', 'e_mail', 'mail'],
            'phone' => ['phone', 'phone_number', 'telephone', 'tel'],
            'mobile' => ['mobile', 'mobile_phone', 'cell', 'cellphone'],
            'job_title' => ['job_title', 'title', 'position', 'role'],
            'company' => ['company', 'company_name', 'organization', 'org'],
            'company_domain' => ['domain', 'company_domain', 'website_domain'],
            'website' => ['website', 'url', 'site'],
            'industry' => ['industry', 'sector'],
            'country' => ['country', 'nation'],
            'city' => ['city', 'town'],
            'state' => ['state', 'province', 'region'],
            'linkedin_url' => ['linkedin', 'linkedin_url', 'linkedin_profile'],
            'source' => ['source', 'lead_source', 'origin'],
            'tags' => ['tags', 'tag', 'labels', 'categories'],
            'notes' => ['notes', 'note', 'comments', 'description'],
        ];

        $mapping = [];
        foreach ($fieldAliases as $field => $aliases) {
            foreach ($aliases as $alias) {
                foreach ($csvColumns as $column) {
                    if (Str::lower($column) === Str::lower($alias) ||
                        Str::contains(Str::lower($column), Str::lower($alias))) {
                        $mapping[$field] = $column;
                        break 2;
                    }
                }
            }
        }

        return $mapping;
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