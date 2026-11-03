<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('trial_ends_at')
                ->constrained('users')->nullOnDelete();
        });

        // Backfill the owner_id column for workspaces that already have an
        // owner recorded on the workspace_user pivot. This keeps the new
        // column in sync with existing data instead of leaving it NULL.
        $ownerRows = DB::table('workspace_user')
            ->where('is_owner', true)
            ->get(['workspace_id', 'user_id']);

        foreach ($ownerRows as $row) {
            DB::table('workspaces')
                ->where('id', $row->workspace_id)
                ->update(['owner_id' => $row->user_id]);
        }
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
        });
    }
};