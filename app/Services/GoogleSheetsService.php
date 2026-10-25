<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleSheetsService
{
    /**
     * Extracts Document ID and GID from any Google Sheets URL format.
     */
    public function parseSheetUrl(string $url): array
    {
        // Format: https://docs.google.com/spreadsheets/d/{SPREADSHEET_ID}/...
        $docId = null;
        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $url, $matches)) {
            $docId = $matches[1];
        }

        // Format: ...gid=12345
        $gid = '0';
        if (preg_match('#[?&#]gid=([0-9]+)#', $url, $matches)) {
            $gid = $matches[1];
        }

        if (! $docId) {
            throw new RuntimeException('Invalid Google Sheets URL. Please provide a valid Google Spreadsheet link (e.g., https://docs.google.com/spreadsheets/d/.../edit).');
        }

        return [
            'document_id' => $docId,
            'gid' => $gid,
            'export_url' => "https://docs.google.com/spreadsheets/d/{$docId}/export?format=csv&gid={$gid}",
        ];
    }

    /**
     * Downloads the Google Sheet CSV export and stores it in storage/app/imports.
     */
    public function fetchAndStoreCsv(string $url): array
    {
        $parsed = $this->parseSheetUrl($url);
        $exportUrl = $parsed['export_url'];

        // Download CSV with timeout and redirect following
        $response = Http::withHeaders([
            'User-Agent' => 'OutreachCRM-GoogleSheets/1.0',
            'Accept' => 'text/csv,text/plain,*/*',
        ])->timeout(20)->get($exportUrl);

        if ($response->failed() || empty(trim($response->body()))) {
            throw new RuntimeException(
                'Could not fetch Google Sheet. Please verify that the spreadsheet sharing settings are set to "Anyone with the link can view".'
            );
        }

        $csvContent = $response->body();

        // Sanity check: Ensure returned payload is CSV and not an HTML login/error screen
        if (stripos($csvContent, '<!DOCTYPE html') !== false || stripos($csvContent, '<html') !== false) {
            throw new RuntimeException(
                'The Google Sheet returned an HTML login page instead of CSV. Please make sure the sheet is shared as "Anyone with the link can view".'
            );
        }

        $filename = 'imports/gsheet_' . Str::random(16) . '.csv';
        Storage::disk('local')->put($filename, $csvContent);

        return [
            'stored_path' => $filename,
            'full_path' => storage_path('app/' . $filename),
            'document_id' => $parsed['document_id'],
            'gid' => $parsed['gid'],
            'original_filename' => 'Google_Sheet_' . substr($parsed['document_id'], 0, 8) . '.csv',
        ];
    }
}
