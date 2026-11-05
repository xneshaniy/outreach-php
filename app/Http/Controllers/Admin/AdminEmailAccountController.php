<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailAccount;
use App\Services\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminEmailAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $query = EmailAccount::with(['user:id,name,email', 'workspace:id,name']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('host', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'paused') {
                $query->where('is_active', false);
            } elseif ($status === 'error') {
                $query->where('last_test_status', 'failed');
            }
        }

        $accounts = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Admin/EmailAccounts/Index', [
            'accounts' => $accounts,
            'filters' => $request->only(['search', 'status']),
            'summary' => [
                'total' => EmailAccount::count(),
                'active' => EmailAccount::where('is_active', true)->count(),
                'paused' => EmailAccount::where('is_active', false)->count(),
                'error' => EmailAccount::where('last_test_status', 'failed')->count(),
                'sentToday' => (int) EmailAccount::sum('sent_today'),
                'totalDailyQuota' => (int) EmailAccount::sum('daily_send_limit'),
                'totalSent' => (int) \App\Models\Message::where('status', '!=', 'failed')->count(),
                'totalDelivered' => (int) \App\Models\Message::whereIn('status', ['sent', 'delivered', 'opened', 'clicked', 'replied'])->count(),
                'totalBounced' => (int) EmailAccount::sum('bounced_count') + (int) \App\Models\Message::where('status', 'bounced')->count(),
                'totalFailed' => (int) EmailAccount::sum('failed_count') + (int) \App\Models\Message::where('status', 'failed')->count(),
                'totalReplies' => (int) \App\Models\InboundEmailReply::count(),
            ],
        ]);
    }

    public function testConnection(EmailAccount $account, EmailService $emailService): RedirectResponse
    {
        try {
            $result = $emailService->testConnection($account);

            if ($result['success']) {
                $account->update([
                    'is_active' => true,
                    'last_tested_at' => now(),
                    'last_test_status' => 'success',
                    'last_test_message' => null,
                ]);

                return back()->with('success', "Connection successful! Connected to {$account->host}:{$account->port}");
            } else {
                $account->update([
                    'last_tested_at' => now(),
                    'last_test_status' => 'failed',
                    'last_test_message' => $result['message'],
                ]);

                app(\App\Services\AdminAlertService::class)->alertMailboxDisconnected($account, $result['message']);

                return back()->withErrors(['error' => "Connection failed: {$result['message']}"]);
            }
        } catch (\Throwable $e) {
            $account->update([
                'last_tested_at' => now(),
                'last_test_status' => 'failed',
                'last_test_message' => $e->getMessage(),
            ]);

            app(\App\Services\AdminAlertService::class)->alertMailboxDisconnected($account, $e->getMessage());

            return back()->withErrors(['error' => "Connection test error: {$e->getMessage()}"]);
        }
    }

    public function toggleStatus(EmailAccount $account): RedirectResponse
    {
        $newActive = ! $account->is_active;
        $account->update(['is_active' => $newActive]);
        $statusStr = $newActive ? 'active' : 'paused';

        activity()
            ->causedBy(auth()->user())
            ->performedOn($account)
            ->log("Email account status changed to {$statusStr}");

        return back()->with('success', "Email account is now {$statusStr}.");
    }

    public function updateLimits(EmailAccount $account, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'daily_send_limit' => 'nullable|integer|min:1|max:10000',
            'daily_limit' => 'nullable|integer|min:1|max:10000',
        ]);

        $limit = $validated['daily_send_limit'] ?? $validated['daily_limit'] ?? 6;
        $account->update([
            'daily_send_limit' => $limit,
            'limit_increase_requested' => false,
            'limit_increase_approved_by' => auth()->id(),
        ]);

        return back()->with('success', "Email account sending limit updated to {$limit}/day by Super Admin.");
    }

    public function syncHostinger(Request $request, \App\Services\HostingerMailService $service): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace ?? \App\Models\Workspace::first();
        $result = $service->syncMailboxes($workspace ? $workspace->id : 1);

        if (!$result['success']) {
            return back()->withErrors(['error' => 'Hostinger sync failed: ' . ($result['error'] ?? 'API error')]);
        }

        return back()->with('success', "Hostinger mailboxes synced! Total: {$result['total']}, Added: {$result['added']}, Updated: {$result['updated']}");
    }
}
