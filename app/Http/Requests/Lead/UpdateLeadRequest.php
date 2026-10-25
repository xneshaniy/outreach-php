<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentWorkspace !== null;
    }

    public function rules(): array
    {
        $workspaceId = $this->user()->currentWorkspace->id;
        $leadId = $this->route('lead')?->id;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('leads', 'normalized_email')->where('workspace_id', $workspaceId)->ignore($leadId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'seniority' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'company_domain' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'company_size' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'linkedin_url' => ['nullable', 'string', 'max:255'],
            'twitter_url' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(['new', 'contacted', 'qualified', 'unqualified', 'converted', 'archived'])],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'category' => ['nullable', 'string'],
            'pipeline_stage' => ['nullable', 'string'],
            'assigned_campaign_id' => ['nullable', 'exists:campaigns,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
            'follow_up_at' => ['nullable', 'date'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A lead with this email already exists in your workspace.',
        ];
    }
}