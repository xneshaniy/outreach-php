<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Owner of this email account
            $table->string('name'); // Display name
            $table->string('email');
            $table->string('normalized_email')->index();
            $table->string('provider'); // smtp, gmail, outlook, custom
            $table->string('host');
            $table->integer('port')->default(587);
            $table->string('encryption')->default('tls'); // tls, ssl, none
            $table->string('username');
            $table->text('password_encrypted'); // Encrypted password
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('signature_html')->nullable();
            $table->string('signature_text')->nullable();
            $table->integer('daily_send_limit')->default(200);
            $table->integer('sent_today')->default(0);
            $table->date('last_sent_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('track_opens')->default(true);
            $table->boolean('track_clicks')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status')->nullable(); // success, failed
            $table->text('last_test_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['workspace_id', 'email']);
            $table->index(['workspace_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};