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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($hash, $validated) {
            /** @var Invitation $invitation */
            $invitation = Invitation::where('token_hash', $hash)->lockForUpdate()->first();

            if (!$invitation || !$invitation->isPending()) {
                return back()->withErrors(['error' => 'This invitation is no longer valid, has expired, or has already been accepted.']);
            }

            // Find or create user
            $user = User::where('email', $invitation->email)->first();

            $isSuperAdmin = in_array($invitation->role, ['super_admin', 'Super Admin'])
                || ($invitation->role === 'admin' && !User::where('is_super_admin', true)->exists());

            if ($user) {
                $user->update([
                    'name' => $validated['name'],
                    'password' => Hash::make($validated['password']),
                    'is_active' => true,
                    'is_super_admin' => $user->is_super_admin || $isSuperAdmin,
                ]);
            } else {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $invitation->email,
                    'password' => Hash::make($validated['password']),
                    'is_active' => true,
                    'is_super_admin' => $isSuperAdmin,
                ]);
            }

            // Determine workspace
            $workspace = $invitation->workspace_id
                ? Workspace::find($invitation->workspace_id)
                : Workspace::first();

            if (!$workspace) {
                $workspace = Workspace::create([
                    'name' => $user->name . "'s Workspace",
                    'slug' => \Illuminate\Support\Str::slug($user->name . '-workspace') . '-' . \Illuminate\Support\Str::random(6),
                    'created_by' => $user->id,
                    'is_active' => true,
                ]);
            }

            // Resolve role from roles table
            $roleName = match ($invitation->role) {
                'super_admin' => 'Super Admin',
                'workspace_admin', 'admin' => 'Workspace Admin',
                'campaign_operator', 'campaign_manager' => 'Campaign Operator',
                'manager' => 'Manager',
                'analyst' => 'Analyst',
                'client' => 'Client',
                default => 'User',
            };

            $roleRecord = \Spatie\Permission\Models\Role::where('name', $roleName)
                ->orWhere('name', 'Admin')
                ->first();

            $roleId = $roleRecord?->id;

            // Attach user to workspace
            if (!$workspace->users()->where('user_id', $user->id)->exists()) {
                $workspace->users()->attach($user->id, [
                    'role_id' => $roleId,
                    'is_owner' => false,
                    'is_active' => true,
                    'joined_at' => now(),
                ]);
            } else {
                $workspace->users()->updateExistingPivot($user->id, [
                    'role_id' => $roleId,
                    'is_active' => true,
                ]);
            }

            // Set current workspace
            $user->setCurrentWorkspace($workspace);

            // Sync custom module permissions if specified on the invitation
            if (!empty($invitation->permissions) && is_array($invitation->permissions)) {
                $validPermissions = \Spatie\Permission\Models\Permission::whereIn('name', $invitation->permissions)->pluck('name')->toArray();
                if (!empty($validPermissions)) {
                    $user->givePermissionTo($validPermissions);
                }
            }

            // Mark invitation accepted atomically
            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => Carbon::now(),
            ]);

            // Notify inviter if exists
            if ($invitation->invited_by_id) {
                \App\Models\CrmNotification::create([
                    'workspace_id' => $workspace->id,
                    'user_id' => $invitation->invited_by_id,
                    'type' => 'invitation_accepted',
                    'title' => 'Invitation Accepted',
                    'message' => "{$user->name} ({$user->email}) accepted your invitation and joined as {$roleName}.",
                ]);
            }

            Auth::login($user);

            activity()
                ->causedBy($user)
                ->performedOn($invitation)
                ->log('Invitation accepted and account setup completed');

            return redirect()->route('dashboard')->with('success', 'Welcome to Outreach CRM! Your account setup is complete.');
        });
    }
}
