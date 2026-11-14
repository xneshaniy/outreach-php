<?php

namespace App\Exports;

use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeadsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    protected array $columns;

    public function __construct(
        protected Enumerable $leads,
        array $columns = []
    ) {
        $allowed = [
            'first_name', 'last_name', 'email', 'phone', 'job_title',
            'company', 'status', 'source', 'score', 'owner', 'city',
            'country', 'website', 'linkedin_url', 'follow_up_at', 'created_at'
        ];

        if (empty($columns)) {
            $this->columns = [
                'first_name', 'last_name', 'email', 'phone', 'job_title',
                'company', 'status', 'source', 'owner', 'follow_up_at', 'created_at'
            ];
        } else {
            $this->columns = array_values(array_intersect($columns, $allowed));
            if (empty($this->columns)) {
                $this->columns = [
                    'first_name', 'last_name', 'email', 'phone', 'job_title',
                    'company', 'status', 'source', 'owner', 'follow_up_at', 'created_at'
                ];
            }
        }
    }

    public function collection(): Enumerable
    {
        return $this->leads;
    }

    public function headings(): array
    {
        $titles = [
            'first_name'   => 'First Name',
            'last_name'    => 'Last Name',
            'email'        => 'Email',
            'phone'        => 'Phone',
            'job_title'    => 'Job Title',
            'company'      => 'Company',
            'status'       => 'Status',
            'source'       => 'Source',
            'score'        => 'Quality Score',
            'owner'        => 'Owner',
            'city'         => 'City',
            'country'      => 'Country',
            'website'      => 'Website',
            'linkedin_url' => 'LinkedIn URL',
            'follow_up_at' => 'Follow Up At',
            'created_at'   => 'Created At',
        ];

        return array_map(fn($col) => $titles[$col] ?? ucwords(str_replace('_', ' ', $col)), $this->columns);
    }

    public function map($lead): array
    {
        $row = [];
        foreach ($this->columns as $col) {
            $row[] = match ($col) {
                'first_name'   => $lead->first_name ?? '',
                'last_name'    => $lead->last_name ?? '',
                'email'        => $lead->email ?? '',
                'phone'        => $lead->phone ?? $lead->mobile ?? '',
                'job_title'    => $lead->job_title ?? '',
                'company'      => $lead->company?->name ?? $lead->company_name ?? '',
                'status'       => $lead->status ?? 'new',
                'source'       => $lead->source ?? '',
                'score'        => $lead->score ?? $lead->lead_quality_score ?? '',
                'owner'        => $lead->owner?->name ?? '',
                'city'         => $lead->city ?? '',
                'country'      => $lead->country ?? '',
                'website'      => $lead->website ?? '',
                'linkedin_url' => $lead->linkedin_url ?? '',
                'follow_up_at' => optional($lead->follow_up_at)->format('Y-m-d H:i:s') ?? '',
                'created_at'   => optional($lead->created_at)->format('Y-m-d H:i:s') ?? '',
                default        => $lead->{$col} ?? '',
            };
        }
        return $row;
    }
}
