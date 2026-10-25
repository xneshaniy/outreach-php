<?php

namespace App\Services;

use App\Models\CrmNotification;
use App\Models\Workspace;
use App\Models\User;

class NotificationService
{
    /**
     * Create a notification in the database.
     */
    public function notify(Workspace $workspace, string $type, string $title, string $message, array $data = [], ?int $userId = null): CrmNotification
    {
        return CrmNotification::create([
            'workspace_id' => $workspace->id,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'read_at' => null,
        ]);
    }

    /**
     * Fetch unread notifications for a workspace / user.
     */
    public function getUnread(Workspace $workspace, ?int $userId = null, int $limit = 15)
    {
        $query = CrmNotification::where('workspace_id', $workspace->id)
            ->whereNull('read_at');

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            });
        }

        return $query->latest()->limit($limit)->get();
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(CrmNotification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a workspace.
     */
    public function markAllAsRead(Workspace $workspace, ?int $userId = null): void
    {
        $query = CrmNotification::where('workspace_id', $workspace->id)
            ->whereNull('read_at');

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            });
        }

        $query->update(['read_at' => now()]);
    }
}
