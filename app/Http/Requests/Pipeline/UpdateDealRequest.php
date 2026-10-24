<?php

namespace App\Http\Requests\Pipeline;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentWorkspace !== null;
    }

    public function rules(): array
    {
        $workspaceId = $this->user()->currentWorkspace->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'value' => ['sometimes', 'numeric', 'min:0', 'max:999999999.99'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'expected_close_date' => ['nullable', 'date'],
            'actual_close_date' => ['nullable', 'date'],
            'stage_id' => ['sometimes', 'required', 'exists:pipeline_stages,id'],
            'lead_id' => ['nullable', 'exists:leads,id', Rule::exists('leads', 'id')->where('workspace_id', $workspaceId)],
            'company_id' => ['nullable', 'exists:companies,id', Rule::exists('companies', 'id')->where('workspace_id', $workspaceId)],
            'owner_id' => ['nullable', 'exists:users,id'],
            'status' => ['sometimes', 'string', Rule::in(['open', 'won', 'lost'])],
            'notes' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}