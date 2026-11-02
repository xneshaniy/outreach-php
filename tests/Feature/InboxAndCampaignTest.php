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
}
