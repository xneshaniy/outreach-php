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

Artisan::command('queue:drain {--max=100 : Maximum number of jobs to process}', function () {
    $max = (int) $this->option('max');
    $this->info("Draining database queue (max {$max} jobs)...");
    \App\Support\QueueDrain::run($max);
})->purpose('Drain database queue jobs once');

Artisan::command('user:info {email}', function ($email) {
    $user = \App\Models\User::where('email', $email)->first();
    if (! $user) {
        $this->error("User {$email} not found!");
        return 1;
    }
    $this->info("User: {$user->id} | {$user->email} | {$user->name}");
    $this->info("Current workspace: " . ($user->currentWorkspace ? $user->currentWorkspace->name : 'NONE'));
    $this->info("Workspaces count: " . $user->workspaces()->count());
    foreach ($user->workspaces as $w) {
        $this->info("- Workspace: {$w->id} | {$w->name} | active={$w->is_active}");
    }
    return 0;
})->purpose('Show user info and workspaces');

Artisan::command('user:password {email} {password}', function ($email, $password) {
    $user = \App\Models\User::where('email', $email)->first();
    if (! $user) {
        $this->error("User {$email} not found!");
        return 1;
    }
    $user->password = \Illuminate\Support\Facades\Hash::make($password);
    $user->save();
    $this->info("Password updated successfully for {$email}");
    return 0;
})->purpose('Set user password');

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

// Process campaign email batches (send due emails across all active campaigns).
Schedule::command('campaigns:send-batch')->everyFiveMinutes()->withoutOverlapping();

// Sends task reminder emails.
Schedule::job(new SendReminderEmailsJob)->everyFiveMinutes()->withoutOverlapping();

// Prunes old messages, logs and import files.
Schedule::job(new CleanupJob)->dailyAt('02:00');

/*
 * Shared hosting has no long-running queue worker. Drain the database queue
 * from the same once-per-minute cron entry that runs the scheduler.
 *
 * The drain runs in-process instead of spawning "queue:work" or a child
 * script: this host's PHP CLI segfaults while tearing down the queue worker
 * singleton at shutdown, and Laravel redirects spawned event output to
 * /dev/null, which hides the real work. QueueDrain exits through
 * pcntl_exec('/bin/true') after the jobs are committed, so the scheduler
 * process never reaches the crashing shutdown code. Keep this event last:
 * replacing the process ends the schedule run.
 */
Schedule::call([\App\Support\QueueDrain::class, 'run'])
    ->everyMinute()
    ->description('Drain database queue');
