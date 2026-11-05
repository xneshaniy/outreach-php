<?php

namespace App\Console\Commands;

use App\Services\InboundReplySyncService;
use Illuminate\Console\Command;

class SyncInboundRepliesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:sync-replies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll connected Hostinger mailboxes via IMAP/API for incoming prospect replies and trigger CRM alerts';

    /**
     * Execute the console command.
     */
    public function handle(InboundReplySyncService $syncService): int
    {
        $this->info('Checking inboxes for new prospect replies...');

        $stats = $syncService->syncAllAccounts();

        $this->info("Completed reply check. Accounts scanned: {$stats['accounts_checked']}, New replies saved: {$stats['replies_found']}, Errors: {$stats['errors']}");

        return Command::SUCCESS;
    }
}
