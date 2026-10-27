<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeadsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_users_cannot_access_admin_leads_or_workspaces()
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create(['is_super_admin' => false]);
        $workspace->users()->attach($user->id, ['is_owner' => true, 'is_active' => true]);
        $user->setCurrentWorkspace($workspace);

        $this->actingAs($user);

        // Normal user accessing admin leads -> 403 Forbidden
        $response = $this->get('/admin/leads');
        $response->assertForbidden();

        // Normal user accessing admin workspaces -> 403 Forbidden
        $response = $this->get('/admin/workspaces');
        $response->assertForbidden();
    }

    public function test_normal_users_are_isolated_to_their_own_workspace_leads()
    {
        $workspaceA = Workspace::factory()->create(['name' => 'Workspace Alpha']);
        $workspaceB = Workspace::factory()->create(['name' => 'Workspace Beta']);

        $userA = User::factory()->create(['name' => 'User Alpha']);
        $userB = User::factory()->create(['name' => 'User Beta']);

        $workspaceA->users()->attach($userA->id, ['is_owner' => true, 'is_active' => true]);
        $workspaceB->users()->attach($userB->id, ['is_owner' => true, 'is_active' => true]);

        $userA->setCurrentWorkspace($workspaceA);
        $userB->setCurrentWorkspace($workspaceB);

        $leadA = Lead::factory()->create([
            'workspace_id' => $workspaceA->id,
            'first_name' => 'Alice',
            'email' => 'alice@alpha.test',
        ]);
        $leadB = Lead::factory()->create([
            'workspace_id' => $workspaceB->id,
            'first_name' => 'Bob',
            'email' => 'bob@beta.test',
        ]);

        // User A only sees leadA
        $this->actingAs($userA);
        $res = $this->get('/leads');
        $res->assertOk();
        $leadsA = collect($res->inertiaProps('leads.data') ?? [])->pluck('id')->toArray();
        $this->assertContains($leadA->id, $leadsA);
        $this->assertNotContains($leadB->id, $leadsA);

        // User A cannot view leadB details
        $resForbidden = $this->get("/leads/{$leadB->id}");
        $resForbidden->assertForbidden();

        // User B only sees leadB
        $this->actingAs($userB);
        $res = $this->get('/leads');
        $res->assertOk();
        $leadsB = collect($res->inertiaProps('leads.data') ?? [])->pluck('id')->toArray();
        $this->assertContains($leadB->id, $leadsB);
        $this->assertNotContains($leadA->id, $leadsB);
    }

    public function test_super_admin_can_view_all_leads_across_all_workspaces()
    {
        $workspaceA = Workspace::factory()->create(['name' => 'Workspace Alpha']);
        $workspaceB = Workspace::factory()->create(['name' => 'Workspace Beta']);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $workspaceA->users()->attach($userA->id, ['is_owner' => true, 'is_active' => true]);
        $workspaceB->users()->attach($userB->id, ['is_owner' => true, 'is_active' => true]);

        $leadA = Lead::factory()->create([
            'workspace_id' => $workspaceA->id,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@alpha.test',
        ]);
        $leadB = Lead::factory()->create([
            'workspace_id' => $workspaceB->id,
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'email' => 'bob@beta.test',
        ]);

        $this->actingAs($superAdmin);

        // Super Admin visits /admin/leads
        $res = $this->get('/admin/leads');
        $res->assertOk();

        $leads = collect($res->inertiaProps('leads.data') ?? [])->pluck('id')->toArray();
        $this->assertContains($leadA->id, $leads);
        $this->assertContains($leadB->id, $leads);

        // Summary counts match database
        $this->assertEquals(2, $res->inertiaProps('summary.total_leads'));
        $this->assertEquals(2, $res->inertiaProps('summary.total_workspaces'));
    }

    public function test_super_admin_can_filter_leads_by_workspace()
    {
        $workspaceA = Workspace::factory()->create(['name' => 'Workspace Alpha']);
        $workspaceB = Workspace::factory()->create(['name' => 'Workspace Beta']);

        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $leadA = Lead::factory()->create([
            'workspace_id' => $workspaceA->id,
            'first_name' => 'Alice',
            'email' => 'alice@alpha.test',
        ]);
        $leadB = Lead::factory()->create([
            'workspace_id' => $workspaceB->id,
            'first_name' => 'Bob',
            'email' => 'bob@beta.test',
        ]);

        $this->actingAs($superAdmin);

        // Filter by Workspace A
        $resA = $this->get("/admin/leads?workspace_id={$workspaceA->id}");
        $resA->assertOk();
        $leadsA = collect($resA->inertiaProps('leads.data') ?? [])->pluck('id')->toArray();
        $this->assertContains($leadA->id, $leadsA);
        $this->assertNotContains($leadB->id, $leadsA);
        $this->assertEquals(1, $resA->inertiaProps('summary.total_leads'));

        // Filter by Workspace B
        $resB = $this->get("/admin/leads?workspace_id={$workspaceB->id}");
        $resB->assertOk();
        $leadsB = collect($resB->inertiaProps('leads.data') ?? [])->pluck('id')->toArray();
        $this->assertContains($leadB->id, $leadsB);
        $this->assertNotContains($leadA->id, $leadsB);
        $this->assertEquals(1, $resB->inertiaProps('summary.total_leads'));
    }

    public function test_super_admin_can_search_leads_across_workspaces()
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $leadA = Lead::factory()->create([
            'workspace_id' => $workspaceA->id,
            'first_name' => 'Zara',
            'email' => 'unique_zara@test.com',
        ]);
        $leadB = Lead::factory()->create([
            'workspace_id' => $workspaceB->id,
            'first_name' => 'Tom',
            'email' => 'tom@test.com',
        ]);

        $this->actingAs($superAdmin);

        $res = $this->get('/admin/leads?search=unique_zara');
        $res->assertOk();
        $leads = collect($res->inertiaProps('leads.data') ?? [])->pluck('id')->toArray();
        $this->assertContains($leadA->id, $leads);
        $this->assertNotContains($leadB->id, $leads);
    }

    public function test_super_admin_can_view_workspace_details_and_toggle_status()
    {
        $workspace = Workspace::factory()->create(['name' => 'Acme Corp', 'is_active' => true]);
        $user = User::factory()->create(['name' => 'Acme Owner']);
        $workspace->users()->attach($user->id, ['is_owner' => true, 'is_active' => true]);
        $lead = Lead::factory()->create(['workspace_id' => $workspace->id]);

        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($superAdmin);

        // View workspace details
        $res = $this->get("/admin/workspaces/{$workspace->id}");
        $res->assertOk();
        $this->assertEquals('Acme Corp', $res->inertiaProps('workspace.name'));
        $this->assertEquals(1, $res->inertiaProps('workspace.leads_count'));

        // Toggle workspace status
        $postRes = $this->post("/admin/workspaces/{$workspace->id}/toggle");
        $postRes->assertRedirect();
        $this->assertFalse($workspace->fresh()->is_active);
    }

    public function test_super_admin_can_delete_lead()
    {
        $workspace = Workspace::factory()->create();
        $lead = Lead::factory()->create(['workspace_id' => $workspace->id]);

        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($superAdmin);

        $res = $this->delete("/admin/leads/{$lead->id}");
        $res->assertRedirect();
        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
    }

    public function test_super_admin_dashboard_shows_accurate_workspace_and_lead_counts()
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        Lead::factory()->count(3)->create(['workspace_id' => $workspace1->id]);
        Lead::factory()->count(2)->create(['workspace_id' => $workspace2->id]);

        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($superAdmin);

        $res = $this->get('/admin/dashboard');
        $res->assertOk();

        $this->assertEquals(2, $res->inertiaProps('metrics.workspaces.total'));
        $this->assertEquals(5, $res->inertiaProps('metrics.leads.total'));
    }
}
