<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Product Categories
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

        // 2. Products / Service Packages
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
                $table->string('name');
                $table->string('sku')->nullable();
                $table->string('type')->default('service'); // service, retainer, lead_package, product
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->decimal('cost', 10, 2)->nullable();
                $table->string('currency', 10)->default('USD');
                $table->string('billing_period')->nullable(); // one_time, monthly, quarterly, yearly
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['workspace_id', 'is_active']);
            });
        }

        // 3. Quotes
        if (!Schema::hasTable('quotes')) {
            Schema::create('quotes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->string('quote_number');
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('deal_id')->nullable()->constrained('deals')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('draft'); // draft, sent, accepted, declined, invoiced
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

        // 4. Quote Items
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

        // 5. Invoices
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
                $table->string('status')->default('draft'); // draft, sent, paid, partially_paid, overdue, cancelled
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

        // 6. Invoice Items
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

        // 7. Client ROI & Outreach Reports
        if (!Schema::hasTable('client_reports')) {
            Schema::create('client_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->string('title');
                $table->string('client_name');
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->json('metrics')->nullable(); // leads_contacted, verified_leads, delivered, opened, replied, interested, meetings_booked
                $table->text('summary_notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('workspace_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_reports');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
