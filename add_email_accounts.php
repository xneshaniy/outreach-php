<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

$workspace_id = 1;

$accounts = [
    ['name' => 'Jack',   'email' => 'jack@outreach.click'],
    ['name' => 'Arthur', 'email' => 'arthur@outreach.click'],
    ['name' => 'Leo',    'email' => 'leo@outreach.click'],
    ['name' => 'Harry',  'email' => 'harry@outreach.click'],
    ['name' => 'George', 'email' => 'george@outreach.click'],
];

$password   = 'Outreach@123$';
$encrypted  = Crypt::encryptString($password);
$now        = now()->toDateTimeString();

echo "Running seeder...\n";

foreach ($accounts as $account) {
    $exists = DB::table('email_accounts')
        ->where('email', $account['email'])
        ->whereNull('deleted_at')
        ->first();

    $data = [
        'workspace_id'       => $workspace_id,
        'user_id'            => 1, // associate with admin user
        'name'               => $account['name'],
        'email'              => $account['email'],
        'normalized_email'   => strtolower($account['email']),
        'provider'           => 'smtp',
        'host'               => 'smtp.hostinger.com',
        'port'               => 465,
        'encryption'         => 'ssl',
        'username'           => $account['email'],
        'password_encrypted' => $encrypted,
        'from_name'          => $account['name'],
        'reply_to'           => $account['email'],
        'daily_send_limit'   => 500,
        'sent_today'         => 0,
        'bounced_count'      => 0,
        'failed_count'       => 0,
        'is_active'          => 1,
        'is_default'         => 0,
        'track_opens'        => 1,
        'track_clicks'       => 1,
        'imap_host'          => 'imap.hostinger.com',
        'imap_port'          => 993,
        'imap_encryption'    => 'ssl',
        'imap_username'      => $account['email'],
        'warmup_enabled'     => 1,
        'warmup_daily_limit' => 5,
        'warmup_max_limit'   => 40,
        'warmup_step'        => 2,
        'warmup_sent_today'  => 0,
        'warmup_status'      => 'warming',
        'warmup_reputation_score' => 100,
        'updated_at'         => $now,
    ];

    if ($exists) {
        DB::table('email_accounts')->where('id', $exists->id)->update($data);
        echo "UPDATED : {$account['email']} (id={$exists->id})\n";
    } else {
        $data['created_at'] = $now;
        $id = DB::table('email_accounts')->insertGetId($data);
        echo "ADDED   : {$account['email']} (id={$id})\n";
    }
}

echo "All accounts seeded successfully!\n";
