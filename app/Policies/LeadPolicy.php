<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Lead;
use Illuminate\Auth\Access\Response;

class LeadPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_super_admin) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function view(User $user, Lead $lead): bool
    {
        return $lead->workspace_id === $user->currentWorkspace?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $lead->workspace_id === $user->currentWorkspace?->id;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $lead->workspace_id === $user->currentWorkspace?->id
            && $user->can('delete leads');
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $lead->workspace_id === $user->currentWorkspace?->id;
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        return $lead->workspace_id === $user->currentWorkspace?->id
            && $user->is_super_admin;
    }

    public function export(User $user): bool
    {
        return $user->currentWorkspace !== null
            && $user->can('export leads');
    }

    public function import(User $user): bool
    {
        return $user->currentWorkspace !== null
            && $user->can('import leads');
    }
}