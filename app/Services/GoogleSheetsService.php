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
        $url = trim($url);

        // 1. Published to the web format: /spreadsheets/d/e/{PUB_ID}/...
        if (preg_match('~/spreadsheets/d/e/([a-zA-Z0-9_-]+)~', $url, $matches)) {
            $pubId = $matches[1];
            $gid = '0';
            if (preg_match('~[?&#]gid=([0-9]+)~', $url, $gidMatches)) {
                $gid = $gidMatches[1];
            }
            return [
                'document_id' => $pubId,
                'gid' => $gid,
                'export_url' => "https://docs.google.com/spreadsheets/d/e/{$pubId}/pub?output=csv&gid={$gid}",
            ];
        }

        // 2. Standard format: /spreadsheets/d/{SPREADSHEET_ID}/...
        $docId = null;
        if (preg_match('~/spreadsheets/d/([a-zA-Z0-9_-]+)~', $url, $matches)) {
            $docId = $matches[1];
        }

        $gid = '0';
        if (preg_match('~[?&#]gid=([0-9]+)~', $url, $gidMatches)) {
            $gid = $gidMatches[1];
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

        // Download CSV with modern user-agent, redirect following, and ssl resilience
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Accept' => 'text/csv,text/plain,*/*',
        ])->timeout(25)->withoutVerifying()->get($exportUrl);

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

        // Strip UTF-8 BOM if present so first column header matches cleanly
        $bom = pack('H*', 'EFBBBF');
        $csvContent = preg_replace("/^$bom/", '', $csvContent);

        $filename = 'imports/gsheet_' . Str::random(16) . '.csv';
        Storage::disk('local')->put($filename, $csvContent);

        return [
            'stored_path' => $filename,
            'full_path' => Storage::disk('local')->path($filename),
            'document_id' => $parsed['document_id'],
            'gid' => $parsed['gid'],
            'original_filename' => 'Google_Sheet_' . substr($parsed['document_id'], 0, 8) . '.csv',
        ];
    }
}
