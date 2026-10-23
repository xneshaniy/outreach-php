<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentWorkspace !== null;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['archive', 'restore', 'delete', 'assign', 'add_to_list', 'remove_from_list'])],
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['integer', 'exists:leads,id'],
            'owner_id' => ['required_if:action,assign', 'exists:users,id'],
            'lead_list_id' => ['required_if:action,add_to_list,remove_from_list', 'exists:lead_lists,id'],
        ];
    }
}