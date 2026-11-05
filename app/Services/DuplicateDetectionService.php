<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateDetectionService
{
    /**
     * Find potential duplicate lead groups in a workspace.
     */
    public function findDuplicates(Workspace $workspace): array
    {
        $duplicateGroups = [];

        // 1. Exact Email match (multiple records with same normalized_email)
        $emailDuplicates = Lead::where('workspace_id', $workspace->id)
            ->select('normalized_email', DB::raw('COUNT(*) as count'))
            ->whereNotNull('normalized_email')
            ->where('normalized_email', '!=', '')
            ->groupBy('normalized_email')
            ->having('count', '>', 1)
            ->pluck('normalized_email');

        foreach ($emailDuplicates as $email) {
            $leads = Lead::where('workspace_id', $workspace->id)
                ->where('normalized_email', $email)
                ->with(['company', 'owner'])
                ->get();

            $duplicateGroups[] = [
                'type' => 'email',
                'matched_on' => $email,
                'leads' => $leads,
            ];
        }

        // 2. Exact Phone match
        $phoneDuplicates = Lead::where('workspace_id', $workspace->id)
            ->select('phone', DB::raw('COUNT(*) as count'))
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->groupBy('phone')
            ->having('count', '>', 1)
            ->pluck('phone');

        foreach ($phoneDuplicates as $phone) {
            // Check if already captured in email duplicate group
            $leads = Lead::where('workspace_id', $workspace->id)
                ->where('phone', $phone)
                ->with(['company', 'owner'])
                ->get();

            $leadIds = $leads->pluck('id')->sort()->values()->all();
            $alreadyIncluded = false;
            foreach ($duplicateGroups as $group) {
                $groupLeadIds = $group['leads']->pluck('id')->sort()->values()->all();
                if ($groupLeadIds === $leadIds) {
                    $alreadyIncluded = true;
                    break;
                }
            }

            if (!$alreadyIncluded) {
                $duplicateGroups[] = [
                    'type' => 'phone',
                    'matched_on' => $phone,
                    'leads' => $leads,
                ];
            }
        }

        return $duplicateGroups;
    }

    /**
     * Safely merge a secondary lead into a primary lead using selected field overrides.
     */
    public function mergeLeads(Lead $primary, Lead $secondary, array $fieldSelections, $user): Lead
    {
        return DB::transaction(function () use ($primary, $secondary, $fieldSelections, $user) {
            $updateData = [];

            // Copy chosen fields from secondary
            $mergeableFields = [
                'first_name', 'last_name', 'email', 'phone', 'mobile',
                'job_title', 'department', 'company_name', 'company_id',
                'website', 'industry', 'city', 'country', 'linkedin_url',
                'notes', 'tags', 'custom_fields'
            ];

            foreach ($mergeableFields as $field) {
                if (isset($fieldSelections[$field]) && $fieldSelections[$field] === 'secondary') {
                    $updateData[$field] = $secondary->$field;
                }
            }

            // Always pick the highest score
            if (($secondary->score ?? 0) > ($primary->score ?? 0)) {
                $updateData['score'] = $secondary->score;
                $updateData['category'] = $secondary->category;
                $updateData['score_reasons'] = $secondary->score_reasons;
            }

            // Repoint relations from secondary to primary
            $secondary->messages()->update(['lead_id' => $primary->id]);
            $secondary->campaignRecipients()->update(['lead_id' => $primary->id]);
            $secondary->deals()->update(['lead_id' => $primary->id]);
            $secondary->tasks()->update(['related_id' => $primary->id]);
            $secondary->internalNotes()->update(['lead_id' => $primary->id]);

            // Apply updates to primary
            if (!empty($updateData)) {
                $primary->update($updateData);
            }

            // Soft-delete the merged duplicate
            $secondary->delete();

            // Record audit log
            AuditLog::create([
                'workspace_id' => $primary->workspace_id,
                'user_id'      => $user?->id,
                'action'       => 'lead_merge',
                'auditable_type' => Lead::class,
                'auditable_id'   => $primary->id,
                'old_values'   => ['merged_lead_id' => $secondary->id],
                'new_values'   => $updateData,
            ]);

            return $primary->fresh();
        });
    }
}
