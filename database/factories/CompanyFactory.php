<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $workspace = Workspace::inRandomOrder()->first() ?? Workspace::factory()->create();
        $creator = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))->inRandomOrder()->first();

        return [
            'workspace_id' => $workspace->id,
            'created_by' => $creator?->id ?? $workspace->users()->first()?->id,
            'name' => fake()->company(),
            'domain' => fake()->optional()->domainName(),
            'website' => fake()->optional()->url(),
            'industry' => fake()->optional()->randomElement(['Technology', 'Healthcare', 'Finance', 'Manufacturing', 'Retail', 'Education']),
            'size' => fake()->optional()->randomElement(['1-10', '11-50', '51-200', '201-500', '501-1000', '1000+']),
            'country' => fake()->optional()->countryCode(),
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->state(),
            'postal_code' => fake()->optional()->postcode(),
            'address' => fake()->optional()->streetAddress(),
            'phone' => fake()->optional()->phoneNumber(),
            'linkedin_url' => fake()->optional()->url(),
            'twitter_url' => fake()->optional()->url(),
            'description' => fake()->optional()->paragraph(),
            'custom_fields' => null,
            'last_contacted_at' => null,
        ];
    }
}