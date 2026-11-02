<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GoogleSheetsImportTest extends TestCase
{
    use RefreshDatabase;
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

    public function test_import_preview_endpoint_works_with_google_sheet_url_without_csrf_mismatch(): void
    {
        Storage::fake('local');
        $csvData = "email,first_name,last_name,company\nalice@example.com,Alice,Wonderland,Acme\nbob@example.com,Bob,Builder,Construct";

        Http::fake([
            'docs.google.com/*' => Http::response($csvData, 200, ['Content-Type' => 'text/csv']),
        ]);

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user->id, ['is_owner' => true, 'is_active' => true]);
        $user->setCurrentWorkspace($workspace);

        $response = $this->actingAs($user)
            ->postJson('/leads/import/preview', [
                'google_sheet_url' => 'https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit#gid=0',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'columns',
            'preview',
            'suggested_mapping',
            'stored_path',
            'original_filename',
        ]);
        $this->assertContains('email', $response->json('columns'));
    }
}
