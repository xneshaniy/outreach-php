<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards against 500s on every authenticated screen. These pages historically
 * failed because controllers referenced models that were never imported, or
 * because of broken relationships.
 */
class AuthenticatedPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_authenticated_pages_render_without_server_errors(): void
    {
        $this->createTestUser();
        $this->assertAuthenticated();

        $pages = [
            '/dashboard',
            '/leads',
            '/leads/create',
            '/leads/import',
            '/leads/imports',
            '/companies',
            '/companies/create',
            '/campaigns',
            '/campaigns/create',
            '/email-accounts',
            '/email-accounts/create',
            '/pipeline',
            '/tasks',
            '/tasks/create',
            '/reports',
            '/reports/leads',
            '/reports/campaigns',
            '/reports/deals',
            '/reports/email',
            '/reports/team',
            '/settings/profile',
            '/settings/appearance',
            '/settings/notifications',
            '/settings/integrations',
            '/settings/users',
            '/settings/workspace',
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
