<?php

namespace Tests\Feature;

use App\Models\EmailAccount;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify store endpoints persist valid records end-to-end (controller +
 * validation + model). This catches bugs that empty-payload smoke tests miss.
 */
class StoreValidDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_account_can_be_created_and_persists(): void
    {
        $this->createTestUser();
        $workspace = Workspace::first();

        $res = $this->post('/email-accounts', [
            'name' => 'Test Mailbox',
            'email' => 'newbox@example.com',
            'provider' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'newbox@example.com',
            'password' => 'secret123',
            'from_name' => 'Test',
            'daily_send_limit' => 200,
        ]);

        $res->assertRedirect(route('email-accounts.index'));

        $account = EmailAccount::where('workspace_id', $workspace->id)
            ->where('normalized_email', 'newbox@example.com')
            ->first();

        $this->assertNotNull($account);
        $this->assertNotEmpty($account->password_encrypted, 'password must be encrypted and stored');
        $this->assertSame('secret123', $account->getPassword(), 'decrypted password must round-trip');
    }

    public function test_task_can_be_created_and_persists(): void
    {
        $this->createTestUser();
        $workspace = Workspace::first();

        $res = $this->post('/tasks', [
            'title' => 'Test Task',
            'description' => 'A test description',
            'type' => 'task',
            'status' => 'pending',
            'priority' => 'medium',
            'due_at' => now()->addDays(2)->toDateString(),
        ]);

        $res->assertRedirect(route('tasks.index'));

        $task = Task::where('workspace_id', $workspace->id)
            ->where('title', 'Test Task')
            ->first();

        $this->assertNotNull($task);
        $this->assertSame($workspace->id, $task->workspace_id);
    }

    public function test_company_can_be_created_and_persists(): void
    {
        $this->createTestUser();
        $workspace = Workspace::first();

        $res = $this->post('/companies', [
            'name' => 'Test Company',
            'domain' => 'testcompany.example.com',
            'industry' => 'Technology',
            'country' => 'USA',
            'phone' => '+1-555-0100',
            'description' => 'A test company',
        ]);

        $res->assertRedirect(route('companies.index'));

        \App\Models\Company::where('workspace_id', $workspace->id)
            ->where('name', 'Test Company')
            ->firstOrFail();
    }
}
