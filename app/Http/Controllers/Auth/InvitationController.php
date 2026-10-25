<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function showAcceptForm(string $token): Response|RedirectResponse
    {
        $hash = hash('sha256', $token);
        $invitation = Invitation::with('workspace:id,name')->where('token_hash', $hash)->first();

        if (!$invitation) {
            return Inertia::render('Error', [
                'status' => 404,
                'message' => 'Invalid invitation link or token not found.',
            ]);
        }

        if ($invitation->status === 'accepted') {
            return redirect()->route('login')->with('message', 'This invitation has already been accepted. Please sign in.');
        }

        if ($invitation->status === 'revoked' || $invitation->isExpired()) {
            return Inertia::render('Error', [
                'status' => 410,
                'message' => 'This invitation has expired or been revoked by the administrator.',
            ]);
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'token' => $token,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'workspaceName' => $invitation->workspace?->name ?? 'Default Workspace',
        ]);
    }

    public function accept(string $token, Request $request): RedirectResponse
    {
        $hash = hash('sha256', $token);
        $invitation = Invitation::where('token_hash', $hash)->first();

        if (!$invitation || !$invitation->isPending()) {
            return back()->withErrors(['error' => 'This invitation is no longer valid or has already expired.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Find or create user
        $user = User::where('email', $invitation->email)->first();

        if ($user) {
            $user->update([
                'name' => $validated['name'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);
        } else {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => Hash::make($validated['password']),
                'is_active' => true,
                'is_super_admin' => $invitation->role === 'admin' && !User::where('is_super_admin', true)->exists(),
            ]);
        }

        // Attach to workspace
        if ($invitation->workspace_id) {
            $workspace = Workspace::find($invitation->workspace_id);
            if ($workspace && !$workspace->users()->where('user_id', $user->id)->exists()) {
                $workspace->users()->attach($user->id, ['role' => $invitation->role]);
            }
        } else {
            $defaultWs = Workspace::first();
            if ($defaultWs && !$defaultWs->users()->where('user_id', $user->id)->exists()) {
                $defaultWs->users()->attach($user->id, ['role' => $invitation->role]);
            }
        }

        // Mark invitation accepted
        $invitation->update([
            'status' => 'accepted',
            'accepted_at' => Carbon::now(),
        ]);

        Auth::login($user);

        activity()
            ->causedBy($user)
            ->performedOn($invitation)
            ->log('Invitation accepted and account setup completed');

        return redirect()->route('dashboard')->with('success', 'Welcome to Outreach CRM! Your account is ready.');
    }
}
