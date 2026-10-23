<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\TaskReminderMail;

class SendReminderEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function handle(): void
    {
        $tasks = Task::where('remind_at', '<=', now())
            ->where('reminder_sent', false)
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->where('status', '!=', Task::STATUS_CANCELLED)
            ->with(['assignee', 'creator', 'workspace'])
            ->get();

        foreach ($tasks as $task) {
            if (!$task->assignee || !$task->assignee->email) {
                continue;
            }

            try {
                Mail::to($task->assignee->email)->send(new TaskReminderMail($task));
                $task->update(['reminder_sent' => true]);
                Log::info("Sent task reminder for task {$task->id} to {$task->assignee->email}");
            } catch (\Exception $e) {
                Log::error("Failed to send task reminder for task {$task->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}