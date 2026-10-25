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
    public function createLead(array $data, Workspace $workspace, User $creator): Lead
    {
        return DB::transaction(function () use ($data, $workspace, $creator) {
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

            return Lead::create([
                'workspace_id' => $workspace->id,
                'company_id' => $companyId,
                'owner_id' => $data['owner_id'] ?? null,
                'created_by' => $creator->id,
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
                $company = $this->findOrCreateCompany($lead->workspace, auth()->user(), $data);
                $data['company_id'] = $company?->id;
            }

            $lead->update(array_merge($data, [
                'normalized_email' => isset($data['email']) ? Str::lower(trim($data['email'])) : $lead->normalized_email,
            ]));

            app(\App\Services\LeadScoreService::class)->calculateScore($lead);

            return $lead->fresh();
        });
    }

    public function findOrCreateCompany(Workspace $workspace, User $creator, array $data): ?Company
    {
        $website = $data['website'] ?? null;
        $domain = $data['company_domain'] ?? ($website ? parse_url($website, PHP_URL_HOST) : null);
        $name = $data['company'] ?? $data['company_name'] ?? null;

        if (!$name && !$domain) {
            return null;
        }

        $query = Company::where('workspace_id', $workspace->id);

        if ($domain) {
            $query->where('domain', $domain);
        } elseif ($name) {
            $query->where('name', $name);
        }

        $company = $query->first();

        if (!$company) {
            $company = Company::create([
                'workspace_id' => $workspace->id,
                'created_by' => $creator->id,
                'name' => $name ?: $domain,
                'domain' => $domain,
                'website' => $data['website'] ?? null,
                'industry' => $data['industry'] ?? null,
                'size' => $data['company_size'] ?? null,
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'phone' => $data['company_phone'] ?? null,
            ]);
        }

        return $company;
    }

    public function bulkCreateLeads(array $leadsData, Workspace $workspace, User $creator, array $options = []): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'duplicates' => 0,
            'errors' => [],
        ];

        $skipDuplicates = $options['skip_duplicates'] ?? false;
        $updateExisting = $options['update_existing'] ?? false;

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

                $this->createLead($data, $workspace, $creator);
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
        $sortDirection = $sort['direction'] ?? 'desc';
        $allowedSorts = ['created_at', 'first_name', 'last_name', 'email', 'status', 'follow_up_at', 'last_activity_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function export(Workspace $workspace, array $filters = []): \Illuminate\Support\Collection
    {
        return $this->search($workspace, $filters, [], 10000)->getCollection();
    }
}