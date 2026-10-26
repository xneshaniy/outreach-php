<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_owner_can_update_workspace_and_manage_members(): void
    {
        $this->createTestUser();
        $workspace = Workspace::where('slug', 'test-user-workspace')->first() ?? Workspace::first();

        $this->put('/settings/workspace', [
            'name' => 'Renamed Workspace',
            'primary_color' => '#123456',
        ])->assertRedirect();

        $this->assertSame('Renamed Workspace', $workspace->fresh()->name);

        $member = User::factory()->create(['email' => 'member@example.com']);

        $this->post('/settings/users/invite', ['email' => $member->email])
            ->assertRedirect();

        $this->assertTrue($workspace->users()->whereKey($member->id)->exists());

        $this->put("/settings/users/{$member->id}/role", ['role_id' => null])
            ->assertRedirect();

        $this->delete("/settings/users/{$member->id}")->assertRedirect();
        $this->assertFalse($workspace->users()->whereKey($member->id)->exists());
    }
}
