<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email_task_assigned' => ['boolean'],
            'email_task_due' => ['boolean'],
            'email_task_overdue' => ['boolean'],
            'email_lead_assigned' => ['boolean'],
            'email_campaign_completed' => ['boolean'],
            'email_deal_won' => ['boolean'],
            'email_daily_summary' => ['boolean'],
            'in_app_task_assigned' => ['boolean'],
            'in_app_task_due' => ['boolean'],
            'in_app_mentions' => ['boolean'],
        ];
    }
}