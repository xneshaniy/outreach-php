<?php

namespace App\Services;

use App\Models\GoogleIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetsApiService
{
    private const SHEETS_BASE = 'https://sheets.googleapis.com/v4/spreadsheets';
    private const DRIVE_BASE  = 'https://www.googleapis.com/drive/v3';

    public function __construct(
        protected GoogleOAuthService $oauthService
    ) {}

    /**
     * List Google Spreadsheets the user has access to.
     */
    public function listSpreadsheets(GoogleIntegration $integration): array
    {
        $token = $this->oauthService->getValidToken($integration);
        if (!$token) {
            throw new \Exception('Could not obtain a valid Google access token.');
        }

        $response = Http::withToken($token)
            ->get(self::DRIVE_BASE . '/files', [
                'q'        => "mimeType='application/vnd.google-apps.spreadsheet' and trashed=false",
                'fields'   => 'files(id,name,modifiedTime)',
                'pageSize' => 50,
                'orderBy'  => 'modifiedTime desc',
            ]);

        if (!$response->successful()) {
            Log::error('GoogleSheetsApiService: listSpreadsheets failed', ['status' => $response->status()]);
            throw new \Exception('Failed to list Google Sheets: ' . $response->status());
        }

        return $response->json('files', []);
    }

    /**
     * List worksheets (tabs) inside a spreadsheet.
     */
    public function listWorksheets(GoogleIntegration $integration, string $spreadsheetId): array
    {
        $token = $this->oauthService->getValidToken($integration);
        if (!$token) {
            throw new \Exception('Could not obtain a valid Google access token.');
        }

        $response = Http::withToken($token)
            ->get(self::SHEETS_BASE . "/{$spreadsheetId}", [
                'fields' => 'sheets(properties(sheetId,title))',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to list worksheets: ' . $response->status());
        }

        return array_map(fn($s) => $s['properties'], $response->json('sheets', []));
    }

    /**
     * Read all rows from a worksheet. Returns [headers, rows].
     */
    public function readSheet(GoogleIntegration $integration, string $spreadsheetId, string $range = 'A1:ZZ10000'): array
    {
        $token = $this->oauthService->getValidToken($integration);
        if (!$token) {
            throw new \Exception('Could not obtain a valid Google access token.');
        }

        $response = Http::withToken($token)
            ->get(self::SHEETS_BASE . "/{$spreadsheetId}/values/{$range}", [
                'majorDimension' => 'ROWS',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to read sheet: ' . $response->status());
        }

        $values = $response->json('values', []);
        if (empty($values)) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_shift($values);
        // Pad short rows so all have same number of columns as header
        $rows = array_map(function ($row) use ($headers) {
            while (count($row) < count($headers)) {
                $row[] = '';
            }
            return array_combine($headers, array_slice($row, 0, count($headers)));
        }, $values);

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Map sheet rows to CRM lead fields using provided column mapping.
     */
    public function mapRows(array $rows, array $columnMapping): array
    {
        return array_map(function ($row) use ($columnMapping) {
            $mapped = [];
            foreach ($columnMapping as $crmField => $sheetColumn) {
                if (isset($row[$sheetColumn])) {
                    $value = trim($row[$sheetColumn]);
                    if ($value !== '') {
                        $mapped[$crmField] = $value;
                    }
                }
            }
            return $mapped;
        }, $rows);
    }

    /**
     * Preview: return first N rows mapped to CRM fields.
     */
    public function previewSheet(GoogleIntegration $integration, string $spreadsheetId, string $worksheetName, array $columnMapping, int $limit = 5): array
    {
        $range = "'{$worksheetName}'!A1:ZZ" . ($limit + 1);
        $data  = $this->readSheet($integration, $spreadsheetId, $range);

        $mapped = $this->mapRows(array_slice($data['rows'], 0, $limit), $columnMapping);

        return [
            'headers'       => $data['headers'],
            'preview_rows'  => $mapped,
            'total_in_preview' => count($mapped),
        ];
    }
}
