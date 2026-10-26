<?php

namespace App\Http\Requests\EmailAccount;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmailAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user || !$user->currentWorkspace) {
            return false;
        }

        return $user->is_super_admin
            || $user->isOwnerOf($user->currentWorkspace)
            || $user->can('manage email accounts')
            || $user->can('create email accounts');
    }

    public function rules(): array
    {
        $workspaceId = $this->user()->currentWorkspace->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('email_accounts', 'normalized_email')->where('workspace_id', $workspaceId)],
            'provider' => ['required', 'string', Rule::in(['smtp', 'gmail', 'outlook', 'yahoo', 'custom'])],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'string', Rule::in(['tls', 'ssl', 'none'])],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'signature_html' => ['nullable', 'string'],
            'signature_text' => ['nullable', 'string'],
            'daily_send_limit' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'track_opens' => ['sometimes', 'boolean'],
            'track_clicks' => ['sometimes', 'boolean'],
        ];
    }
}