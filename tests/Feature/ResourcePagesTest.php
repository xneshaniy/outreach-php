<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\Deal;
use App\Models\EmailAccount;
use App\Models\Lead;
use App\Models\LeadImport;
use App\Models\LeadList;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Creates one record of every kind and opens its show/edit screens. This
 * catches broken Eloquent relationships (wrong foreign key, wrong relation
 * type, missing relation) that HTTP status checks on empty pages cannot.
 */
class ResourcePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_show_and_edit_pages_render(): void
    {
        $this->createTestUser();
        $workspace = Workspace::where('slug', 'test-user-workspace')->first() ?? Workspace::first();
        $user = auth()->user();

        $company = Company::factory()->create(['workspace_id' => $workspace->id, 'created_by' => $user->id]);
        $lead = Lead::factory()->create(['workspace_id' => $workspace->id, 'company_id' => $company->id]);
        $account = EmailAccount::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $user->id]);
        $list = LeadList::factory()->create(['workspace_id' => $workspace->id, 'created_by' => $user->id]);
        $campaign = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'email_account_id' => $account->id,
            'lead_list_id' => $list->id,
        ]);
        $pipeline = Pipeline::factory()->create(['workspace_id' => $workspace->id, 'created_by' => $user->id]);
        PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'New',
            'color' => '#6366f1',
            'order' => 1,
            'probability' => 10,
        ]);
        $deal = Deal::factory()->create([
            'workspace_id' => $workspace->id,
            'pipeline_id' => $pipeline->id,
            'lead_id' => $lead->id,
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);
        $task = Task::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
            'related_type' => $lead->getMorphClass(),
            'related_id' => $lead->id,
        ]);
        $import = LeadImport::create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'original_filename' => 'leads.csv',
            'stored_path' => 'imports/leads.csv',
            'status' => 'completed',
        ]);

        $pages = [
            "/leads/{$lead->id}",
            "/leads/{$lead->id}/edit",
            "/companies/{$company->id}",
            "/companies/{$company->id}/edit",
            "/campaigns/{$campaign->id}/report",
            "/campaigns/{$campaign->id}/edit",
            "/email-accounts/{$account->id}",
            "/email-accounts/{$account->id}/edit",
            "/pipeline/{$pipeline->id}",
            "/pipeline/deals/{$deal->id}",
            "/tasks/{$task->id}",
            "/tasks/{$task->id}/edit",
            "/leads/imports/{$import->id}",
            "/reports",
            "/reports/leads",
            "/reports/campaigns",
            "/reports/deals",
            "/reports/team",
            "/reports/email",
        ];

        $failures = [];

        foreach ($pages as $page) {
            $res = $this->get($page);

            if ($res->getStatusCode() !== 200) {
                $failures[$page] = $res->getStatusCode();
            }
        }

        $this->assertSame([], $failures, 'Pages did not return HTTP 200: '.json_encode($failures));
    }
}
