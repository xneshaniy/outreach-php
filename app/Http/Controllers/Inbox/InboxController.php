<?php

namespace App\Http\Controllers\Inbox;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\EmailAccount;
use App\Models\Message;
use App\Services\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function __construct(
        protected EmailService $emailService
    ) {}

    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $query = Message::where('workspace_id', $workspace->id)
            ->with([
                'lead:id,first_name,last_name,email,company_name',
                'campaign:id,name',
                'emailAccount:id,name,email',
            ]);

        // Search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('from_email', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Campaign filter
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // Mailbox filter
        if ($request->filled('email_account_id')) {
            $query->where('email_account_id', $request->email_account_id);
        }

        $messages = $query->latest('created_at')->paginate(25)->withQueryString();

        // Workspace message stats
        $stats = [
            'total_emails' => Message::where('workspace_id', $workspace->id)->count(),
            'total_sent' => Message::where('workspace_id', $workspace->id)->whereNotNull('sent_at')->count(),
            'total_opened' => Message::where('workspace_id', $workspace->id)->whereNotNull('opened_at')->count(),
            'total_replied' => Message::where('workspace_id', $workspace->id)->where(function ($q) {
                $q->whereNotNull('replied_at')->orWhere('status', 'replied');
            })->count(),
            'total_bounced' => Message::where('workspace_id', $workspace->id)->where(function ($q) {
                $q->whereNotNull('bounced_at')->orWhere('status', 'bounced');
            })->count(),
        ];

        $campaigns = Campaign::where('workspace_id', $workspace->id)->select('id', 'name')->get();
        $emailAccounts = EmailAccount::where('workspace_id', $workspace->id)->select('id', 'name', 'email')->get();

        return Inertia::render('Inbox/Index', [
            'messages' => $messages,
            'filters' => $request->only(['search', 'status', 'campaign_id', 'email_account_id']),
            'stats' => $stats,
            'campaigns' => $campaigns,
            'emailAccounts' => $emailAccounts,
            'statuses' => ['all', 'sent', 'delivered', 'opened', 'clicked', 'replied', 'bounced', 'failed', 'queued'],
        ]);
    }

    public function show(Request $request, Message $message): Response
    {
        $workspace = $request->user()->currentWorkspace;
        abort_if($message->workspace_id !== $workspace->id, 403);

        $message->load([
            'lead',
            'campaign',
            'emailAccount',
            'events' => fn($q) => $q->latest('occurred_at'),
        ]);

        // Fetch entire thread history for this lead
        $thread = [];
        if ($message->lead_id) {
            $thread = Message::where('workspace_id', $workspace->id)
                ->where('lead_id', $message->lead_id)
                ->with(['emailAccount:id,name,email', 'campaign:id,name'])
                ->orderBy('created_at', 'asc')
                ->get();
        }

        $emailAccounts = EmailAccount::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->get();

        return Inertia::render('Inbox/Show', [
            'message' => $message,
            'thread' => $thread,
            'emailAccounts' => $emailAccounts,
        ]);
    }

    public function reply(Request $request, Message $message): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        abort_if($message->workspace_id !== $workspace->id, 403);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'email_account_id' => 'nullable|exists:email_accounts,id',
        ]);

        $account = null;
        if (!empty($validated['email_account_id'])) {
            $account = EmailAccount::where('workspace_id', $workspace->id)->find($validated['email_account_id']);
        }
        if (!$account && $message->email_account_id) {
            $account = EmailAccount::where('workspace_id', $workspace->id)->find($message->email_account_id);
        }
        if (!$account) {
            $account = EmailAccount::where('workspace_id', $workspace->id)->where('is_active', true)->first();
        }

        if (!$account) {
            return back()->with('error', 'No active email account found to send this reply. Please connect a mailbox first.');
        }

        // Determine recipient email
        $toEmail = $message->lead?->email;
        $toName = $message->lead ? trim(($message->lead->first_name ?? '') . ' ' . ($message->lead->last_name ?? '')) : null;

        if (!$toEmail && is_array($message->to_emails) && count($message->to_emails) > 0) {
            $first = $message->to_emails[0];
            $toEmail = is_array($first) ? ($first['email'] ?? null) : $first;
            $toName = is_array($first) ? ($first['name'] ?? null) : null;
        }

        if (!$toEmail) {
            return back()->with('error', 'Cannot determine recipient email address.');
        }

        $htmlBody = nl2br(e($validated['body']));

        try {
            $result = $this->emailService->send(
                account: $account,
                to: [['email' => $toEmail, 'name' => $toName]],
                subject: $validated['subject'],
                html: $htmlBody,
                text: $validated['body'],
                options: [
                    'in_reply_to' => $message->message_id,
                    'references' => $message->message_id,
                ]
            );

            // Record the sent reply in Message table
            Message::create([
                'workspace_id' => $workspace->id,
                'email_account_id' => $account->id,
                'campaign_id' => $message->campaign_id,
                'lead_id' => $message->lead_id,
                'message_id' => $result['message_id'] ?? null,
                'in_reply_to' => $message->message_id,
                'subject' => $validated['subject'],
                'body_html' => $htmlBody,
                'body_text' => $validated['body'],
                'from_email' => $account->email,
                'from_name' => $account->from_name ?? $account->name,
                'to_emails' => [['email' => $toEmail, 'name' => $toName]],
                'status' => Message::STATUS_SENT,
                'sent_at' => now(),
            ]);

            return back()->with('success', "Reply sent successfully to {$toEmail}!");
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send reply: ' . $e->getMessage());
        }
    }
}
