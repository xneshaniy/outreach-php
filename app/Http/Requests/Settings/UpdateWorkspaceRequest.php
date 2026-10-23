<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentWorkspace !== null
            && $this->user()->can('manage workspace');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'primary_color' => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'daily_send_limit' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'message_retention_days' => ['sometimes', 'integer', 'min:30', 'max:2555'],
            'audit_log_retention_days' => ['sometimes', 'integer', 'min:30', 'max:2555'],
            'activity_retention_days' => ['sometimes', 'integer', 'min:30', 'max:2555'],
            'import_retention_days' => ['sometimes', 'integer', 'min:30', 'max:2555'],
        ];
    }
}