<?php

namespace Database\Factories;

use App\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    public function definition(): array
    {
        $name = fake()->company();
        
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . fake()->unique()->randomNumber(6),
            'description' => fake()->optional()->paragraph(),
            'primary_color' => '#1e3a5f',
            'secondary_color' => '#0d9488',
            'settings' => [],
            'is_active' => true,
            'trial_ends_at' => null,
        ];
    }
}