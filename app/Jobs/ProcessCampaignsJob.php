<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Message;
use App\Services\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCampaignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(
        public ?int $campaignId = null,
        public int $batchSize = 50
    ) {}

    public function handle(CampaignService $campaignService): void
    {
        $query = Campaign::whereIn('status', [Campaign::STATUS_ACTIVE, Campaign::STATUS_SCHEDULED]);

        if ($this->campaignId) {
            $query->where('id', $this->campaignId);
        }

        // Process scheduled campaigns that are due
        $scheduledCampaigns = (clone $query)
            ->where('status', Campaign::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($scheduledCampaigns as $campaign) {
            try {
                $campaignService->activateCampaign($campaign);
                Log::info("Activated scheduled campaign {$campaign->id}");
            } catch (\Exception $e) {
                Log::error("Failed to activate campaign {$campaign->id}", [
                    'error' => $e->getMessage(),
                ]);
                $campaign->update(['status' => Campaign::STATUS_FAILED]);
            }
        }

        // Process active campaigns
        $activeCampaigns = (clone $query)
            ->where('status', Campaign::STATUS_ACTIVE)
            ->get();

        foreach ($activeCampaigns as $campaign) {
            try {
                $sent = $campaignService->processDueEmails($campaign, $this->batchSize);
                Log::info("Processed campaign {$campaign->id}, sent {$sent} emails");
            } catch (\Exception $e) {
                Log::error("Failed to process campaign {$campaign->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Check for completed campaigns
        $this->checkCompletedCampaigns();
    }

    private function checkCompletedCampaigns(): void
    {
        $campaigns = Campaign::where('status', Campaign::STATUS_ACTIVE)
            ->whereDoesntHave('recipients', function ($q) {
                $q->whereIn('status', [CampaignRecipient::STATUS_PENDING, CampaignRecipient::STATUS_ACTIVE]);
            })
            ->get();

        foreach ($campaigns as $campaign) {
            $campaign->complete();
            Log::info("Completed campaign {$campaign->id}");
        }
    }
}