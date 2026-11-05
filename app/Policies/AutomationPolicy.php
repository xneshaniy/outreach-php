<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Automation;

class AutomationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function view(User $user, Automation $automation): bool
    {
        return $automation->workspace_id === $user->currentWorkspace?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function update(User $user, Automation $automation): bool
    {
        return $automation->workspace_id === $user->currentWorkspace?->id;
    }

    public function delete(User $user, Automation $automation): bool
    {
        return $automation->workspace_id === $user->currentWorkspace?->id;
    }
}
