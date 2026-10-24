<?php

namespace App\Policies;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;

class PipelineStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function view(User $user, PipelineStage $stage): bool
    {
        return $stage->pipeline?->workspace_id === $user->currentWorkspace?->id;
    }

    public function create(User $user, Pipeline $pipeline): bool
    {
        return $pipeline->workspace_id === $user->currentWorkspace?->id
            && $user->can('manage pipelines');
    }

    public function update(User $user, PipelineStage $stage): bool
    {
        return $stage->pipeline?->workspace_id === $user->currentWorkspace?->id
            && $user->can('manage pipelines');
    }

    public function delete(User $user, PipelineStage $stage): bool
    {
        return $stage->pipeline?->workspace_id === $user->currentWorkspace?->id
            && $user->can('manage pipelines');
    }
}
