<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Http\Requests\Settings\UpdateWorkspaceRequest;
use App\Http\Requests\Settings\UpdateNotificationRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSetting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function profile(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Profile', [
            'user' => array_merge(
                $user->only(['id', 'name', 'email', 'phone', 'job_title', 'timezone', 'locale', 'avatar_path']),
                ['current_workspace' => $user->currentWorkspace?->only(['id', 'name', 'slug'])]
            ),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => bcrypt($request->validated()['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function workspace(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $settings = WorkspaceSetting::where('workspace_id', $workspace->id)
            ->where('is_public', true)
            ->get()
            ->keyBy('key')
            ->map(fn($s) => $s->getTypedValue())
            ->toArray();

        return Inertia::render('Settings/Workspace', [
            'workspace' => $workspace,
            'settings' => $settings,
        ]);
    }

    public function updateWorkspace(UpdateWorkspaceRequest $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        abort_if($workspace === null, 403);

        $this->authorize('update', $workspace);

        $workspace->update($request->only(['name', 'description', 'primary_color', 'secondary_color']));

        // Update settings
        foreach ($request->except(['name', 'description', 'primary_color', 'secondary_color', '_token', '_method']) as $key => $value) {
            // The settings table stores scalar values only.
            if ($value !== null && ! is_scalar($value)) {
                continue;
            }

            $workspace->setSetting(
                $key,
                $value,
                is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string'),
                true
            );
        }

        return back()->with('success', 'Workspace settings updated.');
    }

    public function notifications(Request $request): Response
    {
        $user = $request->user();
        $preferences = $user->preferences ?? [];

        return Inertia::render('Settings/Notifications', [
            'preferences' => array_merge([
                'email_task_assigned' => true,
                'email_task_due' => true,
                'email_task_overdue' => true,
                'email_lead_assigned' => true,
                'email_campaign_completed' => true,
                'email_deal_won' => true,
                'email_daily_summary' => false,
                'in_app_task_assigned' => true,
                'in_app_task_due' => true,
                'in_app_mentions' => true,
            ], $preferences),
        ]);
    }

    public function updateNotifications(UpdateNotificationRequest $request): RedirectResponse
    {
        $request->user()->update([
            'preferences' => array_merge($request->user()->preferences ?? [], $request->validated()),
        ]);

        return back()->with('success', 'Notification preferences updated.');
    }

    public function appearance(Request $request): Response
    {
        return Inertia::render('Settings/Appearance', [
            'theme' => $request->user()->preferences['theme'] ?? 'light',
        ]);
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        $request->validate([
            'theme' => 'required|in:light,dark,system',
        ]);

        $request->user()->update([
            'preferences' => array_merge($request->user()->preferences ?? [], [
                'theme' => $request->theme,
            ]),
        ]);

        return back()->with('success', 'Appearance updated.');
    }

    public function integrations(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        return Inertia::render('Settings/Integrations', [
            'workspace' => $workspace,
            'availableIntegrations' => [
                [
                    'id' => 'google_sheets',
                    'name' => 'Google Sheets',
                    'description' => 'Import contacts directly from Google Sheets shareable links with automatic column mapping.',
                    'status' => 'connected',
                    'badge' => 'Active',
                    'action_label' => 'Import Leads',
                    'action_url' => route('leads.import.form'),
                ],
                [
                    'id' => 'csv',
                    'name' => 'CSV / Text Import',
                    'description' => 'Upload standard CSV files to create or update leads in bulk.',
                    'status' => 'connected',
                    'badge' => 'Active',
                    'action_label' => 'Upload CSV',
                    'action_url' => route('leads.import.form'),
                ],
                [
                    'id' => 'webhook',
                    'name' => 'Webhooks',
                    'description' => 'Receive real-time events via HTTP callbacks',
                    'status' => 'available',
                    'badge' => 'Coming Soon',
                ],
                [
                    'id' => 'zapier',
                    'name' => 'Zapier',
                    'description' => 'Connect with 5,000+ apps via automated zaps',
                    'status' => 'available',
                    'badge' => 'Coming Soon',
                ],
                [
                    'id' => 'slack',
                    'name' => 'Slack',
                    'description' => 'Send notification alerts to designated Slack channels',
                    'status' => 'available',
                    'badge' => 'Coming Soon',
                ],
            ],
        ]);
    }

    public function users(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $members = $workspace->users()
            ->wherePivot('is_active', true)
            ->withPivot('role_id', 'is_owner', 'joined_at', 'last_accessed_at')
            ->get();

        $roles = \Spatie\Permission\Models\Role::where('workspace_id', $workspace->id)
            ->orWhereNull('workspace_id')
            ->get();

        $pendingInvitations = \App\Models\Invitation::where('workspace_id', $workspace->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', \Illuminate\Support\Carbon::now())
            ->with('invitedBy:id,name')
            ->latest()
            ->get();

        return Inertia::render('Settings/Users', [
            'workspace' => $workspace,
            'members' => $members,
            'roles' => $roles,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }

    public function inviteUser(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        abort_if($workspace === null, 403);

        $this->authorize('manageUsers', $workspace);

        $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'nullable|string',
            'role_id' => 'nullable',
        ]);

        $email = strtolower(trim($request->email));

        // 1. Check if user already exists
        $user = User::where('email', $email)->first();

        if ($user) {
            if ($workspace->users()->where('user_id', $user->id)->exists()) {
                return back()->with('error', 'User is already a member of this workspace.');
            }

            $workspace->users()->attach($user->id, [
                'role_id' => $request->role_id,
                'is_owner' => false,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            return back()->with('success', "{$user->name} has been added to the workspace.");
        }

        // 2. If user does not exist, create an invitation since public registration is disabled
        $existingInvite = \App\Models\Invitation::where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', \Illuminate\Support\Carbon::now())
            ->first();

        if ($existingInvite) {
            return back()->with('error', 'An active invitation is already pending for this email address.');
        }

        $pair = \App\Models\Invitation::createTokenPair();
        $assignedRole = $request->input('role', 'user');
        if (!in_array($assignedRole, ['user', 'admin', 'manager', 'campaign_manager'])) {
            $assignedRole = 'user';
        }

        $invitation = \App\Models\Invitation::create([
            'workspace_id' => $workspace->id,
            'email' => $email,
            'role' => $assignedRole,
            'token_hash' => $pair['hash'],
            'status' => 'pending',
            'invited_by_id' => auth()->id(),
            'expires_at' => \Illuminate\Support\Carbon::now()->addDays(7),
        ]);

        $inviteUrl = url("/invite/{$pair['raw']}");

        if (function_exists('activity')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($invitation)
                ->withProperties(['email' => $email, 'role' => $assignedRole])
                ->log('Invitation created from Workspace Settings');
        }

        return back()->with('success', "Invitation generated for {$email}! Share this link: {$inviteUrl}");
    }

    public function removeUser(Request $request, User $user): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        abort_if($workspace === null, 403);

        $this->authorize('manageUsers', $workspace);

        abort_unless($workspace->users()->whereKey($user->id)->exists(), 404);

        if ($workspace->users()->where('user_id', $user->id)->wherePivot('is_owner', true)->exists()) {
            return back()->with('error', 'Cannot remove workspace owner.');
        }

        $workspace->users()->detach($user->id);

        return back()->with('success', 'User removed from workspace.');
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        abort_if($workspace === null, 403);

        $this->authorize('manageUsers', $workspace);

        abort_unless($workspace->users()->whereKey($user->id)->exists(), 404);

        $request->validate([
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $workspace->users()->updateExistingPivot($user->id, [
            'role_id' => $request->role_id,
        ]);

        return back()->with('success', 'User role updated.');
    }
}