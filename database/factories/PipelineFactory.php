<?php

namespace Database\Factories;

use App\Models\Pipeline;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PipelineFactory extends Factory
{
    protected $model = Pipeline::class;

    public function definition(): array
    {
        $workspace = Workspace::inRandomOrder()->first() ?? Workspace::factory()->create();
        $creator = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))->inRandomOrder()->first();

        return [
            'workspace_id' => $workspace->id,
            'created_by' => $creator?->id ?? $workspace->users()->first()?->id,
            'name' => fake()->words(3, true) . ' Pipeline',
            'description' => fake()->optional()->sentence(),
            'is_default' => false,
        ];
    }
}