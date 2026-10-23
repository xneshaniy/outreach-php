<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Workspace;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        $workspace = Workspace::inRandomOrder()->first() ?? Workspace::factory()->create();
        $company = Company::where('workspace_id', $workspace->id)->inRandomOrder()->first();
        $owner = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))->inRandomOrder()->first();
        $creator = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))->inRandomOrder()->first();

        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $email = \Illuminate\Support\Str::lower("{$firstName}.{$lastName}@" . fake()->domainName());

        return [
            'workspace_id' => $workspace->id,
            'company_id' => $company?->id,
            'owner_id' => $owner?->id,
            'created_by' => $creator?->id ?? $workspace->users()->first()?->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'normalized_email' => \Illuminate\Support\Str::lower($email),
            'phone' => fake()->optional()->phoneNumber(),
            'mobile' => fake()->optional()->phoneNumber(),
            'job_title' => fake()->optional()->jobTitle(),
            'department' => fake()->optional()->randomElement(['Sales', 'Marketing', 'Engineering', 'Operations', 'Finance']),
            'seniority' => fake()->optional()->randomElement(['Junior', 'Mid', 'Senior', 'Lead', 'Director', 'VP', 'C-Level']),
            'linkedin_url' => fake()->optional()->url(),
            'twitter_url' => fake()->optional()->url(),
            'country' => fake()->optional()->countryCode(),
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->state(),
            'timezone' => fake()->optional()->timezone(),
            'source' => fake()->optional()->randomElement(['website', 'referral', 'linkedin', 'cold_call', 'event', 'partner']),
            'status' => fake()->randomElement(['new', 'contacted', 'qualified', 'unqualified', 'converted', 'archived']),
            'tags' => fake()->optional()->randomElements(['hot', 'warm', 'cold', 'enterprise', 'smb', 'decision_maker'], fake()->numberBetween(0, 3)),
            'notes' => fake()->optional()->paragraph(),
            'custom_fields' => null,
            'follow_up_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'last_activity_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'last_contacted_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'converted_at' => null,
        ];
    }

    public function new(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'new',
        ]);
    }

    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'contacted',
        ]);
    }

    public function qualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'qualified',
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'converted',
            'converted_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}