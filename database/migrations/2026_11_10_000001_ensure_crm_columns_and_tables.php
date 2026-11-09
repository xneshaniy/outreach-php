<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure users table has current_workspace_id
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'current_workspace_id')) {
                    $table->unsignedBigInteger('current_workspace_id')->nullable()->after('is_super_admin')->index();
                }
            });
        }

        // 1. Ensure leads table has all essential columns
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (!Schema::hasColumn('leads', 'company_name')) {
                    $table->string('company_name')->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('leads', 'website')) {
                    $table->string('website')->nullable()->after('company_id');
                }
                if (!Schema::hasColumn('leads', 'country_code')) {
                    $table->string('country_code', 5)->nullable()->after('country')->index();
                }
                if (!Schema::hasColumn('leads', 'detected_language')) {
                    $table->string('detected_language', 10)->default('en')->after('country')->index();
                }
                if (!Schema::hasColumn('leads', 'business_category')) {
                    $table->string('business_category')->nullable()->after('industry');
                }
                if (!Schema::hasColumn('leads', 'lead_quality_score')) {
                    $table->integer('lead_quality_score')->default(50)->after('score')->index();
                }
                if (!Schema::hasColumn('leads', 'verification_status')) {
                    $table->string('verification_status')->default('unverified')->after('score');
                }
                if (!Schema::hasColumn('leads', 'pipeline_stage')) {
                    $table->string('pipeline_stage')->default('new_lead')->after('status')->index();
                }
                if (!Schema::hasColumn('leads', 'source_url')) {
                    $table->text('source_url')->nullable()->after('source');
                }
                if (!Schema::hasColumn('leads', 'discovery_date')) {
                    $table->timestamp('discovery_date')->nullable()->after('source');
                }
                if (!Schema::hasColumn('leads', 'approval_status')) {
                    $table->string('approval_status')->default('auto_approved')->after('status');
                }
                if (!Schema::hasColumn('leads', 'score_reasons')) {
                    $table->json('score_reasons')->nullable()->after('score');
                }
            });
        }

        // 2. Ensure product_categories table exists
        if (!Schema::hasTable('product_categories')) {
            Schema::create('product_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->index('workspace_id');
            });
        }

        // 3. Ensure products table exists
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
                $table->string('name');
                $table->string('sku')->nullable();
                $table->string('type')->default('service');
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->decimal('cost', 10, 2)->nullable();
                $table->string('currency', 10)->default('USD');
                $table->string('billing_period')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['workspace_id', 'is_active']);
            });
        }

        // 4. Ensure quotes table exists
        if (!Schema::hasTable('quotes')) {
            Schema::create('quotes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->string('quote_number');
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('deal_id')->nullable()->constrained('deals')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('draft');
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->string('currency', 10)->default('USD');
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->text('terms')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
                $table->index(['workspace_id', 'status']);
            });
        }

        // 5. Ensure quote_items table exists
        if (!Schema::hasTable('quote_items')) {
            Schema::create('quote_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('item_name');
                $table->text('description')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        // 6. Ensure invoices table exists
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->string('invoice_number');
                $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('deal_id')->nullable()->constrained('deals')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('draft');
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->string('currency', 10)->default('USD');
                $table->date('issue_date')->nullable();
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_method')->nullable();
                $table->text('notes')->nullable();
                $table->text('terms')->nullable();
                $table->timestamps();
                $table->index(['workspace_id', 'status']);
            });
        }

        // 7. Ensure invoice_items table exists
        if (!Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('item_name');
                $table->text('description')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        // 8. Ensure client_reports table exists
        if (!Schema::hasTable('client_reports')) {
            Schema::create('client_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->string('title');
                $table->string('client_name');
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->json('metrics')->nullable();
                $table->text('summary_notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index('workspace_id');
            });
        }
            // 9. Ensure import_mapping_templates table exists
        if (!Schema::hasTable('import_mapping_templates')) {
            Schema::create('import_mapping_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->json('mapping');
                $table->timestamps();
                $table->index(['workspace_id', 'name']);
            });
        }

        // 10. Ensure lead_imports table exists
        if (!Schema::hasTable('lead_imports')) {
            Schema::create('lead_imports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->foreignId('lead_list_id')->nullable()->constrained('lead_lists')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('original_filename');
                $table->string('stored_path');
                $table->string('status')->default('pending');
                $table->integer('total_rows')->default(0);
                $table->integer('processed_rows')->default(0);
                $table->integer('successful_rows')->default(0);
                $table->integer('failed_rows')->default(0);
                $table->integer('duplicate_rows')->default(0);
                $table->json('column_mapping')->nullable();
                $table->json('errors')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 11. Ensure lead_import_errors table exists
        if (!Schema::hasTable('lead_import_errors')) {
            Schema::create('lead_import_errors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_import_id')->constrained('lead_imports')->cascadeOnDelete();
                $table->integer('row_number');
                $table->json('row_data');
                $table->text('error_message');
                $table->timestamps();
            });
        }
    }
public function down(): void
    {
        // Safe down: do not drop production data
    }
};
