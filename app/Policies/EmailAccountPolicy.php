<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EmailAccount;
use Illuminate\Auth\Access\Response;

class EmailAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function view(User $user, EmailAccount $account): bool
    {
        return $account->workspace_id === $user->currentWorkspace?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentWorkspace !== null
            && $user->can('manage email accounts');
    }

    public function update(User $user, EmailAccount $account): bool
    {
        return $account->workspace_id === $user->currentWorkspace?->id
            && $user->can('manage email accounts');
    }

    public function delete(User $user, EmailAccount $account): bool
    {
        return $account->workspace_id === $user->currentWorkspace?->id
            && $user->can('manage email accounts');
    }

    public function test(User $user, EmailAccount $account): bool
    {
        return $account->workspace_id === $user->currentWorkspace?->id;
    }
}