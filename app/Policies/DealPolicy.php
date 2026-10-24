<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\User;

class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function view(User $user, Deal $deal): bool
    {
        return $deal->workspace_id === $user->currentWorkspace?->id;
    }

    /**
     * Deals are always created inside a pipeline, so the pipeline (when
     * supplied by the controller) must belong to the current workspace.
     */
    public function create(User $user, ?Pipeline $pipeline = null): bool
    {
        if ($user->currentWorkspace === null) {
            return false;
        }

        if ($pipeline !== null && $pipeline->workspace_id !== $user->currentWorkspace->id) {
            return false;
        }

        return $user->can('create deals');
    }

    public function update(User $user, Deal $deal): bool
    {
        return $deal->workspace_id === $user->currentWorkspace?->id;
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $deal->workspace_id === $user->currentWorkspace?->id
            && $user->can('delete deals');
    }
}
