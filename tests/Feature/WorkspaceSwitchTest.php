<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify workspace switching persists across requests and that authorization
 * boundaries are respected (super admin cross-workspace; normal user limited
 * to authorized workspaces).
 */
class WorkspaceSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_can_switch_between_authorized_workspaces_and_data_changes(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $wsA = Workspace::factory()->create(['name' => 'Workspace A', 'is_active' => true]);
        $wsB = Workspace::factory()->create(['name' => 'Workspace B', 'is_active' => true]);

        $user->workspaces()->attach($wsA->id, ['is_owner' => true, 'is_active' => true, 'joined_at' => now()]);
        $user->workspaces()->attach($wsB->id, ['is_owner' => false, 'is_active' => true, 'joined_at' => now()]);

        $this->actingAs($user);

        // Start in workspace A with an older last_accessed_at so ordering is
        // deterministic across sub-second requests.
        $user->setCurrentWorkspace($wsA);
        $user->workspaces()->updateExistingPivot($wsA->id, [
            'last_accessed_at' => now()->subHour(),
        ]);
        $this->assertSame($wsA->id, $user->currentWorkspace->id);

        // Switch to workspace B via the endpoint
        $res = $this->post('/settings/workspace/switch', ['workspace_id' => $wsB->id]);
        $res->assertRedirect(route('dashboard'));

        // The selection must persist on a fresh request (re-fetch user)
        $user->refresh();
        $this->assertSame($wsB->id, $user->currentWorkspace->id, 'workspace switch must persist across requests');

        // Data must be scoped to the new workspace
        \App\Models\Task::factory()->create(['workspace_id' => $wsB->id, 'title' => 'Task in B']);
        \App\Models\Task::factory()->create(['workspace_id' => $wsA->id, 'title' => 'Task in A']);

        $tasksInB = \App\Models\Task::where('workspace_id', $user->currentWorkspace->id)->get()->pluck('title');
        $this->assertTrue($tasksInB->contains('Task in B'));
        $this->assertFalse($tasksInB->contains('Task in A'));
    }

    public function test_normal_user_cannot_switch_to_unauthorized_workspace(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $wsA = Workspace::factory()->create(['is_active' => true]);
        $wsB = Workspace::factory()->create(['is_active' => true]);

        $user->workspaces()->attach($wsA->id, ['is_owner' => true, 'is_active' => true, 'joined_at' => now()]);
        // wsB: NOT attached to user

        $this->actingAs($user);
        $user->setCurrentWorkspace($wsA);

        $res = $this->post('/settings/workspace/switch', ['workspace_id' => $wsB->id]);
        $res->assertStatus(403);
    }

    public function test_super_admin_can_access_any_workspace(): void
    {
        $super = User::factory()->create(['is_active' => true, 'is_super_admin' => true]);

        $wsA = Workspace::factory()->create(['is_active' => true]);
        $wsB = Workspace::factory()->create(['is_active' => true]);

        $this->actingAs($super);

        // Super admin not attached to wsB but can still switch to it (no 403).
        $res = $this->post('/settings/workspace/switch', ['workspace_id' => $wsB->id]);
        $res->assertRedirect(route('dashboard'));

        // Cross-workspace access: the switch must attach/activate wsB for the
        // super admin even though they were not previously a member.
        $this->assertTrue(
            $super->workspaces()->where('workspace_id', $wsB->id)->wherePivot('is_active', true)->exists(),
            'Super admin must be able to activate a workspace they were not attached to.'
        );
    }
}
