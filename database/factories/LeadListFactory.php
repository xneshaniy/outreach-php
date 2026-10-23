<?php

namespace Database\Factories;

use App\Models\LeadList;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadListFactory extends Factory
{
    protected $model = LeadList::class;

    public function definition(): array
    {
        $workspace = Workspace::inRandomOrder()->first() ?? Workspace::factory()->create();
        $creator = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))->inRandomOrder()->first();

        return [
            'workspace_id' => $workspace->id,
            'created_by' => $creator?->id ?? $workspace->users()->first()?->id,
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'is_dynamic' => false,
            'filters' => null,
        ];
    }

    public function dynamic(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dynamic' => true,
            'filters' => [
                'status' => 'new',
                'source' => 'website',
            ],
        ]);
    }
}