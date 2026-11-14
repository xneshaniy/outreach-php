<?php

namespace App\Http\Controllers\Inbox;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\EmailAccount;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Workspace;
use App\Services\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function __construct(
        protected EmailService $emailService
    ) {}

    protected function getWorkspace(Request $request): Workspace
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace;
        if (!$workspace && $user) {
            $workspace = $user->workspaces()->first()
                ?? Workspace::where('is_active', true)->orderBy('name')->first()
                ?? Workspace::first();
            if ($workspace) {
                session(['current_workspace_id' => $workspace->id]);
                try { $user->setCurrentWorkspace($workspace); } catch (\Throwable $e) {}
            }
        }

        if (!$workspace) {
            $workspace = Workspace::create([
                'name' => 'Default Workspace',
                'slug' => 'default-' . Str::random(6),
                'is_active' => true,
            ]);
            if ($user) {
                $user->workspaces()->attach($workspace->id, ['role' => 'admin']);
                $user->setCurrentWorkspace($workspace);
            }
        }

        return $workspace;
    }

    public function index(Request $request): Response
    {
        $workspace = $this->getWorkspace($request);

        $query = Message::where('workspace_id', $workspace->id)
            ->with([
                'lead:id,first_name,last_name,email,company_id',
                'lead.company:id,name',
                'campaign:id,name',
                'emailAccount:id,name,email',
            ]);

        // Folder filter
        $folder = $request->input('folder', 'all');
        if ($folder === 'inbox') {
            $query->where(function ($q) {
                $q->where('status', 'replied')
                  ->orWhereNotNull('replied_at');
            });
        } elseif ($folder === 'sent') {
            $query->whereNotNull('sent_at')->where('status', '!=', 'replied');
        } elseif ($folder === 'starred') {
            $query->where(function ($q) {
                $q->where('status', 'opened')
                  ->orWhere('status', 'replied');
            });
        } elseif ($folder === 'bounced') {
            $query->where(function ($q) {
                $q->where('status', 'bounced')
                  ->orWhere('status', 'failed');
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('from_email', 'like', "%{$search}%")
                    ->orWhere('body_text', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('company', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
                    });
            });
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
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

        $emailAccounts = EmailAccount::where(function ($q) use ($workspace) {
                $q->where('workspace_id', $workspace->id)->orWhereNull('workspace_id');
            })
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'from_name', 'daily_send_limit', 'sent_today', 'is_active')
            ->get();

        if ($emailAccounts->isEmpty()) {
            $emailAccounts = EmailAccount::where('is_active', true)
                ->select('id', 'name', 'email', 'from_name', 'daily_send_limit', 'sent_today', 'is_active')
                ->get();
        }

        $recentLeads = Lead::where('workspace_id', $workspace->id)
            ->whereNotNull('email')
            ->select('id', 'first_name', 'last_name', 'email', 'company_name')
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn($l) => [
                'id' => $l->id,
                'name' => trim(($l->first_name ?? '') . ' ' . ($l->last_name ?? '')) ?: 'Prospect',
                'email' => $l->email,
                'company' => $l->company_name ?? '',
            ]);

        return Inertia::render('Inbox/Index', [
            'messages' => $messages,
            'filters' => $request->only(['search', 'status', 'campaign_id', 'email_account_id', 'folder']),
            'stats' => $stats,
            'campaigns' => $campaigns,
            'emailAccounts' => $emailAccounts,
            'recentLeads' => $recentLeads,
            'statuses' => ['all', 'sent', 'delivered', 'opened', 'clicked', 'replied', 'bounced', 'failed', 'queued'],
        ]);
    }

    public function show(Request $request, Message $message): Response
    {
        $workspace = $this->getWorkspace($request);
        abort_if($message->workspace_id !== $workspace->id, 403);

        $message->load([
            'lead.company:id,name',
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
        $workspace = $this->getWorkspace($request);
        abort_if($message->workspace_id !== $workspace->id, 403);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'email_account_id' => 'nullable|exists:email_accounts,id',
        ]);

        $account = null;
        if (!empty($validated['email_account_id'])) {
            $account = EmailAccount::where('workspace_id', $workspace->id)->find($validated['email_account_id'])
                ?? EmailAccount::find($validated['email_account_id']);
        }
        if (!$account && $message->email_account_id) {
            $account = EmailAccount::where('workspace_id', $workspace->id)->find($message->email_account_id);
        }
        if (!$account) {
            $account = EmailAccount::where('workspace_id', $workspace->id)->where('is_active', true)->first()
                ?? EmailAccount::where('is_active', true)->first();
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
                'message_id' => $result['message_id'] ?? ('reply_' . Str::random(24)),
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

            $account->incrementSent();

            return back()->with('success', "Reply sent successfully to {$toEmail}!");
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send reply: ' . $e->getMessage());
        }
    }

    /**
     * Send direct email to any email address using any sender account
     */
    public function send(Request $request): RedirectResponse
    {
        $workspace = $this->getWorkspace($request);

        $validated = $request->validate([
            'to_email'         => 'required|email|max:255',
            'to_name'          => 'nullable|string|max:255',
            'subject'          => 'required|string|max:255',
            'body'             => 'required|string',
            'email_account_id' => 'nullable|exists:email_accounts,id',
        ]);

        $account = null;
        if (!empty($validated['email_account_id'])) {
            $account = EmailAccount::where('workspace_id', $workspace->id)->find($validated['email_account_id'])
                ?? EmailAccount::find($validated['email_account_id']);
        }
        if (!$account) {
            $account = EmailAccount::where('workspace_id', $workspace->id)->where('is_active', true)->first()
                ?? EmailAccount::where('is_active', true)->first();
        }

        if (!$account) {
            return back()->with('error', 'No active email account found. Please connect an email account under Email Accounts first.');
        }

        $toEmail = trim($validated['to_email']);
        $toName = trim($validated['to_name'] ?? '');

        // Find or create lead so conversation history and inbox tracking work seamlessly
        $lead = Lead::where('workspace_id', $workspace->id)
            ->where('normalized_email', Lead::normalizeEmail($toEmail))
            ->first();

        if (!$lead) {
            $nameParts = explode(' ', $toName ?: 'Contact', 2);
            $lead = Lead::create([
                'workspace_id'     => $workspace->id,
                'created_by'       => $request->user()?->id,
                'first_name'       => $nameParts[0] ?: 'Contact',
                'last_name'        => $nameParts[1] ?? '',
                'email'            => $toEmail,
                'status'           => 'contacted',
                'source'           => 'inbox_direct',
                'last_contacted_at'=> now(),
            ]);
        }

        $htmlBody = nl2br(e($validated['body']));

        try {
            $result = $this->emailService->send(
                account: $account,
                to: [['email' => $toEmail, 'name' => $toName ?: $lead->full_name]],
                subject: $validated['subject'],
                html: $htmlBody,
                text: $validated['body']
            );

            $messageId = $result['message_id'] ?? ('msg_' . Str::random(24));

            Message::create([
                'workspace_id'     => $workspace->id,
                'email_account_id' => $account->id,
                'campaign_id'      => null,
                'lead_id'          => $lead->id,
                'message_id'       => $messageId,
                'subject'          => $validated['subject'],
                'body_html'        => $htmlBody,
                'body_text'        => $validated['body'],
                'from_email'       => $account->email,
                'from_name'        => $account->from_name ?? $account->name,
                'to_emails'        => [['email' => $toEmail, 'name' => $toName ?: $lead->full_name]],
                'status'           => Message::STATUS_SENT,
                'sent_at'          => now(),
            ]);

            $account->incrementSent();
            $lead->update(['last_contacted_at' => now()]);

            return back()->with('success', "Email sent successfully to {$toEmail} from {$account->email}!");
        } catch (\Throwable $e) {
            Log::error('Inbox direct send failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }
}
