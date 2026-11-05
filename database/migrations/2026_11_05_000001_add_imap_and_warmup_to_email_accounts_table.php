<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add IMAP configuration and Warm-up tracking columns to email_accounts
        Schema::table('email_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('email_accounts', 'imap_host')) {
                $table->string('imap_host')->nullable()->after('encryption');
                $table->integer('imap_port')->default(993)->after('imap_host');
                $table->string('imap_encryption')->default('ssl')->after('imap_port');
                $table->string('imap_username')->nullable()->after('imap_encryption');
            }

            if (!Schema::hasColumn('email_accounts', 'warmup_enabled')) {
                $table->boolean('warmup_enabled')->default(false)->after('track_clicks');
                $table->integer('warmup_daily_limit')->default(5)->after('warmup_enabled'); // starting emails per day
                $table->integer('warmup_max_limit')->default(40)->after('warmup_daily_limit'); // target emails per day
                $table->integer('warmup_step')->default(2)->after('warmup_max_limit'); // daily increase
                $table->integer('warmup_sent_today')->default(0)->after('warmup_step');
                $table->date('warmup_last_date')->nullable()->after('warmup_sent_today');
                $table->string('warmup_status')->default('idle')->after('warmup_last_date'); // idle, warming, paused, completed
                $table->integer('warmup_reputation_score')->default(100)->after('warmup_status');
            }
        });

        // 2. Create email_warmup_logs table for audit & deliverability insights
        if (!Schema::hasTable('email_warmup_logs')) {
            Schema::create('email_warmup_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('email_account_id')->constrained('email_accounts')->cascadeOnDelete();
                $table->string('sender_email');
                $table->string('recipient_email');
                $table->string('subject');
                $table->string('status')->default('sent'); // sent, replied, bounced, spam_saved
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['email_account_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_warmup_logs');

        Schema::table('email_accounts', function (Blueprint $table) {
            $cols = [
                'imap_host', 'imap_port', 'imap_encryption', 'imap_username',
                'warmup_enabled', 'warmup_daily_limit', 'warmup_max_limit',
                'warmup_step', 'warmup_sent_today', 'warmup_last_date',
                'warmup_status', 'warmup_reputation_score'
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('email_accounts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
