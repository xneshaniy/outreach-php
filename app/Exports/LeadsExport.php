<?php

namespace App\Exports;

use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeadsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(protected Enumerable $leads)
    {
    }

    public function collection(): Enumerable
    {
        return $this->leads;
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Job Title',
            'Company',
            'Status',
            'Source',
            'Owner',
            'Follow Up At',
            'Created At',
        ];
    }

    public function map($lead): array
    {
        return [
            $lead->first_name,
            $lead->last_name,
            $lead->email,
            $lead->phone,
            $lead->job_title,
            $lead->company?->name,
            $lead->status,
            $lead->source,
            $lead->owner?->name,
            optional($lead->follow_up_at)->format('Y-m-d H:i:s'),
            optional($lead->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
