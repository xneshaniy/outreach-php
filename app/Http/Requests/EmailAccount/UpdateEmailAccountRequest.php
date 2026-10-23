<?php

namespace App\Http\Requests\EmailAccount;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentWorkspace !== null
            && $this->user()->can('manage email accounts');
    }

    public function rules(): array
    {
        $workspaceId = $this->user()->currentWorkspace->id;
        $accountId = $this->route('email_account')->id ?? null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('email_accounts', 'normalized_email')->where('workspace_id', $workspaceId)->ignore($accountId)],
            'provider' => ['sometimes', 'string', Rule::in(['smtp', 'gmail', 'outlook', 'yahoo', 'custom'])],
            'host' => ['sometimes', 'required', 'string', 'max:255'],
            'port' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['sometimes', 'required', 'string', Rule::in(['tls', 'ssl', 'none'])],
            'username' => ['sometimes', 'required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'signature_html' => ['nullable', 'string'],
            'signature_text' => ['nullable', 'string'],
            'daily_send_limit' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'is_active' => ['sometimes', 'boolean'],
            'track_opens' => ['sometimes', 'boolean'],
            'track_clicks' => ['sometimes', 'boolean'],
        ];
    }
}