<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_leads_can_be_exported_as_a_csv_download(): void
    {
        $this->post('/register', [
            'name' => 'Export User',
            'email' => 'export@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $workspace = Workspace::where('slug', 'export-user')->first() ?? Workspace::first();
        $this->assertNotNull($workspace);

        Lead::factory()->create(['workspace_id' => $workspace->id]);

        $response = $this->get('/leads/export');

        $response->assertOk();
        $response->assertDownload();
    }
}
