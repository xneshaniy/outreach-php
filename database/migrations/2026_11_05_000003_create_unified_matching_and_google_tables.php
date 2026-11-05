<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance companies table
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'email')) {
                $table->string('email')->nullable()->after('name');
            }
            if (!Schema::hasColumn('companies', 'company_emails')) {
                $table->json('company_emails')->nullable()->after('email');
            }
            if (!Schema::hasColumn('companies', 'enrichment_status')) {
                $table->string('enrichment_status')->default('pending')->after('description');
            }
            if (!Schema::hasColumn('companies', 'enrichment_source')) {
                $table->string('enrichment_source')->nullable()->after('enrichment_status');
            }
            if (!Schema::hasColumn('companies', 'verification_status')) {
                $table->string('verification_status')->default('unverified')->after('enrichment_source');
            }
            if (!Schema::hasColumn('companies', 'source_url')) {
                $table->string('source_url', 1000)->nullable()->after('verification_status');
            }
        });

        // 2. Enhance leads table
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'company_match_status')) {
                $table->string('company_match_status')->default('unlinked')->after('company_id');
            }
            if (!Schema::hasColumn('leads', 'contact_emails')) {
                $table->json('contact_emails')->nullable()->after('normalized_email');
            }
            if (!Schema::hasColumn('leads', 'enrichment_source')) {
                $table->string('enrichment_source')->nullable()->after('verification_status');
            }
            if (!Schema::hasColumn('leads', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('enrichment_source');
            }
        });

        // 3. Create google_integrations table
        if (!Schema::hasTable('google_integrations')) {
            Schema::create('google_integrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('google_account_id')->nullable();
                $table->string('google_email');
                $table->string('google_name')->nullable();
                $table->string('google_avatar', 500)->nullable();
                $table->json('scopes')->nullable();
                $table->text('access_token');
                $table->text('refresh_token')->nullable();
                $table->timestamp('token_expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->index(['workspace_id', 'is_active']);
                $table->index(['workspace_id', 'google_email']);
            });
        }

        // 4. Create google_sheet_syncs table
        if (!Schema::hasTable('google_sheet_syncs')) {
            Schema::create('google_sheet_syncs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('google_integration_id')->nullable()->constrained('google_integrations')->nullOnDelete();
                $table->string('spreadsheet_id');
                $table->string('spreadsheet_name')->nullable();
                $table->string('worksheet_name')->nullable();
                $table->json('column_mapping')->nullable();
                $table->string('sync_mode')->default('manual'); // manual, hourly, daily, weekly
                $table->timestamp('last_synced_at')->nullable();
                $table->string('last_status')->default('pending');
                $table->text('last_error')->nullable();
                $table->unsignedInteger('records_imported')->default(0);
                $table->unsignedInteger('records_updated')->default(0);
                $table->timestamps();

                $table->index(['workspace_id', 'sync_mode']);
            });
        }

        // 5. Create google_import_logs table
        if (!Schema::hasTable('google_import_logs')) {
            Schema::create('google_import_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('source_type'); // google_sheets, gmail_contacts, gmail_messages
                $table->string('source_identifier')->nullable();
                $table->string('status')->default('success'); // success, partial, failed
                $table->unsignedInteger('imported_count')->default(0);
                $table->unsignedInteger('updated_count')->default(0);
                $table->unsignedInteger('matched_companies_count')->default(0);
                $table->unsignedInteger('created_companies_count')->default(0);
                $table->json('error_details')->nullable();
                $table->timestamps();

                $table->index(['workspace_id', 'source_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('google_import_logs');
        Schema::dropIfExists('google_sheet_syncs');
        Schema::dropIfExists('google_integrations');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['company_match_status', 'contact_emails', 'enrichment_source', 'is_verified']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['email', 'company_emails', 'enrichment_status', 'enrichment_source', 'verification_status', 'source_url']);
        });
    }
};
