<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Leads: Add score_reasons for explainable scoring
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'score_reasons')) {
                $table->json('score_reasons')->nullable()->after('score');
            }
        });

        // 2. Email Accounts: Add deliverability and health tracking columns
        Schema::table('email_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('email_accounts', 'spf_status')) {
                $table->string('spf_status')->default('unknown')->after('is_active');
            }
            if (!Schema::hasColumn('email_accounts', 'dkim_status')) {
                $table->string('dkim_status')->default('unknown')->after('spf_status');
            }
            if (!Schema::hasColumn('email_accounts', 'dmarc_status')) {
                $table->string('dmarc_status')->default('unknown')->after('dkim_status');
            }
            if (!Schema::hasColumn('email_accounts', 'health_score')) {
                $table->unsignedTinyInteger('health_score')->default(100)->after('dmarc_status');
            }
            if (!Schema::hasColumn('email_accounts', 'last_health_check_at')) {
                $table->timestamp('last_health_check_at')->nullable()->after('health_score');
            }
            if (!Schema::hasColumn('email_accounts', 'limit_increase_requested')) {
                $table->boolean('limit_increase_requested')->default(false)->after('daily_limit');
            }
            if (!Schema::hasColumn('email_accounts', 'limit_increase_approved_by')) {
                $table->foreignId('limit_increase_approved_by')->nullable()->after('limit_increase_requested')->constrained('users')->nullOnDelete();
            }
        });

        // 3. Companies: Add AI research columns
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'ai_research')) {
                $table->json('ai_research')->nullable()->after('description');
            }
            if (!Schema::hasColumn('companies', 'last_researched_at')) {
                $table->timestamp('last_researched_at')->nullable()->after('ai_research');
            }
        });

        // 4. Automations table
        if (!Schema::hasTable('automations')) {
            Schema::create('automations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->string('trigger_event'); // lead_created, score_reached, reply_received
                $table->json('trigger_config')->nullable();
                $table->string('action_type'); // assign_campaign, create_task, notify_owner, manual_approval
                $table->json('action_config')->nullable();
                $table->boolean('requires_approval')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['workspace_id', 'is_active']);
            });
        }

        // 5. Automation execution logs table
        if (!Schema::hasTable('automation_logs')) {
            Schema::create('automation_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status')->default('executed'); // pending_approval, executed, rejected, failed
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->json('details')->nullable();
                $table->timestamps();

                $table->index(['workspace_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('automations');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['ai_research', 'last_researched_at']);
        });

        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropForeign(['limit_increase_approved_by']);
            $table->dropColumn([
                'spf_status',
                'dkim_status',
                'dmarc_status',
                'health_score',
                'last_health_check_at',
                'limit_increase_requested',
                'limit_increase_approved_by',
            ]);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['score_reasons']);
        });
    }
};
