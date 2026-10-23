<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Campaign;
use Illuminate\Auth\Access\Response;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $campaign->workspace_id === $user->currentWorkspace?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentWorkspace !== null
            && $user->can('create campaigns');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $campaign->workspace_id === $user->currentWorkspace?->id
            && $user->can('manage campaigns');
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $campaign->workspace_id === $user->currentWorkspace?->id
            && $user->can('delete campaigns');
    }

    public function send(User $user, Campaign $campaign): bool
    {
        return $campaign->workspace_id === $user->currentWorkspace?->id
            && $user->can('send campaigns');
    }

    public function manageRecipients(User $user, Campaign $campaign): bool
    {
        return $campaign->workspace_id === $user->currentWorkspace?->id
            && $user->can('manage campaigns');
    }
}