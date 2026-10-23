<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Task;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentWorkspace !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', Rule::in([Task::TYPE_TASK, Task::TYPE_CALL, Task::TYPE_EMAIL, Task::TYPE_MEETING, Task::TYPE_NOTE, Task::TYPE_FOLLOW_UP])],
            'status' => ['sometimes', 'string', Rule::in([Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED, Task::STATUS_CANCELLED])],
            'priority' => ['sometimes', 'string', Rule::in([Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH, Task::PRIORITY_URGENT])],
            'due_at' => ['nullable', 'date'],
            'remind_at' => ['nullable', 'date', 'before_or_equal:due_at'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'related_type' => ['nullable', 'string', Rule::in(['lead', 'company', 'deal', 'campaign'])],
            'related_id' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('related_type') && $this->filled('related_id')) {
                $model = match ($this->related_type) {
                    'lead' => \App\Models\Lead::class,
                    'company' => \App\Models\Company::class,
                    'deal' => \App\Models\Deal::class,
                    'campaign' => \App\Models\Campaign::class,
                    default => null,
                };

                if ($model && !$model::where('id', $this->related_id)->where('workspace_id', $this->user()->currentWorkspace->id)->exists()) {
                    $validator->errors()->add('related_id', 'The selected related record does not exist in your workspace.');
                }
            }
        });
    }
}