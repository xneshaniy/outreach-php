<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_cannot_access_other_workspace_leads()
    {
        // Create two workspaces
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        // Create users for each workspace
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $workspace1->users()->attach($user1->id, ['is_owner' => true, 'is_active' => true]);
        $workspace2->users()->attach($user2->id, ['is_owner' => true, 'is_active' => true]);

        $user1->setCurrentWorkspace($workspace1);
        $user2->setCurrentWorkspace($workspace2);

        // Create leads in each workspace
        $lead1 = Lead::factory()->create(['workspace_id' => $workspace1->id]);
        $lead2 = Lead::factory()->create(['workspace_id' => $workspace2->id]);

        // User 1 should only see their leads
        $this->actingAs($user1);
        $response = $this->get('/leads');
        $response->assertOk();
        
        $leads = $response->viewData('leads') ?? $response->original['leads']['data'] ?? [];
        $leadIds = collect($leads)->pluck('id')->toArray();
        
        $this->assertContains($lead1->id, $leadIds);
        $this->assertNotContains($lead2->id, $leadIds);

        // User 2 should only see their leads
        $this->actingAs($user2);
        $response = $this->get('/leads');
        $response->assertOk();
        
        $leads = $response->viewData('leads') ?? $response->original['leads']['data'] ?? [];
        $leadIds = collect($leads)->pluck('id')->toArray();
        
        $this->assertContains($lead2->id, $leadIds);
        $this->assertNotContains($lead1->id, $leadIds);
    }

    public function test_users_cannot_view_other_workspace_lead_details()
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $workspace1->users()->attach($user1->id, ['is_owner' => true, 'is_active' => true]);
        $workspace2->users()->attach($user2->id, ['is_owner' => true, 'is_active' => true]);

        $user1->setCurrentWorkspace($workspace1);
        $user2->setCurrentWorkspace($workspace2);

        $lead1 = Lead::factory()->create(['workspace_id' => $workspace1->id]);
        $lead2 = Lead::factory()->create(['workspace_id' => $workspace2->id]);

        // User 1 tries to access User 2's lead
        $this->actingAs($user1);
        $response = $this->get("/leads/{$lead2->id}");
        $response->assertForbidden();

        // User 2 tries to access User 1's lead
        $this->actingAs($user2);
        $response = $this->get("/leads/{$lead1->id}");
        $response->assertForbidden();
    }

    public function test_users_cannot_modify_other_workspace_leads()
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $workspace1->users()->attach($user1->id, ['is_owner' => true, 'is_active' => true]);
        $workspace2->users()->attach($user2->id, ['is_owner' => true, 'is_active' => true]);

        $user1->setCurrentWorkspace($workspace1);
        $user2->setCurrentWorkspace($workspace2);

        $lead2 = Lead::factory()->create(['workspace_id' => $workspace2->id]);

        // User 1 tries to update User 2's lead
        $this->actingAs($user1);
        $response = $this->put("/leads/{$lead2->id}", [
            'first_name' => 'Hacked',
        ]);
        $response->assertForbidden();

        // User 1 tries to delete User 2's lead
        $response = $this->delete("/leads/{$lead2->id}");
        $response->assertForbidden();
    }

    public function test_super_admin_can_access_all_workspaces()
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        $user1 = User::factory()->create();
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $workspace1->users()->attach($user1->id, ['is_owner' => true, 'is_active' => true]);
        $workspace2->users()->attach($superAdmin->id, ['is_owner' => true, 'is_active' => true]);

        $lead1 = Lead::factory()->create(['workspace_id' => $workspace1->id]);
        $lead2 = Lead::factory()->create(['workspace_id' => $workspace2->id]);

        // Super admin should be able to access all
        $this->actingAs($superAdmin);
        $superAdmin->setCurrentWorkspace($workspace2);

        $response = $this->get("/leads/{$lead1->id}");
        $response->assertOk();
    }
}