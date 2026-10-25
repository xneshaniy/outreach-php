<?php

declare(strict_types=1);

/**
 * One-shot database queue drain for shared hosting.
 *
 * The scheduler calls App\Support\QueueDrain in-process. This script exists
 * for manual runs and for a direct cron fallback; it bootstraps the console
 * kernel and delegates to the same drain implementation.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('not found');
}

$crm = __DIR__;

require $crm.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require $crm.'/bootstrap/app.php';

$publicPath = is_dir(dirname($crm).'/public_html')
    ? dirname($crm).'/public_html'
    : $crm.'/public';

$app->usePublicPath($publicPath);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

App\Support\QueueDrain::run(100);
