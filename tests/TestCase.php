<?php

namespace Tests;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Create an authenticated user with a personal workspace, bypassing the
     * invitation-only registration endpoint.
     */
    protected function createTestUser(array $attributes = []): User
    {
        $user = User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ], $attributes));

        $workspace = Workspace::create([
            'name' => $user->name . '\'s Workspace',
            'slug' => Str::slug($user->name . '-workspace') . '-' . Str::random(6),
            'created_by' => $user->id,
        ]);

        $workspace->users()->attach($user->id, [
            'role_id' => null,
            'is_owner' => true,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $user->setCurrentWorkspace($workspace);
        Auth::login($user);

        return $user;
    }
}
