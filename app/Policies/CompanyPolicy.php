<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Company;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function view(User $user, Company $company): bool
    {
        return $company->workspace_id === $user->currentWorkspace?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function update(User $user, Company $company): bool
    {
        return $company->workspace_id === $user->currentWorkspace?->id;
    }

    public function delete(User $user, Company $company): bool
    {
        return $company->workspace_id === $user->currentWorkspace?->id
            && $user->can('delete companies');
    }
}