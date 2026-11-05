<?php

namespace App\Console\Commands;

use App\Services\EmailWarmupService;
use Illuminate\Console\Command;

class RunEmailWarmupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:warmup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute scheduled warm-up sending rounds across enabled email accounts to build sender reputation';

    /**
     * Execute the console command.
     */
    public function handle(EmailWarmupService $warmupService): int
    {
        $this->info('Starting scheduled Email Warmup round...');

        $stats = $warmupService->runDailyWarmup();

        $this->info("Warmup complete. Accounts processed: {$stats['accounts_processed']}, Emails sent: {$stats['emails_sent']}, Failed: {$stats['failed']}");

        return Command::SUCCESS;
    }
}
