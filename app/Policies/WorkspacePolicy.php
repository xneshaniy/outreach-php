<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $user->hasAccessToWorkspace($workspace);
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $user->hasAccessToWorkspace($workspace)
            && ($user->isOwnerOf($workspace) || $user->can('manage workspace'));
    }

    public function manageUsers(User $user, Workspace $workspace): bool
    {
        return $user->hasAccessToWorkspace($workspace)
            && ($user->isOwnerOf($workspace) || $user->can('manage workspace users'));
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->isOwnerOf($workspace) && $user->is_super_admin;
    }
}