<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkedInApplierTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserAndWorkspace(): User
    {
        $user = $this->createTestUser();
        $this->assertAuthenticated();
        return $user;
    }

    public function test_linkedin_applier_page_loads_successfully(): void
    {
        $this->setupUserAndWorkspace();

        $response = $this->get('/tools/linkedin-applier');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tools/LinkedInApplier/Index')
            ->has('applications')
            ->has('networkInvites')
            ->has('inboxMessages')
            ->has('searches')
        );
    }

    public function test_linkedin_applier_search_jobs(): void
    {
        $this->setupUserAndWorkspace();

        $response = $this->postJson('/tools/linkedin-applier/search', [
            'query' => 'Sales Representative',
            'location' => 'Remote',
            'job_type' => 'full_time',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'search',
            'applications',
            'message',
        ]);
    }

    public function test_linkedin_applier_generate_lion(): void
    {
        $this->setupUserAndWorkspace();

        $response = $this->postJson('/tools/linkedin-applier/network/lion', [
            'industry' => 'SaaS',
            'role' => 'Sales Leader',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'invites',
            'message',
        ]);
    }

    public function test_linkedin_applier_classify_message(): void
    {
        $this->setupUserAndWorkspace();

        $response = $this->postJson('/tools/linkedin-applier/inbox/classify', [
            'sender_name' => 'Recruiter John',
            'message_text' => 'Hi, we have an open SDR role and saw your impressive profile. Can we talk?',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message_item',
            'message',
        ]);
    }
}
