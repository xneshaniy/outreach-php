<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function view(User $user, Task $task): bool
    {
        return $task->workspace_id === $user->currentWorkspace?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function update(User $user, Task $task): bool
    {
        return $task->workspace_id === $user->currentWorkspace?->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $task->workspace_id === $user->currentWorkspace?->id
            && ($task->assigned_to === $user->id || $task->created_by === $user->id || $user->can('delete tasks'));
    }

    public function assign(User $user, Task $task): bool
    {
        return $task->workspace_id === $user->currentWorkspace?->id
            && ($task->created_by === $user->id || $user->can('manage tasks'));
    }
}