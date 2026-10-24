<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Campaign;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentWorkspace !== null
            && $this->user()->can('manage campaigns');
    }

    public function rules(): array
    {
        $workspaceId = $this->user()->currentWorkspace->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'email_account_id' => ['nullable', 'exists:email_accounts,id', Rule::exists('email_accounts', 'id')->where('workspace_id', $workspaceId)->where('is_active', true)],
            'lead_list_id' => ['nullable', 'exists:lead_lists,id', Rule::exists('lead_lists', 'id')->where('workspace_id', $workspaceId)],
            'status' => ['sometimes', 'string', Rule::in(Campaign::STATUSES)],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'sending_window' => ['nullable', 'array'],
            'sending_window.start' => ['required_with:sending_window', 'date_format:H:i'],
            'sending_window.end' => ['required_with:sending_window', 'date_format:H:i', 'after:sending_window.start'],
            'sending_window.days' => ['sometimes', 'array'],
            'sending_window.days.*' => ['integer', 'between:0,6'],
            'daily_send_limit' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'delay_between_emails' => ['sometimes', 'integer', 'min:0', 'max:86400'],
        ];
    }
}