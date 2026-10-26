<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::withCount(['emailAccounts', 'campaigns'])
            ->with(['workspaces:id,name']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'suspended') {
                $query->where('is_active', false);
            } elseif ($status === 'super_admin') {
                $query->where('is_super_admin', true);
            }
        }

        $users = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status']),
            'totalUsers' => User::count(),
            'activeUsers' => User::where('is_active', true)->count(),
            'suspendedUsers' => User::where('is_active', false)->count(),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load([
            'workspaces',
            'emailAccounts' => fn($q) => $q->orderByDesc('created_at')->take(10),
            'campaigns' => fn($q) => $q->orderByDesc('created_at')->take(10),
        ]);

        $tokenStats = [
            'total_tokens' => (int) $user->tokenUsageRecords()->sum('total_tokens'),
            'total_cost' => (float) $user->tokenUsageRecords()->sum('estimated_cost'),
            'this_month_tokens' => (int) $user->tokenUsageRecords()->where('created_at', '>=', now()->startOfMonth())->sum('total_tokens'),
            'this_month_cost' => (float) $user->tokenUsageRecords()->where('created_at', '>=', now()->startOfMonth())->sum('estimated_cost'),
        ];

        $activityLogs = Activity::where('causer_type', User::class)
            ->where('causer_id', $user->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        return Inertia::render('Admin/Users/Show', [
            'viewUser' => $user,
            'tokenStats' => $tokenStats,
            'activityLogs' => $activityLogs,
        ]);
    }

    public function toggleStatus(User $user, Request $request): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot suspend your own account.']);
        }

        $request->validate([
            'is_active' => 'required|boolean',
            'reason' => 'nullable|string|max:255',
        ]);

        $newStatus = $request->boolean('is_active');

        // Prevent suspending the last active Super Admin
        if ($user->is_super_admin && !$newStatus) {
            $otherActiveSuperAdmins = User::where('is_super_admin', true)
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveSuperAdmins === 0) {
                return back()->withErrors(['error' => 'Operation blocked: You cannot suspend the only active Super Admin account.']);
            }
        }

        $user->is_active = $newStatus;
        $user->suspension_reason = $user->is_active ? null : $request->input('reason', 'Suspended by Super Admin');
        $user->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log($user->is_active ? 'User account reactivated' : 'User account suspended');

        return back()->with('success', $user->is_active ? 'User reactivated successfully.' : 'User suspended successfully.');
    }

    public function updateRoleAndLimits(User $user, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_super_admin' => 'required|boolean',
            'daily_email_limit' => 'required|integer|min:1|max:50000',
            'monthly_token_budget' => 'required|numeric|min:0|max:10000',
            'max_campaigns' => 'required|integer|min:1|max:1000',
        ]);

        if ($user->id === auth()->id() && !$validated['is_super_admin']) {
            return back()->withErrors(['error' => 'You cannot revoke your own Super Admin access.']);
        }

        // Prevent demoting the last active Super Admin
        if ($user->is_super_admin && !$validated['is_super_admin']) {
            $otherActiveSuperAdmins = User::where('is_super_admin', true)
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveSuperAdmins === 0) {
                return back()->withErrors(['error' => 'Operation blocked: System requires at least one active Super Admin.']);
            }
        }

        $user->update($validated);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties($validated)
            ->log('User role and limits updated');

        return back()->with('success', 'User limits and permissions updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        // Prevent deleting the last active Super Admin
        if ($user->is_super_admin) {
            $otherActiveSuperAdmins = User::where('is_super_admin', true)
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveSuperAdmins === 0) {
                return back()->withErrors(['error' => 'Operation blocked: Cannot delete the only active Super Admin account.']);
            }
        }

        $user->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('User soft deleted');

        return redirect()->route('admin.users.index')->with('success', 'User deleted safely.');
    }
}
