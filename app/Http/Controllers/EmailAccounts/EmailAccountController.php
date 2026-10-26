<?php

namespace App\Http\Controllers\EmailAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailAccount\StoreEmailAccountRequest;
use App\Http\Requests\EmailAccount\UpdateEmailAccountRequest;
use App\Models\EmailAccount;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmailAccountController extends Controller
{
    public function __construct(
        protected EmailService $emailService
    ) {}

    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $accounts = EmailAccount::where('workspace_id', $workspace->id)
            ->with(['user'])
            ->latest()
            ->paginate(20)
            ->through(function ($acc) {
                return [
                    'id' => $acc->id,
                    'name' => $acc->name,
                    'email' => $acc->email,
                    'provider' => $acc->provider,
                    'host' => $acc->host,
                    'port' => $acc->port,
                    'encryption' => $acc->encryption,
                    'username' => $acc->username,
                    'from_name' => $acc->from_name,
                    'reply_to' => $acc->reply_to,
                    'daily_send_limit' => $acc->daily_send_limit,
                    'sent_today' => $acc->last_sent_date === now()->toDateString() ? $acc->sent_today : 0,
                    'remaining_today' => $acc->getRemainingToday(),
                    'bounced_count' => $acc->bounced_count ?? 0,
                    'failed_count' => $acc->failed_count ?? 0,
                    'is_active' => (bool)$acc->is_active,
                    'is_default' => (bool)$acc->is_default,
                    'last_tested_at' => $acc->last_tested_at?->toIso8601String(),
                    'last_test_status' => $acc->last_test_status,
                    'last_test_message' => $acc->last_test_message,
                    'created_at' => $acc->created_at?->toIso8601String(),
                ];
            });

        return Inertia::render('EmailAccounts/Index', [
            'accounts' => $accounts,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('EmailAccounts/Create', [
            'providers' => [
                ['id' => 'smtp', 'name' => 'Custom SMTP'],
                ['id' => 'gmail', 'name' => 'Gmail / Google Workspace'],
                ['id' => 'outlook', 'name' => 'Outlook / Office 365'],
                ['id' => 'yahoo', 'name' => 'Yahoo'],
                ['id' => 'custom', 'name' => 'Other'],
            ],
            'encryption_options' => [
                ['value' => 'tls', 'label' => 'TLS (Port 587)'],
                ['value' => 'ssl', 'label' => 'SSL (Port 465)'],
                ['value' => 'none', 'label' => 'None (Port 25)'],
            ],
        ]);
    }

    public function store(StoreEmailAccountRequest $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        $account = EmailAccount::create(array_merge($request->validated(), [
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'normalized_email' => strtolower(trim($request->email)),
        ]));

        // Test connection defensively on save
        try {
            $testResult = $account->testConnection();
        } catch (\Throwable $e) {
            $testResult = ['success' => false, 'message' => $e->getMessage()];
        }

        if ($workspace->emailAccounts()->count() === 1) {
            $account->update(['is_default' => true]);
        }

        $msg = $testResult['success']
            ? 'Email account connected and verified successfully.'
            : 'Email account saved, but SMTP test failed: ' . $testResult['message'];

        return redirect()->route('email-accounts.index')
            ->with($testResult['success'] ? 'success' : 'warning', $msg);
    }

    public function show(Request $request, EmailAccount $account): Response
    {
        $this->authorize('view', $account);

        $account->load(['user', 'campaigns' => fn($q) => $q->limit(5)]);

        return Inertia::render('EmailAccounts/Show', [
            'account' => $account,
        ]);
    }

    public function edit(Request $request, EmailAccount $account): Response
    {
        $this->authorize('update', $account);

        return Inertia::render('EmailAccounts/Edit', [
            'account' => $account,
            'providers' => [
                ['id' => 'smtp', 'name' => 'Custom SMTP'],
                ['id' => 'gmail', 'name' => 'Gmail / Google Workspace'],
                ['id' => 'outlook', 'name' => 'Outlook / Office 365'],
                ['id' => 'yahoo', 'name' => 'Yahoo'],
                ['id' => 'custom', 'name' => 'Other'],
            ],
            'encryption_options' => [
                ['value' => 'tls', 'label' => 'TLS (Port 587)'],
                ['value' => 'ssl', 'label' => 'SSL (Port 465)'],
                ['value' => 'none', 'label' => 'None (Port 25)'],
            ],
        ]);
    }

    public function update(UpdateEmailAccountRequest $request, EmailAccount $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $data = $request->validated();
        if (isset($data['email'])) {
            $data['normalized_email'] = strtolower(trim($data['email']));
        }

        // Only update password if provided
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $account->update($data);

        return redirect()->route('email-accounts.index')
            ->with('success', 'Email account updated successfully.');
    }

    public function destroy(Request $request, EmailAccount $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $wasDefault = $account->is_default;
        $account->delete();

        if ($wasDefault) {
            $newDefault = EmailAccount::where('workspace_id', $request->user()->currentWorkspace->id)
                ->where('is_active', true)
                ->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return redirect()->route('email-accounts.index')
            ->with('success', 'Email account removed successfully.');
    }

    public function test(Request $request, EmailAccount $account): JsonResponse
    {
        $this->authorize('update', $account);

        $result = $account->testConnection();

        return response()->json($result);
    }

    public function testUnsaved(Request $request): JsonResponse
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'username' => 'required|string',
            'password' => 'required|string',
            'encryption' => 'required|string|in:ssl,tls,none',
        ]);

        $result = $this->emailService->testCredentials($request->only([
            'host', 'port', 'username', 'password', 'encryption'
        ]));

        return response()->json($result);
    }

    public function toggle(Request $request, EmailAccount $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $account->update([
            'is_active' => !$account->is_active
        ]);

        $state = $account->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "Email account {$account->email} has been {$state}.");
    }

    public function setDefault(Request $request, EmailAccount $account): RedirectResponse
    {
        $this->authorize('update', $account);

        EmailAccount::where('workspace_id', $request->user()->currentWorkspace->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $account->update(['is_default' => true]);

        return back()->with('success', 'Default email account updated.');
    }
}
