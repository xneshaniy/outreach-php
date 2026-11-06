<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. LinkedIn Job Searches & Discovered Jobs
        if (!Schema::hasTable('linkedin_job_searches')) {
            Schema::create('linkedin_job_searches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('query');
                $table->string('location')->nullable();
                $table->string('job_type')->nullable(); // full_time, contract, remote, etc.
                $table->string('experience_level')->nullable();
                $table->unsignedInteger('results_count')->default(0);
                $table->timestamps();
            });
        }

        // 2. LinkedIn Job Applications & AI Tailoring
        if (!Schema::hasTable('linkedin_job_applications')) {
            Schema::create('linkedin_job_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('search_id')->nullable()->constrained('linkedin_job_searches')->nullOnDelete();
                $table->string('job_title');
                $table->string('company');
                $table->string('location')->nullable();
                $table->string('job_url')->nullable();
                $table->string('salary_range')->nullable();
                $table->text('job_description')->nullable();
                $table->json('resume_customization')->nullable(); // tailored summary, bullets, skills
                $table->json('screening_answers')->nullable(); // Q&A pairs for application
                $table->string('status')->default('discovered'); // discovered, tailored, applied, interviewing, offered, rejected
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();

                $table->index(['workspace_id', 'status']);
            });
        }

        // 3. LinkedIn Network Invites & Open Networker (LION)
        if (!Schema::hasTable('linkedin_network_invites')) {
            Schema::create('linkedin_network_invites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('full_name');
                $table->string('headline')->nullable();
                $table->string('company')->nullable();
                $table->string('profile_url');
                $table->text('invite_note')->nullable();
                $table->string('connection_type')->default('lion'); // lion, recruiter, founder, prospect
                $table->string('status')->default('drafted'); // drafted, sent, accepted, replied
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['workspace_id', 'status']);
            });
        }

        // 4. LinkedIn Inbound Messages & AI Manager
        if (!Schema::hasTable('linkedin_inbound_messages')) {
            Schema::create('linkedin_inbound_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('sender_name');
                $table->string('sender_profile')->nullable();
                $table->text('message_text');
                $table->string('classification')->default('networking'); // job_pitch, recruiter_outreach, lead_inquiry, spam, networking
                $table->text('ai_suggested_reply')->nullable();
                $table->string('status')->default('unread'); // unread, drafted, sent, archived
                $table->timestamps();

                $table->index(['workspace_id', 'status']);
            });
        }

        // 5. AgentSDR Multi-Provider Enrichment Providers
        if (!Schema::hasTable('sdr_enrichment_providers')) {
            Schema::create('sdr_enrichment_providers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->string('provider_name'); // apollo, hunter, lusha, findymail, snov, zerobounce, rocketreach, etc.
                $table->text('api_key')->nullable();
                $table->boolean('is_active')->default(false);
                $table->integer('priority')->default(1);
                $table->json('settings')->nullable();
                $table->timestamps();

                $table->unique(['workspace_id', 'provider_name']);
            });
        }

        // 6. AgentSDR Human-in-the-Loop Approval Queue
        if (!Schema::hasTable('sdr_approval_queues')) {
            Schema::create('sdr_approval_queues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('channel'); // email, linkedin, whatsapp
                $table->string('action_type'); // outreach_first_touch, follow_up, reply_draft, connection_request
                $table->string('recipient_name')->nullable();
                $table->string('recipient_contact'); // email, linkedin profile, or phone
                $table->string('subject')->nullable();
                $table->text('proposed_content');
                $table->string('ai_intent')->nullable(); // interested, meeting_request, question, follow_up
                $table->json('context_data')->nullable(); // grounded company facts / rationale
                $table->string('status')->default('pending'); // pending, approved, rejected, sent
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['workspace_id', 'status', 'channel']);
            });
        }

        // 7. AgentSDR Dynamic AI Table Columns / Formulas
        if (!Schema::hasTable('sdr_ai_columns')) {
            Schema::create('sdr_ai_columns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('lead_list_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->text('prompt_template');
                $table->string('model')->default('gemini-1.5-pro');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sdr_ai_columns');
        Schema::dropIfExists('sdr_approval_queues');
        Schema::dropIfExists('sdr_enrichment_providers');
        Schema::dropIfExists('linkedin_inbound_messages');
        Schema::dropIfExists('linkedin_network_invites');
        Schema::dropIfExists('linkedin_job_applications');
        Schema::dropIfExists('linkedin_job_searches');
    }
};
