<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_owner_implicitly_has_every_permission(): void
    {
        $workspace = Workspace::factory()->create();
        $owner = User::factory()->create();

        $workspace->users()->attach($owner->id, [
            'is_owner' => true,
            'is_active' => true,
        ]);

        $owner->setCurrentWorkspace($workspace);

        $this->assertTrue(Gate::forUser($owner)->allows('create campaigns'));
        $this->assertTrue(Gate::forUser($owner)->allows('delete leads'));
        $this->assertTrue(Gate::forUser($owner)->allows('manage workspace users'));
    }

    public function test_member_permissions_come_from_the_role_stored_on_the_workspace_pivot(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $workspace = Workspace::factory()->create();
        $member = User::factory()->create();
        $role = Role::where('name', 'User')->firstOrFail();

        $workspace->users()->attach($member->id, [
            'role_id' => $role->id,
            'is_owner' => false,
            'is_active' => true,
        ]);

        $member->setCurrentWorkspace($workspace);

        $this->assertTrue(Gate::forUser($member)->allows('create campaigns'));
        $this->assertTrue(Gate::forUser($member)->allows('export leads'));
        $this->assertFalse(Gate::forUser($member)->allows('delete leads'));
        $this->assertFalse(Gate::forUser($member)->allows('manage workspace users'));
    }

    public function test_member_without_a_role_has_no_privileged_permissions(): void
    {
        $workspace = Workspace::factory()->create();
        $member = User::factory()->create();

        $workspace->users()->attach($member->id, [
            'role_id' => null,
            'is_owner' => false,
            'is_active' => true,
        ]);

        $member->setCurrentWorkspace($workspace);

        $this->assertFalse(Gate::forUser($member)->allows('create campaigns'));
        $this->assertFalse(Gate::forUser($member)->allows('delete leads'));
    }
}
