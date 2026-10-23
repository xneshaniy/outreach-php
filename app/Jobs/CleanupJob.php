<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\AuditLog;
use App\Models\Activity;
use App\Models\LeadImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function handle(): void
    {
        $this->cleanupOldMessages();
        $this->cleanupOldAuditLogs();
        $this->cleanupOldActivities();
        $this->cleanupOldImports();
        $this->resetDailyCounters();
    }

    private function cleanupOldMessages(): void
    {
        $retentionDays = config('crm.message_retention_days', 365);
        $cutoff = Carbon::now()->subDays($retentionDays);

        $deleted = Message::where('created_at', '<', $cutoff)
            ->whereIn('status', [Message::STATUS_DELIVERED, Message::STATUS_BOUNCED, Message::STATUS_FAILED])
            ->limit(1000)
            ->delete();

        Log::info("Cleaned up {$deleted} old messages");
    }

    private function cleanupOldAuditLogs(): void
    {
        $retentionDays = config('crm.audit_log_retention_days', 365);
        $cutoff = Carbon::now()->subDays($retentionDays);

        $deleted = AuditLog::where('created_at', '<', $cutoff)
            ->limit(5000)
            ->delete();

        Log::info("Cleaned up {$deleted} old audit logs");
    }

    private function cleanupOldActivities(): void
    {
        $retentionDays = config('crm.activity_retention_days', 180);
        $cutoff = Carbon::now()->subDays($retentionDays);

        $deleted = Activity::where('created_at', '<', $cutoff)
            ->limit(5000)
            ->delete();

        Log::info("Cleaned up {$deleted} old activities");
    }

    private function cleanupOldImports(): void
    {
        $retentionDays = config('crm.import_retention_days', 90);
        $cutoff = Carbon::now()->subDays($retentionDays);

        $imports = LeadImport::where('created_at', '<', $cutoff)
            ->whereIn('status', ['completed', 'failed', 'cancelled'])
            ->limit(100)
            ->get();

        foreach ($imports as $import) {
            if ($import->stored_path) {
                Storage::disk('local')->delete($import->stored_path);
            }
            $import->delete();
        }

        Log::info("Cleaned up " . count($imports) . " old imports");
    }

    private function resetDailyCounters(): void
    {
        // Handled automatically by EmailAccount::canSendToday()
    }
}