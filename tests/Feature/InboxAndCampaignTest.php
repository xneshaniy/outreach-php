<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\EmailAccount;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxAndCampaignTest extends TestCase
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

    public function test_authenticated_user_can_view_inbox(): void
    {
        $response = $this->get(route('inbox.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page->component('Inbox/Index'));
    }

    public function test_user_can_complete_active_campaign(): void
    {
        $account = EmailAccount::factory()->create(['workspace_id' => $this->workspace->id]);
        $campaign = Campaign::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
            'email_account_id' => $account->id,
            'status' => Campaign::STATUS_ACTIVE,
        ]);

        $response = $this->post(route('campaigns.complete', $campaign));

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals(Campaign::STATUS_COMPLETED, $campaign->status);
    }

    public function test_user_can_send_direct_email_from_inbox(): void
    {
        $account = EmailAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'is_active' => true,
        ]);

        $mockEmailService = $this->mock(\App\Services\EmailService::class);
        $mockEmailService->shouldReceive('send')->once()->andReturn([
            'success' => true,
            'message_id' => 'test-message-id-123',
        ]);

        $response = $this->post(route('inbox.send'), [
            'email_account_id' => $account->id,
            'to_email' => 'prospect@example.com',
            'to_name' => 'John Prospect',
            'subject' => 'Hello from Inbox',
            'body' => 'This is a direct test email body.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', [
            'workspace_id' => $this->workspace->id,
            'email_account_id' => $account->id,
            'subject' => 'Hello from Inbox',
        ]);
        $this->assertDatabaseHas('leads', [
            'workspace_id' => $this->workspace->id,
            'email' => 'prospect@example.com',
        ]);
    }
}
