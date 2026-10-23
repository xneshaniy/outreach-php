<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CampaignStep;
use App\Models\EmailAccount;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
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

    public function test_user_can_create_campaign()
    {
        $emailAccount = EmailAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'is_active' => true,
        ]);

        $leadList = LeadList::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->post('/campaigns', [
            'name' => 'Test Campaign',
            'description' => 'A test campaign',
            'email_account_id' => $emailAccount->id,
            'lead_list_id' => $leadList->id,
            'daily_send_limit' => 100,
            'steps' => [
                [
                    'name' => 'Step 1',
                    'subject' => 'Hello {{first_name}}',
                    'body_html' => '<p>Hi {{first_name}},</p><p>This is a test.</p>',
                    'delay_days' => 0,
                    'delay_hours' => 0,
                    'delay_minutes' => 0,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    public function test_campaign_requires_at_least_one_step()
    {
        $emailAccount = EmailAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->post('/campaigns', [
            'name' => 'Test Campaign',
            'email_account_id' => $emailAccount->id,
            'steps' => [],
        ]);

        $response->assertSessionHasErrors('steps');
    }

    public function test_user_can_activate_campaign()
    {
        $campaign = Campaign::factory()->create([
            'workspace_id' => $this->workspace->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $emailAccount = EmailAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'is_active' => true,
        ]);

        $campaign->update(['email_account_id' => $emailAccount->id]);

        CampaignStep::factory()->create([
            'campaign_id' => $campaign->id,
            'is_active' => true,
        ]);

        Lead::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->post("/campaigns/{$campaign->id}/activate");

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals(Campaign::STATUS_ACTIVE, $campaign->status);
    }

    public function test_user_can_pause_campaign()
    {
        $campaign = Campaign::factory()->create([
            'workspace_id' => $this->workspace->id,
            'status' => Campaign::STATUS_ACTIVE,
        ]);

        $response = $this->post("/campaigns/{$campaign->id}/pause");

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals(Campaign::STATUS_PAUSED, $campaign->status);
    }

    public function test_user_can_add_recipients_to_campaign()
    {
        $campaign = Campaign::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $leads = Lead::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->post("/campaigns/{$campaign->id}/recipients", [
            'lead_ids' => $leads->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('campaign_recipients', 5);
    }

    public function test_campaign_recipient_prevents_duplicate_sends()
    {
        $campaign = Campaign::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $lead = Lead::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id,
            'lead_id' => $lead->id,
        ]);

        $response = $this->post("/campaigns/{$campaign->id}/recipients", [
            'lead_ids' => [$lead->id],
        ]);

        $this->assertDatabaseCount('campaign_recipients', 1);
    }
}