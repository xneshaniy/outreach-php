<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use App\Models\LeadList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        $this->workspace->users()->attach($this->user->id, ['is_owner' => true, 'is_active' => true]);
        $this->user->setCurrentWorkspace($this->workspace);
        
        $this->actingAs($this->user);
    }

    public function test_user_can_create_lead()
    {
        $response = $this->post('/leads', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1-555-123-4567',
            'job_title' => 'CEO',
            'company' => 'Acme Inc',
            'status' => 'new',
            'source' => 'website',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'email' => 'john.doe@example.com',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    public function test_user_cannot_create_duplicate_lead()
    {
        Lead::factory()->create([
            'workspace_id' => $this->workspace->id,
            'email' => 'john.doe@example.com',
            'normalized_email' => 'john.doe@example.com',
        ]);

        $response = $this->post('/leads', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'john.doe@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_view_lead()
    {
        $lead = Lead::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->get("/leads/{$lead->id}");

        $response->assertOk();
    }

    public function test_user_cannot_view_lead_from_other_workspace()
    {
        $otherWorkspace = Workspace::factory()->create();
        $lead = Lead::factory()->create([
            'workspace_id' => $otherWorkspace->id,
        ]);

        $response = $this->get("/leads/{$lead->id}");

        $response->assertForbidden();
    }

    public function test_user_can_update_lead()
    {
        $lead = Lead::factory()->create([
            'workspace_id' => $this->workspace->id,
            'first_name' => 'John',
        ]);

        $response = $this->put("/leads/{$lead->id}", [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'first_name' => 'Jane',
        ]);
    }

    public function test_user_can_delete_lead()
    {
        $lead = Lead::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->delete("/leads/{$lead->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
    }

    public function test_user_can_bulk_archive_leads()
    {
        $leads = Lead::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->post('/leads/bulk-action', [
            'action' => 'archive',
            'lead_ids' => $leads->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        
        foreach ($leads as $lead) {
            $this->assertSoftDeleted('leads', ['id' => $lead->id]);
        }
    }
}