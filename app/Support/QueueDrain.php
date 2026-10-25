<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;

/**
 * One-shot database queue drain for shared hosting.
 *
 * The host's PHP CLI segfaults while the Artisan console application tears
 * down the queue worker singleton. The scheduler therefore calls this class
 * in-process and replaces the process with /bin/true afterwards, which skips
 * the destructors that crash. All jobs are committed before that happens.
 */
class QueueDrain
{
    /**
     * Process due jobs once.
     *
     * The explicit queue list is required because campaign emails are
     * dispatched to the "emails" queue; "default" carries imports, reminders
     * and cleanup jobs.
     */
    public static function run(int $maxJobs = 100): void
    {
        $app = app();

        /** @var Worker $worker */
        $worker = $app->make('queue.worker');

        $worker->setName('crm-drain')
            ->setCache($app->make(Cache::class));

        $connection = $app->make('queue')->connection('database');

        $pending = static fn (): int => $connection->size('emails') + $connection->size('default');

        $options = new WorkerOptions(
            name: 'crm-drain',
            backoff: 0,
            memory: 128,
            timeout: 0,
            sleep: 0,
            maxTries: 1,
            force: false,
            stopWhenEmpty: true,
            maxJobs: $maxJobs,
            maxTime: 0,
            rest: 0,
            stopWhenEmptyFor: 0,
        );

        $processed = 0;

        while ($processed < $maxJobs) {
            $before = $pending();

            if ($before === 0) {
                break;
            }

            $worker->runNextJob('database', 'emails,default', $options);

            // A successful job is deleted, so the count must fall. If it did
            // not, the remaining jobs are reserved elsewhere; stop instead of
            // spinning until max-jobs is reached.
            if ($pending() >= $before) {
                break;
            }

            $processed++;
        }

        echo "Processed {$processed} job(s).\n";

        if (function_exists('ob_flush')) {
            @ob_flush();
        }

        @flush();

        /*
         * This host segfaults in PHP's shutdown after a CLI job. Replacing the
         * process with /bin/true exits with status 0 and skips the destructors
         * that crash; the jobs have already been committed and acknowledged.
         */
        if (function_exists('pcntl_exec')) {
            @pcntl_exec('/bin/true');
        }
    }
}
