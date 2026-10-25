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
                  ->orWhere('smtp_host', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $accounts = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Admin/EmailAccounts/Index', [
            'accounts' => $accounts,
            'filters' => $request->only(['search', 'status']),
            'summary' => [
                'total' => EmailAccount::count(),
                'active' => EmailAccount::where('status', 'active')->count(),
                'paused' => EmailAccount::where('status', 'paused')->count(),
                'error' => EmailAccount::where('status', 'error')->count(),
                'sentToday' => (int) EmailAccount::sum('sent_today'),
                'totalDailyQuota' => (int) EmailAccount::sum('daily_limit'),
            ],
        ]);
    }

    public function testConnection(EmailAccount $account, EmailService $emailService): RedirectResponse
    {
        try {
            $result = $emailService->testConnection($account);

            if ($result['success']) {
                $account->update([
                    'status' => 'active',
                    'last_tested_at' => now(),
                    'last_error' => null,
                ]);

                return back()->with('success', "Connection successful! Connected to {$account->smtp_host}:{$account->smtp_port}");
            } else {
                $account->update([
                    'status' => 'error',
                    'last_tested_at' => now(),
                    'last_error' => $result['message'],
                ]);

                return back()->withErrors(['error' => "Connection failed: {$result['message']}"]);
            }
        } catch (\Throwable $e) {
            $account->update([
                'status' => 'error',
                'last_tested_at' => now(),
                'last_error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => "Connection test error: {$e->getMessage()}"]);
        }
    }

    public function toggleStatus(EmailAccount $account): RedirectResponse
    {
        $newStatus = ($account->status === 'active') ? 'paused' : 'active';
        $account->update(['status' => $newStatus]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($account)
            ->log("Email account status changed to {$newStatus}");

        return back()->with('success', "Email account is now {$newStatus}.");
    }

    public function updateLimits(EmailAccount $account, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'daily_limit' => 'required|integer|min:1|max:10000',
            'hourly_limit' => 'nullable|integer|min:1|max:1000',
        ]);

        $account->update($validated);

        return back()->with('success', 'Email account limits updated.');
    }
}
