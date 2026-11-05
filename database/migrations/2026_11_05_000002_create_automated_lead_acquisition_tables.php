<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend leads table with automated discovery, country, language, quality and pipeline fields
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'country_code')) {
                $table->string('country_code', 5)->nullable()->after('country')->index();
            }
            if (!Schema::hasColumn('leads', 'detected_language')) {
                $table->string('detected_language', 10)->default('en')->after('country_code')->index();
            }
            if (!Schema::hasColumn('leads', 'company_name')) {
                $table->string('company_name')->nullable()->after('company_id')->index();
            }
            if (!Schema::hasColumn('leads', 'website')) {
                $table->string('website')->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('leads', 'business_category')) {
                $table->string('business_category')->nullable()->after('website');
            }
            if (!Schema::hasColumn('leads', 'lead_quality_score')) {
                $table->integer('lead_quality_score')->default(50)->after('status')->index();
            }
            if (!Schema::hasColumn('leads', 'verification_status')) {
                $table->string('verification_status')->default('unverified')->after('lead_quality_score'); // verified, plausible, unverified, invalid
            }
            if (!Schema::hasColumn('leads', 'pipeline_stage')) {
                $table->string('pipeline_stage')->default('new_lead')->after('verification_status')->index();
                // Stage keys:
                // new_lead, qualified, email_ready, email_sent, open_recorded, replied,
                // interested, follow_up_required, converted, not_interested, unsubscribed
            }
            if (!Schema::hasColumn('leads', 'source_url')) {
                $table->text('source_url')->nullable()->after('source');
            }
            if (!Schema::hasColumn('leads', 'discovery_date')) {
                $table->timestamp('discovery_date')->nullable()->after('source_url');
            }
            if (!Schema::hasColumn('leads', 'approval_status')) {
                $table->string('approval_status')->default('auto_approved')->after('pipeline_stage'); // pending_review, approved, rejected, auto_approved
            }
            if (!Schema::hasColumn('leads', 'recorded_opens_count')) {
                $table->integer('recorded_opens_count')->default(0)->after('last_contacted_at');
            }
            if (!Schema::hasColumn('leads', 'first_opened_at')) {
                $table->timestamp('first_opened_at')->nullable()->after('recorded_opens_count');
            }
            if (!Schema::hasColumn('leads', 'last_opened_at')) {
                $table->timestamp('last_opened_at')->nullable()->after('first_opened_at');
            }
            if (!Schema::hasColumn('leads', 'reply_status')) {
                $table->string('reply_status')->nullable()->after('last_opened_at'); // interested, not_interested, follow_up, out_of_office, unsubscribe, unknown
            }
            if (!Schema::hasColumn('leads', 'last_replied_at')) {
                $table->timestamp('last_replied_at')->nullable()->after('reply_status');
            }
        });

        // 2. Automated Lead Discovery Schedules Table
        if (!Schema::hasTable('lead_discovery_schedules')) {
            Schema::create('lead_discovery_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('target_country')->default('France'); // France, Germany, etc.
                $table->string('target_city')->nullable();
                $table->string('target_industry'); // e.g. "Dentists", "IT Services", "Logistics"
                $table->string('keywords')->nullable();
                $table->integer('max_results_per_run')->default(20);
                $table->boolean('enrich_websites')->default(true);
                $table->boolean('auto_approve')->default(true);
                $table->boolean('is_active')->default(true);
                $table->string('frequency')->default('daily'); // daily, twice_daily, hourly
                $table->timestamp('last_run_at')->nullable();
                $table->string('last_status')->nullable();
                $table->text('last_error')->nullable();
                $table->integer('total_leads_found')->default(0);
                $table->timestamps();

                $table->index(['workspace_id', 'is_active']);
            });
        }

        // 3. Lead Discovery Logs Table
        if (!Schema::hasTable('lead_discovery_logs')) {
            Schema::create('lead_discovery_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('schedule_id')->nullable()->constrained('lead_discovery_schedules')->nullOnDelete();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->string('query');
                $table->string('country');
                $table->string('city')->nullable();
                $table->integer('results_count')->default(0);
                $table->integer('new_leads_saved')->default(0);
                $table->integer('duplicates_skipped')->default(0);
                $table->string('status')->default('success'); // success, partial, failed
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['workspace_id', 'created_at']);
            });
        }

        // 4. Inbound Email Messages / Replies Tracking Table
        if (!Schema::hasTable('inbound_email_replies')) {
            Schema::create('inbound_email_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
                $table->string('message_id')->nullable()->index(); // RFC Message-ID
                $table->string('in_reply_to')->nullable()->index();
                $table->string('sender_email')->index();
                $table->string('sender_name')->nullable();
                $table->string('recipient_email')->index();
                $table->string('subject');
                $table->text('body_text')->nullable();
                $table->longText('body_html')->nullable();
                $table->string('sentiment')->default('unknown'); // interested, not_interested, follow_up, out_of_office, unsubscribe, unknown
                $table->boolean('is_read')->default(false);
                $table->boolean('notification_sent')->default(false);
                $table->timestamp('received_at')->nullable();
                $table->timestamps();

                $table->index(['workspace_id', 'is_read']);
                $table->index(['workspace_id', 'sentiment']);
                $table->index(['workspace_id', 'received_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_email_replies');
        Schema::dropIfExists('lead_discovery_logs');
        Schema::dropIfExists('lead_discovery_schedules');

        Schema::table('leads', function (Blueprint $table) {
            $cols = [
                'country_code', 'detected_language', 'company_name', 'website',
                'business_category', 'lead_quality_score', 'verification_status',
                'pipeline_stage', 'source_url', 'discovery_date', 'approval_status',
                'recorded_opens_count', 'first_opened_at', 'last_opened_at',
                'reply_status', 'last_replied_at'
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('leads', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
