<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Workspace;
use App\Models\User;
use App\Models\Lead;
use App\Models\Company;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        $workspace = Workspace::inRandomOrder()->first() ?? Workspace::factory()->create();
        $pipeline = Pipeline::where('workspace_id', $workspace->id)->inRandomOrder()->first() ?? Pipeline::factory()->create(['workspace_id' => $workspace->id]);
        $defaultStage = $pipeline->getDefaultStage();
        $owner = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))->inRandomOrder()->first();
        $creator = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))->inRandomOrder()->first();
        $lead = Lead::where('workspace_id', $workspace->id)->inRandomOrder()->first();
        $company = Company::where('workspace_id', $workspace->id)->inRandomOrder()->first();

        return [
            'workspace_id' => $workspace->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $defaultStage?->id,
            'lead_id' => $lead?->id,
            'company_id' => $company?->id,
            'owner_id' => $owner?->id,
            'created_by' => $creator?->id ?? $workspace->users()->first()?->id,
            'name' => fake()->catchPhrase() . ' Deal',
            'value' => fake()->randomFloat(2, 1000, 100000),
            'currency' => 'USD',
            'expected_close_date' => fake()->optional()->dateTimeBetween('now', '+90 days'),
            'actual_close_date' => null,
            'status' => Deal::STATUS_OPEN,
            'notes' => fake()->optional()->paragraph(),
            'custom_fields' => null,
            'last_activity_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Deal::STATUS_WON,
            'actual_close_date' => now()->toDateString(),
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Deal::STATUS_LOST,
            'actual_close_date' => now()->toDateString(),
        ]);
    }
}