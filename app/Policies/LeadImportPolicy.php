<?php

namespace App\Policies;

use App\Models\LeadImport;
use App\Models\User;

class LeadImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function view(User $user, LeadImport $import): bool
    {
        return $import->workspace_id === $user->currentWorkspace?->id;
    }

    public function delete(User $user, LeadImport $import): bool
    {
        return $import->workspace_id === $user->currentWorkspace?->id
            && $user->can('import leads');
    }
}
