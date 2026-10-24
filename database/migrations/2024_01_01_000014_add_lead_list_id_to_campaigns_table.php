<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('campaigns', 'lead_list_id')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->foreignId('lead_list_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('campaigns', 'lead_list_id')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('lead_list_id');
            });
        }
    }
};
