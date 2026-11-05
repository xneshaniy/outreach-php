<?php

namespace App\Console\Commands;

use App\Services\HostingerMailService;
use Illuminate\Console\Command;

class SyncHostingerMailboxesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostinger:sync-mailboxes {--workspace=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch all mailboxes created in Hostinger via API and auto-connect them to the CRM';

    /**
     * Execute the console command.
     */
    public function handle(HostingerMailService $service): int
    {
        $this->info('Connecting to Hostinger Mail API...');

        $workspaceId = (int) $this->option('workspace');
        $res = $service->syncMailboxes($workspaceId);

        if (!$res['success']) {
            $this->error('Failed to sync from Hostinger: ' . ($res['error'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        $this->info("Sync completed! Total mailboxes in Hostinger: {$res['total']}, New added: {$res['added']}, Existing updated: {$res['updated']}");

        return Command::SUCCESS;
    }
}
