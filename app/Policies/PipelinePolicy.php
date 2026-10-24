<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Pipeline;
use Illuminate\Auth\Access\Response;

class PipelinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function view(User $user, Pipeline $pipeline): bool
    {
        return $pipeline->workspace_id === $user->currentWorkspace?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentWorkspace !== null
            && $user->can('manage pipelines');
    }

    public function update(User $user, Pipeline $pipeline): bool
    {
        return $pipeline->workspace_id === $user->currentWorkspace?->id
            && $user->can('manage pipelines');
    }

    public function delete(User $user, Pipeline $pipeline): bool
    {
        return $pipeline->workspace_id === $user->currentWorkspace?->id
            && $user->can('manage pipelines');
    }

    public function manageStages(User $user, Pipeline $pipeline): bool
    {
        return $pipeline->workspace_id === $user->currentWorkspace?->id
            && $user->can('manage pipelines');
    }
}