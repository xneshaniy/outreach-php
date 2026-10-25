<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\CrmNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function markRead(Request $request, CrmNotification $notification): JsonResponse
    {
        $this->notificationService->markAsRead($notification);
        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $this->notificationService->markAllAsRead($workspace, $request->user()->id);
        return response()->json(['success' => true]);
    }
}
