<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AdminLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Activity::with(['causer:id,name,email']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%");
            });
        }

        if ($event = $request->input('event')) {
            $query->where('event', $event);
        }

        $logs = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'event']),
            'totalLogs' => Activity::count(),
        ]);
    }
}
