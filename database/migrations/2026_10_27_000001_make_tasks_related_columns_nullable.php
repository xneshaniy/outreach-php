<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('related_type')->nullable()->change();
            $table->unsignedBigInteger('related_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('related_type')->nullable(false)->change();
            $table->unsignedBigInteger('related_id')->nullable(false)->change();
        });
    }
};
