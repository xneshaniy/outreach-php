<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade users table with limits and suspension reasons
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'daily_email_limit')) {
                $table->integer('daily_email_limit')->default(500)->after('is_super_admin');
            }
            if (!Schema::hasColumn('users', 'monthly_token_budget')) {
                $table->decimal('monthly_token_budget', 10, 2)->default(50.00)->after('daily_email_limit');
            }
            if (!Schema::hasColumn('users', 'max_campaigns')) {
                $table->integer('max_campaigns')->default(10)->after('monthly_token_budget');
            }
            if (!Schema::hasColumn('users', 'suspension_reason')) {
                $table->string('suspension_reason')->nullable()->after('is_active');
            }
        });

        // 2. Invitations table
        if (!Schema::hasTable('invitations')) {
            Schema::create('invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
                $table->string('email')->index();
                $table->string('role')->default('user');
                $table->string('token_hash', 64)->unique();
                $table->string('status', 20)->default('pending')->index(); // pending, accepted, expired, revoked
                $table->foreignId('invited_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamps();
            });
        }

        // 3. Admin settings table (key-value store)
        if (!Schema::hasTable('admin_settings')) {
            Schema::create('admin_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        // 4. Token usage records table
        if (!Schema::hasTable('token_usage_records')) {
            Schema::create('token_usage_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
                $table->string('token_type', 30)->default('ai')->index(); // ai, api
                $table->string('provider', 50)->default('openai')->index();
                $table->string('model', 80)->nullable();
                $table->integer('input_tokens')->default(0);
                $table->integer('output_tokens')->default(0);
                $table->integer('cached_tokens')->default(0);
                $table->integer('total_tokens')->default(0);
                $table->decimal('estimated_cost', 10, 6)->default(0);
                $table->decimal('actual_cost', 10, 6)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['created_at', 'provider']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('token_usage_records');
        Schema::dropIfExists('admin_settings');
        Schema::dropIfExists('invitations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'daily_email_limit',
                'monthly_token_budget',
                'max_campaigns',
                'suspension_reason',
            ]);
        });
    }
};
