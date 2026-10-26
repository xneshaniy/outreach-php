<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Invitation;
use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityFoundationTest extends TestCase
{
    /**
     * 1. Public registration must be disabled.
     */
    public function test_public_registration_is_disabled(): void
    {
        $response = $this->get('/register');
        $response->assertRedirect('/login');

        $postResponse = $this->post('/register', [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $this->assertEquals(403, $postResponse->getStatusCode());
    }

    /**
     * 2. Invitation token generation & single-use lifecycle.
     */
    public function test_invitation_lifecycle_and_single_use(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $workspace = Workspace::factory()->create();

        $pair = Invitation::createTokenPair();
        $invitation = Invitation::create([
            'workspace_id' => $workspace->id,
            'email' => 'newcolleague@example.com',
            'role' => 'user',
            'token_hash' => $pair['hash'],
            'status' => 'pending',
            'invited_by_id' => $superAdmin->id,
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        $this->assertTrue($invitation->isPending());

        // Visit accept form
        $formResponse = $this->get("/invite/{$pair['raw']}");
        $formResponse->assertStatus(200);

        // Accept invitation
        $acceptResponse = $this->post("/invite/{$pair['raw']}", [
            'name' => 'Colleague One',
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
        ]);
        $acceptResponse->assertRedirect('/dashboard');

        // Invitation must be marked accepted
        $invitation->refresh();
        $this->assertEquals('accepted', $invitation->status);
        $this->assertNotNull($invitation->accepted_at);

        // User must exist and be active
        $user = User::where('email', 'newcolleague@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('SecurePass2026!', $user->password));

        // Replay attempt must fail
        $replayResponse = $this->post("/invite/{$pair['raw']}", [
            'name' => 'Hacker Impersonator',
            'password' => 'HackedPass123!',
            'password_confirmation' => 'HackedPass123!',
        ]);
        $replayResponse->assertSessionHasErrors(['error']);
    }

    /**
     * 3. Expired or revoked invitations cannot be accepted.
     */
    public function test_expired_or_revoked_invitations_cannot_be_accepted(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        // Expired invitation
        $expiredPair = Invitation::createTokenPair();
        Invitation::create([
            'email' => 'expired@example.com',
            'role' => 'user',
            'token_hash' => $expiredPair['hash'],
            'status' => 'pending',
            'invited_by_id' => $superAdmin->id,
            'expires_at' => Carbon::now()->subDays(1),
        ]);

        $res1 = $this->get("/invite/{$expiredPair['raw']}");
        $this->assertEquals(410, $res1->getStatusCode());

        // Revoked invitation
        $revokedPair = Invitation::createTokenPair();
        Invitation::create([
            'email' => 'revoked@example.com',
            'role' => 'user',
            'token_hash' => $revokedPair['hash'],
            'status' => 'revoked',
            'invited_by_id' => $superAdmin->id,
            'expires_at' => Carbon::now()->addDays(5),
        ]);

        $res2 = $this->get("/invite/{$revokedPair['raw']}");
        $this->assertEquals(410, $res2->getStatusCode());
    }

    /**
     * 4. Protection against deleting or demoting the last active Super Admin.
     */
    public function test_last_active_super_admin_cannot_be_deleted_or_demoted(): void
    {
        $loneSuperAdmin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        // Acting as the lone Super Admin
        $this->actingAs($loneSuperAdmin);

        // Attempting to demote self
        $demoteResponse = $this->put(route('admin.users.limits', $loneSuperAdmin->id), [
            'is_super_admin' => false,
            'daily_email_limit' => 500,
            'monthly_token_budget' => 50,
            'max_campaigns' => 10,
        ]);
        $demoteResponse->assertSessionHasErrors(['error']);
        $loneSuperAdmin->refresh();
        $this->assertTrue($loneSuperAdmin->is_super_admin);

        // Attempting to delete self
        $deleteResponse = $this->delete(route('admin.users.destroy', $loneSuperAdmin->id));
        $deleteResponse->assertSessionHasErrors(['error']);
        $this->assertNull($loneSuperAdmin->fresh()->deleted_at);
    }
}
