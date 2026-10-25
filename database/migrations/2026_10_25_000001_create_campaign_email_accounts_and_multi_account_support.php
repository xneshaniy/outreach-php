<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pivot table for multiple email accounts per campaign
        if (!Schema::hasTable('campaign_email_accounts')) {
            Schema::create('campaign_email_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
                $table->foreignId('email_account_id')->constrained('email_accounts')->cascadeOnDelete();
                $table->unsignedInteger('allocation_count')->nullable(); // For account-based distribution
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->timestamps();

                $table->unique(['campaign_id', 'email_account_id']);
                $table->index(['campaign_id', 'sent_count']);
            });
        }

        // 2. Add distribution & scheduling columns to campaigns
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'distribution_strategy')) {
                $table->string('distribution_strategy', 50)->default('round_robin')->after('status'); // round_robin, capacity_based, account_based
            }
            if (!Schema::hasColumn('campaigns', 'start_date')) {
                $table->date('start_date')->nullable()->after('distribution_strategy');
            }
            if (!Schema::hasColumn('campaigns', 'start_time')) {
                $table->time('start_time')->nullable()->after('start_date');
            }
        });

        // 3. Add recipient-level tracking to campaign_recipients
        Schema::table('campaign_recipients', function (Blueprint $table) {
            if (!Schema::hasColumn('campaign_recipients', 'email_account_id')) {
                $table->foreignId('email_account_id')->nullable()->after('lead_list_id')->constrained('email_accounts')->nullOnDelete();
            }
            if (!Schema::hasColumn('campaign_recipients', 'retry_count')) {
                $table->unsignedInteger('retry_count')->default(0)->after('links_clicked');
            }
            if (!Schema::hasColumn('campaign_recipients', 'error_message')) {
                $table->text('error_message')->nullable()->after('retry_count');
            }
            if (!Schema::hasColumn('campaign_recipients', 'last_response')) {
                $table->text('last_response')->nullable()->after('error_message');
            }
        });

        // 4. Add bounce & failure counters to email_accounts
        Schema::table('email_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('email_accounts', 'bounced_count')) {
                $table->unsignedInteger('bounced_count')->default(0)->after('sent_today');
            }
            if (!Schema::hasColumn('email_accounts', 'failed_count')) {
                $table->unsignedInteger('failed_count')->default(0)->after('bounced_count');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_email_accounts');

        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'distribution_strategy')) {
                $table->dropColumn(['distribution_strategy', 'start_date', 'start_time']);
            }
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            if (Schema::hasColumn('campaign_recipients', 'email_account_id')) {
                $table->dropConstrainedForeignId('email_account_id');
                $table->dropColumn(['retry_count', 'error_message', 'last_response']);
            }
        });

        Schema::table('email_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('email_accounts', 'bounced_count')) {
                $table->dropColumn(['bounced_count', 'failed_count']);
            }
        });
    }
};
