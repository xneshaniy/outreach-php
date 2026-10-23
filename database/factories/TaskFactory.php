<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Workspace;
use App\Models\User;
use App\Models\Lead;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $workspace = Workspace::inRandomOrder()->first() ?? Workspace::factory()->create();
        $assignee = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))->inRandomOrder()->first();
        $creator = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))->inRandomOrder()->first();

        $relatedTypes = ['lead', 'company', 'deal', 'campaign'];
        $relatedType = fake()->randomElement($relatedTypes);
        
        $relatedModel = match ($relatedType) {
            'lead' => Lead::class,
            'company' => Company::class,
            'deal' => Deal::class,
            'campaign' => Campaign::class,
        };

        $related = $relatedModel::where('workspace_id', $workspace->id)->inRandomOrder()->first();

        return [
            'workspace_id' => $workspace->id,
            'assigned_to' => $assignee?->id,
            'created_by' => $creator?->id ?? $workspace->users()->first()?->id,
            'related_type' => $related ? $related->getMorphClass() : null,
            'related_id' => $related?->id,
            'title' => fake()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'type' => fake()->randomElement([Task::TYPE_TASK, Task::TYPE_CALL, Task::TYPE_EMAIL, Task::TYPE_MEETING, Task::TYPE_NOTE, Task::TYPE_FOLLOW_UP]),
            'status' => fake()->randomElement([Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED]),
            'priority' => fake()->randomElement([Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH, Task::PRIORITY_URGENT]),
            'due_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'started_at' => null,
            'completed_at' => null,
            'remind_at' => fake()->optional()->dateTimeBetween('now', '+7 days'),
            'reminder_sent' => false,
            'metadata' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'status' => Task::STATUS_PENDING,
        ]);
    }

    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->addHours(fake()->numberBetween(1, 24)),
            'status' => Task::STATUS_PENDING,
        ]);
    }
}