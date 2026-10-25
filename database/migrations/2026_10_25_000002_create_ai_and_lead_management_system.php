<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade leads table with additional fields for advanced management & scoring
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'website')) {
                $table->string('website')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('leads', 'industry')) {
                $table->string('industry')->nullable()->after('website');
            }
            if (!Schema::hasColumn('leads', 'score')) {
                $table->integer('score')->default(0)->after('status')->index();
            }
            if (!Schema::hasColumn('leads', 'category')) {
                $table->string('category')->default('cold')->after('score')->index(); // cold, qualified, interested, hot, converted, unqualified
            }
            if (!Schema::hasColumn('leads', 'pipeline_stage')) {
                $table->string('pipeline_stage')->default('new_lead')->after('category')->index();
            }
            if (!Schema::hasColumn('leads', 'email_verified')) {
                $table->boolean('email_verified')->default(false)->after('normalized_email');
            }
            if (!Schema::hasColumn('leads', 'email_verification_status')) {
                $table->string('email_verification_status')->nullable()->after('email_verified'); // valid, invalid, catch_all, unknown
            }
            if (!Schema::hasColumn('leads', 'assigned_campaign_id')) {
                $table->foreignId('assigned_campaign_id')->nullable()->constrained('campaigns')->nullOnDelete()->after('owner_id');
            }
        });

        // 2. AI Provider Configurations table
        if (!Schema::hasTable('ai_configurations')) {
            Schema::create('ai_configurations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->string('provider')->default('openai'); // openai, gemini, anthropic, custom
                $table->text('api_key_encrypted')->nullable();
                $table->string('model')->default('gpt-4o-mini');
                $table->boolean('is_active')->default(true);
                $table->integer('daily_token_limit')->default(100000);
                $table->integer('tokens_used_today')->default(0);
                $table->date('last_usage_date')->nullable();
                $table->timestamp('last_tested_at')->nullable();
                $table->string('last_test_status')->nullable();
                $table->text('last_test_message')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();

                $table->index(['workspace_id', 'is_active']);
            });
        }

        // 3. Lead Research table for AI-generated company/lead intelligence
        if (!Schema::hasTable('lead_researches')) {
            Schema::create('lead_researches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('company_overview')->nullable();
                $table->string('industry')->nullable();
                $table->text('potential_needs')->nullable();
                $table->text('relevant_services')->nullable();
                $table->text('outreach_angle')->nullable();
                $table->text('suggested_subject')->nullable();
                $table->text('suggested_opening')->nullable();
                $table->json('raw_analysis')->nullable();
                $table->boolean('verified')->default(false);
                $table->timestamps();

                $table->index(['workspace_id', 'lead_id']);
            });
        }

        // 4. Lead Scoring Rules configuration
        if (!Schema::hasTable('lead_scoring_rules')) {
            Schema::create('lead_scoring_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('criteria_key'); // valid_email, relevant_industry, relevant_job_title, website_available, positive_reply, meeting_requested, etc.
                $table->integer('points')->default(10);
                $table->boolean('is_active')->default(true);
                $table->json('conditions')->nullable();
                $table->timestamps();

                $table->index(['workspace_id', 'is_active']);
            });
        }

        // 5. Internal Notes & Reminders on Leads
        if (!Schema::hasTable('lead_notes')) {
            Schema::create('lead_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('content');
                $table->timestamp('remind_at')->nullable();
                $table->boolean('reminder_sent')->default(false);
                $table->timestamps();

                $table->index(['workspace_id', 'lead_id']);
            });
        }

        // 6. User Notifications table
        if (!Schema::hasTable('crm_notifications')) {
            Schema::create('crm_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type'); // positive_reply, meeting_requested, interested_lead, campaign_completed, email_disconnected, campaign_failure, limit_reached, unsubscribe, lead_activity
                $table->string('title');
                $table->text('message');
                $table->json('data')->nullable();
                $table->timestamp('read_at')->nullable()->index();
                $table->timestamps();

                $table->index(['workspace_id', 'read_at']);
            });
        }

        // 7. Add classification fields to messages table for AI reply classification
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'reply_classification')) {
                $table->string('reply_classification')->nullable()->after('status')->index(); // interested, positive, meeting_requested, not_interested, unsubscribe, out_of_office, wrong_person, referral, question, other
            }
            if (!Schema::hasColumn('messages', 'classification_reason')) {
                $table->text('classification_reason')->nullable()->after('reply_classification');
            }
            if (!Schema::hasColumn('messages', 'return_date')) {
                $table->date('return_date')->nullable()->after('classification_reason');
            }
            if (!Schema::hasColumn('messages', 'classification_confidence')) {
                $table->float('classification_confidence')->nullable()->after('return_date');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_notifications');
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('lead_scoring_rules');
        Schema::dropIfExists('lead_researches');
        Schema::dropIfExists('ai_configurations');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['reply_classification', 'classification_reason', 'return_date', 'classification_confidence']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'website', 'industry', 'score', 'category', 'pipeline_stage',
                'email_verified', 'email_verification_status', 'assigned_campaign_id'
            ]);
        });
    }
};
