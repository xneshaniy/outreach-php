<?php

namespace Database\Factories;

use App\Models\CampaignStep;
use App\Models\Campaign;
use App\Models\EmailAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignStepFactory extends Factory
{
    protected $model = CampaignStep::class;

    public function definition(): array
    {
        $campaign = Campaign::inRandomOrder()->first() ?? Campaign::factory()->create();
        $emailAccount = EmailAccount::where('workspace_id', $campaign->workspace_id)->where('is_active', true)->inRandomOrder()->first();

        return [
            'campaign_id' => $campaign->id,
            'email_account_id' => $emailAccount?->id ?? $campaign->email_account_id,
            'step_order' => CampaignStep::where('campaign_id', $campaign->id)->max('step_order') + 1,
            'name' => fake()->words(3, true),
            'subject' => fake()->sentence(),
            'body_html' => '<p>Hi {{first_name}},</p><p>' . fake()->paragraph() . '</p><p>Best regards,<br>The Team</p>',
            'body_text' => "Hi {{first_name}},\n\n" . fake()->paragraph() . "\n\nBest regards,\nThe Team",
            'delay_days' => fake()->numberBetween(0, 7),
            'delay_hours' => fake()->numberBetween(0, 23),
            'delay_minutes' => fake()->numberBetween(0, 59),
            'conditions' => null,
            'is_active' => true,
        ];
    }
}