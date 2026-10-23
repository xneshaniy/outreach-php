<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Process active campaigns every minute
        $schedule->job(new \App\Jobs\ProcessCampaignsJob())->everyMinute();

        // Send task reminders every 5 minutes
        $schedule->job(new \App\Jobs\SendReminderEmailsJob())->everyFiveMinutes();

        // Daily cleanup at 2 AM
        $schedule->job(new \App\Jobs\CleanupJob())->dailyAt('02:00');

        // Reset daily send counters at midnight (handled by EmailAccount model automatically)
        // But we can also schedule a cleanup for stale data
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}