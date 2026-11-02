<?php

namespace Tests\Feature;

use App\Services\GoogleSheetsService;
use Tests\TestCase;

class GoogleSheetsImportTest extends TestCase
{
    public function test_parses_google_sheets_url_with_hash_gid(): void
    {
        $service = new GoogleSheetsService();
        $url = 'https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit#gid=0';
        $result = $service->parseSheetUrl($url);

        $this->assertSame('1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms', $result['document_id']);
        $this->assertSame('0', $result['gid']);
        $this->assertSame('https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/export?format=csv&gid=0', $result['export_url']);
    }

    public function test_parses_google_sheets_url_with_query_gid(): void
    {
        $service = new GoogleSheetsService();
        $url = 'https://docs.google.com/spreadsheets/d/2CxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit?gid=987654';
        $result = $service->parseSheetUrl($url);

        $this->assertSame('2CxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms', $result['document_id']);
        $this->assertSame('987654', $result['gid']);
    }

    public function test_parses_google_sheets_url_with_dashes_and_underscores(): void
    {
        $service = new GoogleSheetsService();
        $url = 'https://docs.google.com/spreadsheets/d/a-B_1234-xyz_99/edit';
        $result = $service->parseSheetUrl($url);

        $this->assertSame('a-B_1234-xyz_99', $result['document_id']);
        $this->assertSame('0', $result['gid']);
    }

    public function test_invalid_url_throws_exception(): void
    {
        $service = new GoogleSheetsService();
        $this->expectException(\RuntimeException::class);
        $service->parseSheetUrl('https://example.com/not-a-google-sheet');
    }
}
