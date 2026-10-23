<?php

namespace Database\Factories;

use App\Models\CampaignRecipient;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadList;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignRecipientFactory extends Factory
{
    protected $model = CampaignRecipient::class;

    public function definition(): array
    {
        $campaign = Campaign::inRandomOrder()->first() ?? Campaign::factory()->create();
        $lead = Lead::where('workspace_id', $campaign->workspace_id)->inRandomOrder()->first() ?? Lead::factory()->create(['workspace_id' => $campaign->workspace_id]);
        $leadList = $campaign->lead_list_id ? LeadList::find($campaign->lead_list_id) : null;

        return [
            'campaign_id' => $campaign->id,
            'lead_id' => $lead->id,
            'lead_list_id' => $leadList?->id,
            'status' => fake()->randomElement([CampaignRecipient::STATUS_PENDING, CampaignRecipient::STATUS_ACTIVE]),
            'current_step' => 0,
            'next_send_at' => fake()->optional()->dateTimeBetween('now', '+7 days'),
            'last_sent_at' => null,
            'replied_at' => null,
            'unsubscribed_at' => null,
            'bounced_at' => null,
            'emails_sent' => 0,
            'emails_opened' => 0,
            'links_clicked' => 0,
            'personalization_data' => null,
        ];
    }
}