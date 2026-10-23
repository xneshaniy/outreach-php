<?php

namespace App\Http\Controllers\EmailAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailAccount\StoreEmailAccountRequest;
use App\Http\Requests\EmailAccount\UpdateEmailAccountRequest;
use App\Models\EmailAccount;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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
            ->paginate(20);

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

        // If this is the first account, make it default
        if ($workspace->emailAccounts()->count() === 1) {
            $account->update(['is_default' => true]);
        }

        return redirect()->route('email-accounts.show', $account)
            ->with('success', 'Email account created successfully.');
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

        $account->update($data);

        return redirect()->route('email-accounts.show', $account)
            ->with('success', 'Email account updated successfully.');
    }

    public function destroy(Request $request, EmailAccount $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $wasDefault = $account->is_default;
        $account->delete();

        // If deleted account was default, make another one default
        if ($wasDefault) {
            $newDefault = EmailAccount::where('workspace_id', $request->user()->currentWorkspace->id)
                ->where('is_active', true)
                ->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return redirect()->route('email-accounts.index')
            ->with('success', 'Email account deleted successfully.');
    }

    public function test(Request $request, EmailAccount $account): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $account);

        $result = $this->emailService->testConnection($account);

        return response()->json($result);
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