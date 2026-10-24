<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CleanupJob;
use App\Jobs\ProcessCampaignsJob;
use App\Jobs\SendReminderEmailsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Laravel 12 loads the scheduler from this file. An app/Console/Kernel.php is
| NOT loaded any more, so every schedule must be registered here or it will
| silently never run.
|
| Requires the cron entry:
|   * * * * * cd /path/to/repository && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Moves scheduled campaigns forward and queues their emails.
Schedule::job(new ProcessCampaignsJob)->everyMinute()->withoutOverlapping();

// Sends task reminder emails.
Schedule::job(new SendReminderEmailsJob)->everyFiveMinutes()->withoutOverlapping();

// Prunes old messages, logs and import files.
Schedule::job(new CleanupJob)->dailyAt('02:00');
