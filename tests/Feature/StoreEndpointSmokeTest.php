<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\LeadImport;
use App\Models\Pipeline;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every "store"/"update" endpoint must answer with a validation redirect (302)
 * or a 403, never a 500, when it receives an empty payload.
 */
class StoreEndpointSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_endpoints_return_validation_errors_not_server_errors(): void
    {
        $this->post('/register', [
            'name' => 'Smoke User',
            'email' => 'stores@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $workspace = Workspace::where('slug', 'smoke-user')->first() ?? Workspace::first();
        $this->assertNotNull($workspace);

        $pipeline = Pipeline::factory()->create(['workspace_id' => $workspace->id]);
        $deal = Deal::factory()->create([
            'workspace_id' => $workspace->id,
            'pipeline_id' => $pipeline->id,
        ]);

        $requests = [
            ['post', '/leads'],
            ['post', '/companies'],
            ['post', '/email-accounts'],
            ['post', '/pipeline'],
            ['post', '/tasks'],
            ['post', '/campaigns'],
            ['post', '/settings/users/invite'],
            ['post', "/pipeline/{$pipeline->id}/deals"],
            ['put', "/pipeline/deals/{$deal->id}"],
            ['put', '/settings/profile'],
            ['put', "/settings/users/{$workspace->users()->first()->id}/role"],
        ];

        $failures = [];

        foreach ($requests as [$method, $url]) {
            $res = $this->{$method}($url, []);

            if ($res->getStatusCode() >= 500) {
                $failures[$method.' '.$url] = $res->getStatusCode();
            }
        }

        $this->assertSame([], $failures, 'Endpoints returned server errors: '.json_encode($failures));
    }

    public function test_lead_import_can_only_be_viewed_by_its_own_workspace(): void
    {
        $this->post('/register', [
            'name' => 'Import Owner',
            'email' => 'imports@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $workspace = Workspace::where('slug', 'import-owner')->first() ?? Workspace::first();
        $otherWorkspace = Workspace::factory()->create();

        $ownImport = LeadImport::create([
            'workspace_id' => $workspace->id,
            'created_by' => auth()->id(),
            'original_filename' => 'own.csv',
            'stored_path' => 'imports/own.csv',
            'status' => 'pending',
        ]);

        $foreignImport = LeadImport::create([
            'workspace_id' => $otherWorkspace->id,
            'created_by' => auth()->id(),
            'original_filename' => 'foreign.csv',
            'stored_path' => 'imports/foreign.csv',
            'status' => 'pending',
        ]);

        $this->get("/leads/imports/{$ownImport->id}")->assertOk();
        $this->get("/leads/imports/{$foreignImport->id}")->assertForbidden();
    }
}
