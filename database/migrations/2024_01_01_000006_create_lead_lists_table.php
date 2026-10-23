<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_dynamic')->default(false);
            $table->json('filters')->nullable(); // For dynamic lists
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_list_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['lead_list_id', 'lead_id']);
        });

        Schema::create('lead_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_list_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('status')->default('pending'); // pending, processing, completed, failed, cancelled
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

        Schema::create('lead_import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_import_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->json('row_data');
            $table->text('error_message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_import_errors');
        Schema::dropIfExists('lead_imports');
        Schema::dropIfExists('lead_list_memberships');
        Schema::dropIfExists('lead_lists');
    }
};