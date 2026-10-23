<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Task;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentWorkspace !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', Rule::in([Task::TYPE_TASK, Task::TYPE_CALL, Task::TYPE_EMAIL, Task::TYPE_MEETING, Task::TYPE_NOTE, Task::TYPE_FOLLOW_UP])],
            'status' => ['sometimes', 'string', Rule::in([Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED, Task::STATUS_CANCELLED])],
            'priority' => ['sometimes', 'string', Rule::in([Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH, Task::PRIORITY_URGENT])],
            'due_at' => ['nullable', 'date'],
            'remind_at' => ['nullable', 'date', 'before_or_equal:due_at'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}