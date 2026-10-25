<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Task;
use App\Models\Lead;
use App\Models\Company;
use App\Models\Deal;
use App\Models\User;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $query = Task::where('workspace_id', $workspace->id)
            ->with(['assignee', 'creator', 'related'])
            ->where('status', '!=', Task::STATUS_COMPLETED);

        // Filters
        if ($request->filled('assignee_id')) {
            $query->where('assigned_to', $request->assignee_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('due_from')) {
            $query->where('due_at', '>=', $request->due_from);
        }

        if ($request->filled('due_to')) {
            $query->where('due_at', '<=', $request->due_to);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // My tasks filter
        if ($request->boolean('mine')) {
            $query->where('assigned_to', $request->user()->id);
        }

        // Overdue filter
        if ($request->boolean('overdue')) {
            $query->where('due_at', '<', now());
        }

        // Due today filter
        if ($request->boolean('due_today')) {
            $query->whereDate('due_at', today());
        }

        $sortField = $request->input('sort_field', 'due_at');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSorts = ['due_at', 'created_at', 'priority', 'title', 'status'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $tasks = $query->paginate(25)->withQueryString();

        $users = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'avatar_path']);

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'filters' => $request->only(['assignee_id', 'type', 'status', 'priority', 'due_from', 'due_to', 'search', 'mine', 'overdue', 'due_today']),
            'users' => $users,
            'types' => [Task::TYPE_TASK, Task::TYPE_CALL, Task::TYPE_EMAIL, Task::TYPE_MEETING, Task::TYPE_NOTE, Task::TYPE_FOLLOW_UP],
            'statuses' => [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED, Task::STATUS_CANCELLED],
            'priorities' => [Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH, Task::PRIORITY_URGENT],
        ]);
    }

    public function create(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $users = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'avatar_path']);

        $relatedType = $request->input('related_type');
        $relatedId = $request->input('related_id');
        $related = null;

        if ($relatedType && $relatedId) {
            $model = match ($relatedType) {
                'lead' => Lead::class,
                'company' => Company::class,
                'deal' => Deal::class,
                'campaign' => Campaign::class,
                default => null,
            };
            if ($model) {
                $related = $model::find($relatedId);
            }
        }

        return Inertia::render('Tasks/Create', [
            'users' => $users,
            'types' => [Task::TYPE_TASK, Task::TYPE_CALL, Task::TYPE_EMAIL, Task::TYPE_MEETING, Task::TYPE_NOTE, Task::TYPE_FOLLOW_UP],
            'priorities' => [Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH, Task::PRIORITY_URGENT],
            'related' => $related,
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $task = Task::create(array_merge($request->validated(), [
            'workspace_id' => $request->user()->currentWorkspace->id,
            'created_by' => $request->user()->id,
        ]));

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task created successfully.');
    }

    public function show(Request $request, Task $task): Response
    {
        $this->authorize('view', $task);

        $task->load(['assignee', 'creator', 'related']);

        return Inertia::render('Tasks/Show', [
            'task' => $task,
        ]);
    }

    public function edit(Request $request, Task $task): Response
    {
        $this->authorize('update', $task);

        $workspace = $request->user()->currentWorkspace;

        $users = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'avatar_path']);

        return Inertia::render('Tasks/Edit', [
            'task' => $task,
            'users' => $users,
            'types' => [Task::TYPE_TASK, Task::TYPE_CALL, Task::TYPE_EMAIL, Task::TYPE_MEETING, Task::TYPE_NOTE, Task::TYPE_FOLLOW_UP],
            'statuses' => [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED, Task::STATUS_CANCELLED],
            'priorities' => [Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH, Task::PRIORITY_URGENT],
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    public function complete(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->complete();

        return back()->with('success', 'Task marked as complete.');
    }

    public function start(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->start();

        return back()->with('success', 'Task started.');
    }

    public function bulkComplete(Request $request): RedirectResponse
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        $workspace = $request->user()->currentWorkspace;
        $tasks = Task::where('workspace_id', $workspace->id)
            ->whereIn('id', $request->task_ids)
            ->get();

        foreach ($tasks as $task) {
            $this->authorize('update', $task);
            $task->complete();
        }

        return back()->with('success', count($tasks) . ' tasks marked as complete.');
    }
}