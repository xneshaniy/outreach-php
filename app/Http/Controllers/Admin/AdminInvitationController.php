<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminInvitationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Invitation::with(['invitedBy:id,name,email', 'workspace:id,name']);

        if ($search = $request->input('search')) {
            $query->where('email', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            if ($status === 'expired') {
                $query->where('expires_at', '<', Carbon::now())->where('status', 'pending');
            } else {
                $query->where('status', $status);
            }
        }

        $invitations = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $availableRoles = [
            ['id' => 'user', 'name' => 'User / Team Member', 'description' => 'Standard access to assigned leads and tasks'],
            ['id' => 'campaign_operator', 'name' => 'Campaign Operator', 'description' => 'Can build sequences, edit templates, and manage outreach'],
            ['id' => 'manager', 'name' => 'Workspace Manager', 'description' => 'Manages leads, team members, pipelines, and mailboxes'],
            ['id' => 'analyst', 'name' => 'Reporting Analyst', 'description' => 'Access to analytics, reports, exports, and billing'],
            ['id' => 'client', 'name' => 'Client Portal', 'description' => 'Read-only access to campaign progress and pipeline summary'],
            ['id' => 'workspace_admin', 'name' => 'Workspace Admin', 'description' => 'Full control over workspace settings, users, and integrations'],
            ['id' => 'super_admin', 'name' => 'Super Admin', 'description' => 'Complete control over entire platform, all workspaces, and settings'],
        ];

        $modules = [
            ['id' => 'contacts', 'name' => 'Contacts & Companies'],
            ['id' => 'leads', 'name' => 'Leads & Lists'],
            ['id' => 'email_accounts', 'name' => 'Email Accounts & SMTP'],
            ['id' => 'campaigns', 'name' => 'Campaigns & Drip Sequences'],
            ['id' => 'templates', 'name' => 'Email Templates'],
            ['id' => 'follow_ups', 'name' => 'Follow-up Automations'],
            ['id' => 'google_sheets', 'name' => 'Google Sheets Sync'],
            ['id' => 'reports', 'name' => 'Reports & Analytics'],
            ['id' => 'automations', 'name' => 'AI & Automations'],
            ['id' => 'workspaces', 'name' => 'Workspaces'],
            ['id' => 'clients', 'name' => 'Client Portal'],
            ['id' => 'users', 'name' => 'User Management'],
            ['id' => 'billing', 'name' => 'Billing & Tokens'],
            ['id' => 'settings', 'name' => 'System Settings'],
        ];

        return Inertia::render('Admin/Invitations/Index', [
            'invitations' => $invitations,
            'filters' => $request->only(['search', 'status']),
            'workspaces' => Workspace::select('id', 'name')->get(),
            'availableRoles' => $availableRoles,
            'modules' => $modules,
            'counts' => [
                'total' => Invitation::count(),
                'pending' => Invitation::where('status', 'pending')->where('expires_at', '>', Carbon::now())->count(),
                'accepted' => Invitation::where('status', 'accepted')->count(),
                'revoked' => Invitation::where('status', 'revoked')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|string|in:user,admin,manager,campaign_manager,campaign_operator,analyst,client,super_admin,workspace_admin',
            'workspace_id' => 'nullable|exists:workspaces,id',
            'permissions' => 'nullable|array',
            'expires_in_days' => 'required|integer|min:1|max:30',
        ]);

        // Normalize email
        $email = strtolower(trim($validated['email']));

        // Check if there is already an active pending invitation
        $existing = Invitation::where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existing) {
            return back()->withErrors(['email' => 'An active invitation is already pending for this email address.']);
        }

        $pair = Invitation::createTokenPair();

        $invitation = Invitation::create([
            'workspace_id' => $validated['workspace_id'] ?? Workspace::first()?->id,
            'email' => $email,
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? null,
            'token_hash' => $pair['hash'],
            'status' => 'pending',
            'invited_by_id' => auth()->id(),
            'expires_at' => Carbon::now()->addDays($validated['expires_in_days']),
        ]);

        $inviteUrl = url("/invite/{$pair['raw']}");

        // Attempt optional email notification if mailer is configured
        $mailSent = false;
        try {
            // Priority 1: Use active connected EmailAccount (SMTP) from database
            $senderAccount = \App\Models\EmailAccount::where('is_active', true)
                ->where('is_default', true)
                ->first()
                ?? \App\Models\EmailAccount::where('is_active', true)->first();

            if ($senderAccount) {
                $emailService = app(\App\Services\EmailService::class);
                $sendResult = $emailService->send(
                    $senderAccount,
                    [['email' => $email, 'name' => '']],
                    'Invitation to join Outreach CRM',
                    "<p>Hello,</p><p>You have been invited to join <strong>Outreach CRM</strong> as <strong>" . htmlspecialchars($validated['role']) . "</strong>.</p><p><a href=\"{$inviteUrl}\" style=\"display:inline-block;padding:10px 20px;background:#0265D2;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;\">Accept Invitation & Set Password</a></p><p>Or copy and paste this link into your browser:<br><a href=\"{$inviteUrl}\">{$inviteUrl}</a></p><p>This invitation link will expire in {$validated['expires_in_days']} days.</p><br><p>Best regards,<br>Outreach Team</p>",
                    "You have been invited to join Outreach CRM as a {$validated['role']}.\n\nClick the link below to set up your password and access your workspace:\n{$inviteUrl}\n\nThis invitation will expire in {$validated['expires_in_days']} days.\n\nOutreach.click Security Team"
                );
                $mailSent = !empty($sendResult['success']);
            } elseif (config('mail.mailers.smtp.host') && config('mail.mailers.smtp.host') !== '127.0.0.1' && !empty(config('mail.mailers.smtp.password'))) {
                \Illuminate\Support\Facades\Mail::raw(
                    "You have been invited to join Outreach CRM as a {$validated['role']}.\n\nClick the link below to set up your password and access your workspace:\n{$inviteUrl}\n\nThis invitation will expire in {$validated['expires_in_days']} days.\n\nOutreach.click Security Team",
                    function ($msg) use ($email) {
                        $msg->to($email)->subject('Invitation to join Outreach CRM');
                    }
                );
                $mailSent = true;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Invite email could not be delivered directly ({$e->getMessage()}); providing copyable invite link instead.");
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($invitation)
            ->withProperties(['email' => $invitation->email, 'role' => $invitation->role])
            ->log('Invitation created');

        return back()->with('invitationSuccess', [
            'message' => $mailSent ? "Invitation created and emailed to {$email}." : 'Invitation created successfully.',
            'email' => $invitation->email,
            'inviteUrl' => $inviteUrl,
        ]);
    }

    public function resend(Invitation $invitation): RedirectResponse
    {
        if ($invitation->status === 'accepted') {
            return back()->withErrors(['error' => 'This invitation has already been accepted.']);
        }

        $pair = Invitation::createTokenPair();

        $invitation->update([
            'token_hash' => $pair['hash'],
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        $inviteUrl = url("/invite/{$pair['raw']}");

        $mailSent = false;
        try {
            $senderAccount = \App\Models\EmailAccount::where('is_active', true)
                ->where('is_default', true)
                ->first()
                ?? \App\Models\EmailAccount::where('is_active', true)->first();

            if ($senderAccount) {
                $emailService = app(\App\Services\EmailService::class);
                $sendResult = $emailService->send(
                    $senderAccount,
                    [['email' => $invitation->email, 'name' => '']],
                    'Invitation to join Outreach CRM (Resent)',
                    "<p>Hello,</p><p>Your invitation to join <strong>Outreach CRM</strong> has been regenerated.</p><p><a href=\"{$inviteUrl}\" style=\"display:inline-block;padding:10px 20px;background:#0265D2;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;\">Accept Invitation & Set Password</a></p><p>Or copy and paste this link:<br><a href=\"{$inviteUrl}\">{$inviteUrl}</a></p><p>This link expires in 7 days.</p>",
                    "Your invitation to join Outreach CRM has been regenerated.\n\nClick the link below:\n{$inviteUrl}\n\nThis link expires in 7 days."
                );
                $mailSent = !empty($sendResult['success']);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Resend invite email failed: {$e->getMessage()}");
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($invitation)
            ->log('Invitation token regenerated and resent');

        return back()->with('invitationSuccess', [
            'message' => $mailSent ? "Invitation regenerated and emailed to {$invitation->email}." : 'Invitation regenerated successfully.',
            'email' => $invitation->email,
            'inviteUrl' => $inviteUrl,
        ]);
    }

    public function revoke(Invitation $invitation): RedirectResponse
    {
        if ($invitation->status === 'accepted') {
            return back()->withErrors(['error' => 'Cannot revoke an accepted invitation.']);
        }

        $invitation->update(['status' => 'revoked']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($invitation)
            ->log('Invitation revoked');

        return back()->with('success', 'Invitation revoked successfully.');
    }
}
