<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadList;
use App\Models\Company;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Statement;

class LeadService
{
    public function createLead(array $data, Workspace $workspace, User $creator, bool $autoEnrich = true): Lead
    {
        return DB::transaction(function () use ($data, $workspace, $creator, $autoEnrich) {
            $normalizedEmail = Str::lower(trim($data['email']));

            // Check for duplicate
            $existing = Lead::where('workspace_id', $workspace->id)
                ->where('normalized_email', $normalizedEmail)
                ->first();

            if ($existing) {
                throw new \Exception("Lead with email {$data['email']} already exists");
            }

            // Find or create company
            $companyId = null;
            if (!empty($data['company']) || !empty($data['company_domain'])) {
                $company = $this->findOrCreateCompany($workspace, $creator, $data);
                $companyId = $company?->id;
            }

            $lead = Lead::create([
                'workspace_id' => $workspace->id,
                'company_id' => $companyId,
                'owner_id' => $data['owner_id'] ?? null,
                'created_by' => $creator?->id ?? $workspace->users()->first()?->id ?? User::first()?->id ?? 1,
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
                'email' => $data['email'],
                'normalized_email' => $normalizedEmail,
                'phone' => $data['phone'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'department' => $data['department'] ?? null,
                'seniority' => $data['seniority'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'twitter_url' => $data['twitter_url'] ?? null,
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'website' => $data['website'] ?? null,
                'industry' => $data['industry'] ?? null,
                'source' => $data['source'] ?? 'manual',
                'status' => $data['status'] ?? 'new',
                'pipeline_stage' => $data['pipeline_stage'] ?? 'new_lead',
                'assigned_campaign_id' => $data['assigned_campaign_id'] ?? null,
                'tags' => $data['tags'] ?? null,
                'notes' => $data['notes'] ?? null,
                'custom_fields' => $data['custom_fields'] ?? null,
                'follow_up_at' => $data['follow_up_at'] ?? null,
            ]);

            app(\App\Services\LeadScoreService::class)->calculateScore($lead);

            // Auto-fill missing details via AI if any key detail is missing and auto-enrichment is requested
            if ($autoEnrich) {
                $lead = $this->autoFillMissingLeadDetails($lead, $workspace);
            }

            return $lead;
        });
    }

    public function updateLead(Lead $lead, array $data): Lead
    {
        return DB::transaction(function () use ($lead, $data) {
            if (isset($data['email']) && $data['email'] !== $lead->email) {
                $normalizedEmail = Str::lower(trim($data['email']));
                $existing = Lead::where('workspace_id', $lead->workspace_id)
                    ->where('normalized_email', $normalizedEmail)
                    ->where('id', '!=', $lead->id)
                    ->first();

                if ($existing) {
                    throw new \Exception("Lead with email {$data['email']} already exists");
                }
            }

            // Handle company
            if (isset($data['company']) || isset($data['company_domain'])) {
                $creatorUser = auth()->user() ?? $lead->creator ?? $lead->workspace?->users()->first() ?? User::first();
                if ($creatorUser) {
                    $company = $this->findOrCreateCompany($lead->workspace, $creatorUser, $data);
                    $data['company_id'] = $company?->id;
                }
            }

            $lead->update(array_merge($data, [
                'normalized_email' => isset($data['email']) ? Str::lower(trim($data['email'])) : $lead->normalized_email,
            ]));

            app(\App\Services\LeadScoreService::class)->calculateScore($lead);

            // Auto-fill missing details via AI if needed
            $lead = $this->autoFillMissingLeadDetails($lead->fresh(), $lead->workspace);

            return $lead->fresh();
        });
    }

public function findOrCreateCompany(Workspace $workspace, ?User $creator, array $data): ?Company
    {
        $creator = $creator ?? auth()->user() ?? $workspace->users()->first() ?? User::first();
        if (!$creator) {
            return null;
        }
        return app(\App\Services\UnifiedMatchingService::class)
            ->matchOrCreateCompany($workspace, $data, $creator);
    }

    public function bulkCreateLeads(array $leadsData, Workspace $workspace, ?User $creator = null, array $options = []): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'duplicates' => 0,
            'errors' => [],
        ];

        $skipDuplicates = $options['skip_duplicates'] ?? false;
        $updateExisting = $options['update_existing'] ?? false;
        $creator = $creator ?? auth()->user() ?? $workspace->users()->first() ?? User::first();

        foreach ($leadsData as $index => $data) {
            try {
                $normalizedEmail = Str::lower(trim($data['email'] ?? ''));

                if (!$normalizedEmail || !filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'email' => $data['email'] ?? '',
                        'error' => 'Invalid or missing email',
                    ];
                    continue;
                }

                $existing = Lead::where('workspace_id', $workspace->id)
                    ->where('normalized_email', $normalizedEmail)
                    ->first();

                if ($existing) {
                    if ($updateExisting) {
                        $this->updateLead($existing, $data);
                        $results['updated']++;
                    } else {
                        $results['duplicates']++;
                    }
                    continue;
                }

                $autoEnrich = $options['auto_enrich'] ?? false;
                $this->createLead($data, $workspace, $creator, $autoEnrich);
                $results['created']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'row' => $index + 1,
                    'email' => $data['email'] ?? '',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function addToList(Lead $lead, LeadList $list, User $user): void
    {
        $list->leads()->syncWithoutDetaching([
            $lead->id => ['added_by' => $user->id],
        ]);
    }

    public function removeFromList(Lead $lead, LeadList $list): void
    {
        $list->leads()->detach($lead->id);
    }

    public function mergeLeads(Lead $primary, Lead $secondary): Lead
    {
        return DB::transaction(function () use ($primary, $secondary) {
            // Move relationships
            $secondary->campaignRecipients()->update(['lead_id' => $primary->id]);
            $secondary->tasks()->update(['related_id' => $primary->id]);
            $secondary->messages()->update(['lead_id' => $primary->id]);
            $secondary->conversations()->update(['lead_id' => $primary->id]);
            $secondary->deals()->update(['lead_id' => $primary->id]);

            // Merge tags
            $primaryTags = $primary->tags ?? [];
            $secondaryTags = $secondary->tags ?? [];
            $primary->tags = array_unique(array_merge($primaryTags, $secondaryTags));

            // Merge notes
            if ($secondary->notes) {
                $primary->notes = ($primary->notes ? $primary->notes . "\n\n---\n\n" : '') . $secondary->notes;
            }

            // Merge custom fields
            $primaryCustom = $primary->custom_fields ?? [];
            $secondaryCustom = $secondary->custom_fields ?? [];
            $primary->custom_fields = array_merge($primaryCustom, $secondaryCustom);

            $primary->save();
            $secondary->delete();

            return $primary->fresh();
        });
    }

    public function archiveLead(Lead $lead): void
    {
        DB::transaction(function () use ($lead) {
            // Stop any active campaign sends for this lead before hiding it.
            $lead->activeCampaignRecipients()->update([
                'status' => 'suppressed',
                'next_send_at' => null,
            ]);

            $lead->update(['status' => 'archived']);
            $lead->delete(); // soft delete: archived leads are hidden from lists
        });
    }

    public function restoreLead(Lead $lead): void
    {
        DB::transaction(function () use ($lead) {
            if ($lead->trashed()) {
                $lead->restore();
            }

            $lead->update(['status' => 'new']);
        });
    }

    public function search(Workspace $workspace, array $filters = [], array $sort = [], int $perPage = 25)
    {
        $query = Lead::where('workspace_id', $workspace->id)
            ->with(['company', 'owner', 'creator'])
            ->withCount(['campaignRecipients', 'tasks', 'messages']);

        // Search term
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        // Pipeline stage filter
        if (!empty($filters['pipeline_stage'])) {
            $query->where('pipeline_stage', $filters['pipeline_stage']);
        }

        // Country filter
        if (!empty($filters['country_code'])) {
            $query->where('country_code', $filters['country_code']);
        }

        // Owner filter
        if (!empty($filters['owner_id'])) {
            $query->ownedBy($filters['owner_id']);
        }

        // Source filter
        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        // Tags filter
        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Follow up filter
        if (!empty($filters['needs_follow_up'])) {
            $query->needsFollowUp();
        }

        // Company filter
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        // Sorting
        $sortField = $sort['field'] ?? 'created_at';
        $sortDirection = strtolower($sort['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sortField === 'name') {
            $query->orderBy('first_name', $sortDirection)->orderBy('last_name', $sortDirection);
        } elseif ($sortField === 'score') {
            $query->orderBy('score', $sortDirection);
        } elseif ($sortField === 'source') {
            $query->orderBy('source', $sortDirection);
        } elseif ($sortField === 'company') {
            $query->leftJoin('companies', 'leads.company_id', '=', 'companies.id')
                ->orderBy('companies.name', $sortDirection)
                ->select('leads.*');
        } elseif (in_array($sortField, ['created_at', 'first_name', 'last_name', 'email', 'status', 'follow_up_at', 'last_activity_at'])) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function export(Workspace $workspace, array $filters = []): \Illuminate\Support\Collection
    {
        return $this->search($workspace, $filters, [], 10000)->getCollection();
    }

    /**
     * Automatically fill missing lead details (company name, country, job title) using AI.
     */
    public function autoFillMissingLeadDetails(Lead $lead, Workspace $workspace): Lead
    {
        // Check if any key details are missing
        $needsCompany = empty($lead->company);
        $needsCountry = empty($lead->country);
        $needsJobTitle = empty($lead->job_title);

        if (!$needsCompany && !$needsCountry && !$needsJobTitle) {
            return $lead;
        }

        try {
            $aiService = app(AIService::class);
            $leadData = [
                'first_name' => $lead->first_name ?? '',
                'last_name' => $lead->last_name ?? '',
                'company' => $lead->company ?? '',
                'website' => $lead->website ?? '',
                'job_title' => $lead->job_title ?? '',
                'industry' => $lead->industry ?? '',
                'linkedin_url' => $lead->linkedin_url ?? '',
            ];

            $result = $aiService->researchLead($workspace, $leadData);

            if ($result['success'] && !empty($result['data'])) {
                $data = $result['data'];
                $updates = [];

                if ($needsCompany && !empty($data['company_name'])) {
                    $companyName = $data['company_name'];
                    $updates['company'] = $companyName;
                    $company = $this->findOrCreateCompany($workspace, auth()->user() ?? $lead->creator, array_merge($leadData, ['company' => $companyName]));
                    if ($company) {
                        $updates['company_id'] = $company->id;
                    }
                } elseif ($needsCompany && !empty($data['company_overview'])) {
                    // Extract a clean company name or use the overview snippet
                    $companyName = $leadData['company'] ?: (parse_url($lead->website ?? '', PHP_URL_HOST) ?? '');
                    if (empty($companyName) && !empty($data['industry'])) {
                        $companyName = 'Company in ' . $data['industry'];
                    }
                    if (!empty($companyName)) {
                        $updates['company'] = $companyName;
                        $company = $this->findOrCreateCompany($workspace, auth()->user() ?? $lead->creator, array_merge($leadData, ['company' => $companyName]));
                        if ($company) {
                            $updates['company_id'] = $company->id;
                        }
                    }
                }

                if ($needsCountry && !empty($data['country'])) {
                    $updates['country'] = $data['country'];
                }

                if ($needsJobTitle && !empty($data['job_title'])) {
                    $updates['job_title'] = $data['job_title'];
                }

                if (!empty($updates)) {
                    $lead->update($updates);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Auto-fill lead details via AI failed: ' . $e->getMessage());
        }

        return $lead->fresh();
    }
}