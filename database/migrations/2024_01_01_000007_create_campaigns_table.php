<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('email_account_id')->nullable()->constrained('email_accounts')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, scheduled, active, paused, completed, cancelled
            $table->string('timezone', 50)->default('UTC');
            $table->json('sending_window')->nullable(); // {start: "09:00", end: "17:00", days: [1,2,3,4,5]}
            $table->integer('daily_send_limit')->default(200);
            $table->integer('delay_between_emails')->default(60); // seconds
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'scheduled_at']);
        });

        Schema::create('campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_account_id')->nullable()->constrained('email_accounts')->nullOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('name');
            $table->string('subject');
            $table->longText('body_html');
            $table->longText('body_text')->nullable();
            $table->unsignedInteger('delay_days')->default(0);
            $table->unsignedInteger('delay_hours')->default(0);
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->json('conditions')->nullable(); // Conditional logic
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['campaign_id', 'step_order']);
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_list_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending'); // pending, active, completed, bounced, unsubscribed, replied, failed, suppressed
            $table->unsignedInteger('current_step')->default(0);
            $table->timestamp('next_send_at')->nullable()->index();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->integer('emails_sent')->default(0);
            $table->integer('emails_opened')->default(0);
            $table->integer('links_clicked')->default(0);
            $table->json('personalization_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['campaign_id', 'lead_id']);
            $table->index(['campaign_id', 'status']);
            $table->index(['campaign_id', 'next_send_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaign_steps');
        Schema::dropIfExists('campaigns');
    }
};