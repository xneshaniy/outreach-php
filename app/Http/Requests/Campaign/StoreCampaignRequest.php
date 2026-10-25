<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentWorkspace !== null
            && $this->user()->can('create campaigns');
    }

    public function rules(): array
    {
        $workspaceId = $this->user()->currentWorkspace->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'email_account_id' => ['nullable', 'exists:email_accounts,id', Rule::exists('email_accounts', 'id')->where('workspace_id', $workspaceId)->where('is_active', true)],
            'email_account_ids' => ['nullable', 'array'],
            'email_account_ids.*' => ['exists:email_accounts,id', Rule::exists('email_accounts', 'id')->where('workspace_id', $workspaceId)],
            'distribution_strategy' => ['sometimes', 'string', Rule::in(['round_robin', 'capacity_based', 'account_based'])],
            'allocations' => ['nullable', 'array'],
            'start_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'string'],
            'lead_list_id' => ['nullable', 'exists:lead_lists,id', Rule::exists('lead_lists', 'id')->where('workspace_id', $workspaceId)],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'sending_window' => ['nullable', 'array'],
            'sending_window.start' => ['required_with:sending_window', 'date_format:H:i'],
            'sending_window.end' => ['required_with:sending_window', 'date_format:H:i', 'after:sending_window.start'],
            'sending_window.days' => ['sometimes', 'array'],
            'sending_window.days.*' => ['integer', 'between:0,6'],
            'daily_send_limit' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'delay_between_emails' => ['sometimes', 'integer', 'min:0', 'max:86400'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'steps.*.subject' => ['required', 'string', 'max:255'],
            'steps.*.body_html' => ['required', 'string'],
            'steps.*.body_text' => ['nullable', 'string'],
            'steps.*.email_account_id' => ['nullable', 'exists:email_accounts,id', Rule::exists('email_accounts', 'id')->where('workspace_id', $workspaceId)->where('is_active', true)],
            'steps.*.delay_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'steps.*.delay_hours' => ['sometimes', 'integer', 'min:0', 'max:23'],
            'steps.*.delay_minutes' => ['sometimes', 'integer', 'min:0', 'max:59'],
            'steps.*.conditions' => ['nullable', 'array'],
            'steps.*.is_active' => ['sometimes', 'boolean'],
        ];
    }
}