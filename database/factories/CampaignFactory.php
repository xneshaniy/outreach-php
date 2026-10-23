<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Workspace;
use App\Models\User;
use App\Models\EmailAccount;
use App\Models\LeadList;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $workspace = Workspace::inRandomOrder()->first() ?? Workspace::factory()->create();
        $creator = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))->inRandomOrder()->first();
        $emailAccount = EmailAccount::where('workspace_id', $workspace->id)->where('is_active', true)->inRandomOrder()->first();
        $leadList = LeadList::where('workspace_id', $workspace->id)->inRandomOrder()->first();

        return [
            'workspace_id' => $workspace->id,
            'created_by' => $creator?->id ?? $workspace->users()->first()?->id,
            'email_account_id' => $emailAccount?->id,
            'lead_list_id' => $leadList?->id,
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement([Campaign::STATUS_DRAFT, Campaign::STATUS_SCHEDULED, Campaign::STATUS_ACTIVE]),
            'timezone' => 'UTC',
            'sending_window' => [
                'start' => '09:00',
                'end' => '17:00',
                'days' => [1, 2, 3, 4, 5],
            ],
            'daily_send_limit' => 200,
            'delay_between_emails' => 60,
            'scheduled_at' => fake()->optional()->dateTimeBetween('now', '+7 days'),
            'started_at' => null,
            'completed_at' => null,
            'paused_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_DRAFT,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->addHour(),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_ACTIVE,
            'started_at' => now(),
        ]);
    }
}