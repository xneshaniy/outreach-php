<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Support\Facades\Log;

class ProcessCampaignBatchCommand extends Command
{
    protected $signature = 'campaigns:send-batch {--campaign= : Specific campaign ID to run} {--batch=50 : Max emails per campaign}';
    protected $description = 'Process and send due campaign emails across configured sending accounts';

    public function handle(CampaignService $campaignService): int
    {
        $campaignId = $this->option('campaign');
        $batchSize = (int) $this->option('batch');

        $this->info('Starting campaign email batch processor...');

        // 1. Activate scheduled campaigns that are due
        $scheduled = Campaign::where('status', Campaign::STATUS_SCHEDULED)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now());
            })
            ->get();

        foreach ($scheduled as $camp) {
            try {
                $campaignService->activateCampaign($camp);
                $this->info("Activated scheduled campaign #{$camp->id} ({$camp->name})");
            } catch (\Exception $e) {
                $this->error("Failed to activate campaign #{$camp->id}: " . $e->getMessage());
            }
        }

        // 2. Process active campaigns
        $query = Campaign::where('status', Campaign::STATUS_ACTIVE);
        if ($campaignId) {
            $query->where('id', $campaignId);
        }

        $campaigns = $query->get();
        if ($campaigns->isEmpty()) {
            $this->info('No active campaigns to process.');
            return Command::SUCCESS;
        }

        $totalSent = 0;
        foreach ($campaigns as $camp) {
            $this->line("Processing campaign #{$camp->id}: {$camp->name} (Strategy: {$camp->distribution_strategy})...");

            try {
                $sent = $campaignService->processDueEmails($camp, $batchSize);
                $totalSent += $sent;
                $this->info("Sent {$sent} emails for campaign #{$camp->id}.");
            } catch (\Exception $e) {
                $this->error("Error processing campaign #{$camp->id}: " . $e->getMessage());
                Log::error("ProcessCampaignBatchCommand error for campaign #{$camp->id}", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Batch process finished. Total emails processed: {$totalSent}");
        return Command::SUCCESS;
    }
}
