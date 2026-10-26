<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignStep;
use App\Models\Company;
use App\Models\Deal;
use App\Models\EmailAccount;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Executes the main write actions (update, archive/restore, campaign state
 * changes, deal win/lose/move, task completion) and asserts they are accepted.
 * Empty-payload tests miss failures caused by real column/relationship usage.
 */
class MutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_common_mutations_succeed(): void
    {
        $this->createTestUser();
        $workspace = Workspace::where('slug', 'test-user-workspace')->first() ?? Workspace::first();
        $user = auth()->user();

        $company = Company::factory()->create(['workspace_id' => $workspace->id, 'created_by' => $user->id]);
        $lead = Lead::factory()->create([
            'workspace_id' => $workspace->id,
            'company_id' => $company->id,
            'owner_id' => $user->id,
        ]);
        $account = EmailAccount::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $user->id]);
        $campaign = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'email_account_id' => $account->id,
        ]);
        CampaignStep::factory()->create(['campaign_id' => $campaign->id, 'is_active' => true]);
        $pipeline = Pipeline::factory()->create(['workspace_id' => $workspace->id, 'created_by' => $user->id]);
        $stage1 = PipelineStage::create([
            'pipeline_id' => $pipeline->id, 'name' => 'New', 'color' => '#6366f1',
            'order' => 1, 'probability' => 10,
        ]);
        $stage2 = PipelineStage::create([
            'pipeline_id' => $pipeline->id, 'name' => 'Won', 'color' => '#10b981',
            'order' => 2, 'probability' => 100, 'is_won' => true, 'is_closed' => true,
        ]);
        $deal = Deal::factory()->create([
            'workspace_id' => $workspace->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage1->id,
            'lead_id' => $lead->id,
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);
        $task = Task::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        $requests = [
            ['put', "/leads/{$lead->id}", ['first_name' => 'Updated', 'email' => $lead->email]],
            ['post', '/leads/bulk-action', ['action' => 'archive', 'lead_ids' => [$lead->id]]],
            ['post', '/leads/bulk-action', ['action' => 'restore', 'lead_ids' => [$lead->id]]],
            ['put', "/companies/{$company->id}", ['name' => 'Updated Co']],
            ['put', "/email-accounts/{$account->id}", ['name' => 'Updated Mailbox']],
            ['post', "/email-accounts/{$account->id}/set-default", []],
            ['post', "/campaigns/{$campaign->id}/pause", []],
            ['post', "/campaigns/{$campaign->id}/resume", []],
            ['post', "/campaigns/{$campaign->id}/schedule", ['scheduled_at' => now()->addHour()->toDateTimeString()]],
            ['post', "/campaigns/{$campaign->id}/activate", []],
            ['post', "/campaigns/{$campaign->id}/cancel", []],
            ['put', "/pipeline/{$pipeline->id}", ['name' => 'Updated Pipeline']],
            ['put', "/pipeline/deals/{$deal->id}", ['name' => 'Updated Deal', 'stage_id' => $stage2->id]],
            ['post', "/pipeline/deals/{$deal->id}/move", ['stage_id' => $stage1->id]],
            ['post', "/pipeline/deals/{$deal->id}/win", []],
            ['post', "/pipeline/deals/{$deal->id}/lose", []],
            ['post', "/tasks/{$task->id}/start", []],
            ['post', "/tasks/{$task->id}/complete", []],
            ['put', "/tasks/{$task->id}", ['title' => 'Updated Task']],
            ['put', '/settings/profile', ['name' => 'Mutation User 2']],
        ];

        $failures = [];

        foreach ($requests as [$method, $url, $payload]) {
            $res = $this->{$method}($url, $payload);

            if ($res->getStatusCode() >= 400) {
                $failures[] = $method.' '.$url.' => '.$res->getStatusCode();
            }
        }

        $this->assertSame([], $failures, print_r($failures, true));

        // The archived lead must be restorable and the deal must end up lost.
        $this->assertNull(Lead::find($lead->id)?->deleted_at);

        // win() moves the deal to the Won stage; lose() keeps that stage because
        // this pipeline has no dedicated Lost stage, so the deal never ends up
        // with a null stage_id.
        $this->assertSame(Deal::STATUS_LOST, $deal->fresh()->status);
        $this->assertNotNull($deal->fresh()->stage_id);
        $this->assertSame($stage2->id, $deal->fresh()->stage_id);
    }
}
