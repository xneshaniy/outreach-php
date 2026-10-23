<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_step_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_recipient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_id')->nullable()->index(); // Message-ID header
            $table->string('in_reply_to')->nullable();
            $table->string('references')->nullable();
            $table->string('subject');
            $table->longText('body_html');
            $table->longText('body_text')->nullable();
            $table->json('headers')->nullable();
            $table->json('attachments')->nullable();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->json('to_emails'); // Array of {email, name, type: to/cc/bcc}
            $table->string('status')->default('queued'); // queued, processing, sent, delivered, bounced, failed, opened, clicked, replied
            $table->string('provider')->nullable(); // smtp, sendgrid, mailgun, etc.
            $table->string('provider_message_id')->nullable();
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'campaign_id']);
            $table->index(['workspace_id', 'lead_id']);
            $table->index(['workspace_id', 'sent_at']);
        });

        Schema::create('message_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('event_type'); // sent, delivered, bounced, opened, clicked, complained, unsubscribed
            $table->json('event_data')->nullable();
            $table->string('provider')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['message_id', 'event_type']);
            $table->index(['workspace_id', 'occurred_at']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('status')->default('open'); // open, closed, archived
            $table->timestamp('last_message_at')->nullable()->index();
            $table->integer('message_count')->default(0);
            $table->integer('unread_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'lead_id']);
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('type')->default('to'); // to, cc, bcc
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('message_events');
        Schema::dropIfExists('messages');
    }
};