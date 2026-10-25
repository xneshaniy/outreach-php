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

        return Inertia::render('Admin/Invitations/Index', [
            'invitations' => $invitations,
            'filters' => $request->only(['search', 'status']),
            'workspaces' => Workspace::select('id', 'name')->get(),
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
            'role' => 'required|string|in:user,admin,manager,campaign_manager',
            'workspace_id' => 'nullable|exists:workspaces,id',
            'expires_in_days' => 'required|integer|min:1|max:30',
        ]);

        // Check if there is already an active pending invitation
        $existing = Invitation::where('email', $validated['email'])
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existing) {
            return back()->withErrors(['email' => 'An active invitation is already pending for this email address.']);
        }

        $pair = Invitation::createTokenPair();

        $invitation = Invitation::create([
            'workspace_id' => $validated['workspace_id'] ?? Workspace::first()?->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token_hash' => $pair['hash'],
            'status' => 'pending',
            'invited_by_id' => auth()->id(),
            'expires_at' => Carbon::now()->addDays($validated['expires_in_days']),
        ]);

        $inviteUrl = url("/invite/{$pair['raw']}");

        activity()
            ->causedBy(auth()->user())
            ->performedOn($invitation)
            ->withProperties(['email' => $invitation->email, 'role' => $invitation->role])
            ->log('Invitation created');

        return back()->with('invitationSuccess', [
            'message' => 'Invitation created successfully.',
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

        activity()
            ->causedBy(auth()->user())
            ->performedOn($invitation)
            ->log('Invitation token regenerated and resent');

        return back()->with('invitationSuccess', [
            'message' => 'Invitation regenerated successfully.',
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
