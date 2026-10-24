<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name', 'activity_log');
        $schema = Schema::connection($connection);

        if (! $schema->hasTable($tableName)) {
            $schema->create($tableName, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable();
                $table->text('description');
                $table->nullableMorphs('subject', 'subject');
                $table->nullableMorphs('causer', 'causer');
                $table->json('properties')->nullable();
                $table->string('event')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->timestamps();
                $table->index('log_name');
            });

            return;
        }

        $schema->table($tableName, function (Blueprint $table) use ($schema, $tableName) {
            if (! $schema->hasColumn($tableName, 'event')) {
                $table->string('event')->nullable();
            }

            if (! $schema->hasColumn($tableName, 'batch_uuid')) {
                $table->uuid('batch_uuid')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->dropIfExists(config('activitylog.table_name', 'activity_log'));
    }
};
