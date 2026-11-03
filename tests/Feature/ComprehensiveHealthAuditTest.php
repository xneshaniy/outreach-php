<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\Deal;
use App\Models\EmailAccount;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\Message;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveHealthAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
        $this->workspace->users()->attach($this->user->id, ['is_owner' => true, 'is_active' => true, 'role' => 'admin']);
        $this->user->setCurrentWorkspace($this->workspace);

        $this->actingAs($this->user);
    }

    public function test_all_crm_index_pages_load_successfully(): void
    {
        $routes = [
            'dashboard',
            'leads.index',
            'companies.index',
            'tasks.index',
            'campaigns.index',
            'email-accounts.index',
            'pipeline.index',
            'inbox.index',
            'reports.index',
            'reports.leads',
            'reports.campaigns',
            'reports.deals',
            'reports.team',
            'reports.email',
            'settings.profile',
            'settings.workspace',
            'settings.notifications',
            'settings.appearance',
            'settings.integrations',
            'settings.users',
        ];

        foreach ($routes as $route) {
            $response = $this->get(route($route));
            $this->assertTrue(
                in_array($response->getStatusCode(), [200, 302]),
                "Route {$route} failed with status {$response->getStatusCode()}"
            );
        }
    }

    public function test_companies_crud_lifecycle(): void
    {
        // 1. Create company via store
        $storeResp = $this->post(route('companies.store'), [
            'name' => 'Acme Corporation Test',
            'domain' => 'acmecorp.com',
            'industry' => 'Technology',
            'country' => 'United States',
            'phone' => '+1 555-1234',
            'description' => 'Target account for enterprise plan',
        ]);
        $storeResp->assertRedirect();

        $company = Company::where('workspace_id', $this->workspace->id)
            ->where('name', 'Acme Corporation Test')
            ->first();
        $this->assertNotNull($company);

        // 2. View company show page
        $showResp = $this->get(route('companies.show', $company->id));
        $showResp->assertOk();

        // 3. Update company
        $updateResp = $this->put(route('companies.update', $company->id), [
            'name' => 'Acme Corporation Updated',
            'domain' => 'acme-updated.com',
            'industry' => 'SaaS',
            'country' => 'Canada',
        ]);
        $updateResp->assertRedirect();
        $company->refresh();
        $this->assertEquals('Acme Corporation Updated', $company->name);

        // 4. Delete company
        $delResp = $this->delete(route('companies.destroy', $company->id));
        $delResp->assertRedirect();
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_tasks_crud_lifecycle(): void
    {
        // 1. Create task
        $storeResp = $this->post(route('tasks.store'), [
            'title' => 'Follow up with lead',
            'description' => 'Send contract agreement',
            'type' => 'call',
            'priority' => 'high',
            'due_at' => now()->addDays(2)->toDateTimeString(),
        ]);
        $storeResp->assertRedirect();

        $task = Task::where('workspace_id', $this->workspace->id)->first();
        $this->assertNotNull($task);

        // 2. View task show page
        $this->get(route('tasks.show', $task->id))->assertOk();

        // 3. Complete task
        $completeResp = $this->post(route('tasks.complete', $task->id));
        $completeResp->assertRedirect();
        $task->refresh();
        $this->assertEquals(Task::STATUS_COMPLETED, $task->status);
    }

    public function test_leads_filter_and_deduplicate(): void
    {
        // Create duplicate leads
        Lead::create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'duplicate@test.com',
            'normalized_email' => 'duplicate@test.com',
            'status' => 'new',
        ]);

        Lead::create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
            'first_name' => 'John Duplicate',
            'last_name' => 'Doe',
            'email' => 'duplicate@test.com',
            'normalized_email' => 'duplicate@test.com',
            'status' => 'new',
        ]);

        $this->assertEquals(2, Lead::where('workspace_id', $this->workspace->id)->where('normalized_email', 'duplicate@test.com')->count());

        // Run deduplication
        $resp = $this->post(route('leads.remove-duplicates'));
        $resp->assertRedirect();

        $this->assertEquals(1, Lead::where('workspace_id', $this->workspace->id)->where('normalized_email', 'duplicate@test.com')->count());
    }
}
