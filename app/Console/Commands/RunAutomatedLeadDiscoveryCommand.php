<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\AutomatedLeadDiscoveryService;
use Illuminate\Console\Command;

class RunAutomatedLeadDiscoveryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:auto-discover 
                            {--country=France : Target country} 
                            {--industry=IT Services : Target business industry or niche}
                            {--city=Paris : Target city}
                            {--limit=10 : Maximum leads to discover}
                            {--enrich : Perform deep website contact page scraping}
                            {--workspace=1 : Workspace ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find relevant businesses on Google Maps, detect language/country, and save qualified leads into the CRM';

    /**
     * Execute the console command.
     */
    public function handle(AutomatedLeadDiscoveryService $discoveryService): int
    {
        $country = $this->option('country');
        $industry = $this->option('industry');
        $city = $this->option('city');
        $limit = (int) $this->option('limit');
        $workspaceId = (int) $this->option('workspace');

        $workspace = Workspace::find($workspaceId);
        if (!$workspace) {
            $this->error("Workspace #{$workspaceId} not found.");
            return Command::FAILURE;
        }

        $this->info("Initiating automated lead discovery for {$industry} in {$city}, {$country}...");

        $enrich = (bool) $this->option('enrich');

        $res = $discoveryService->discoverAndSaveLeads(
            $workspace,
            $country,
            $industry,
            $city,
            null,
            $limit,
            $enrich,
            true
        );

        if (!$res['success']) {
            $this->error("Lead discovery encountered an error.");
            return Command::FAILURE;
        }

        $this->info("Completed! Total discovered: {$res['total_found']}, Newly saved to CRM: {$res['new_leads_saved']}, Duplicates skipped: {$res['duplicates_skipped']}");

        return Command::SUCCESS;
    }
}
