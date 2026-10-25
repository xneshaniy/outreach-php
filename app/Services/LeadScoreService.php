<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadScoringRule;
use App\Models\Workspace;

class LeadScoreService
{
    /**
     * Default scoring rules seeded on-demand for a workspace.
     */
    public const DEFAULT_RULES = [
        ['name' => 'Valid & Verified Email', 'criteria_key' => 'valid_email', 'points' => 15],
        ['name' => 'Company Website Available', 'criteria_key' => 'website_available', 'points' => 10],
        ['name' => 'Relevant Industry Specified', 'criteria_key' => 'relevant_industry', 'points' => 15],
        ['name' => 'Decision Maker Job Title', 'criteria_key' => 'relevant_job_title', 'points' => 20],
        ['name' => 'LinkedIn Profile Included', 'criteria_key' => 'linkedin_available', 'points' => 10],
        ['name' => 'Phone / Mobile Provided', 'criteria_key' => 'phone_available', 'points' => 10],
        ['name' => 'Positive Reply Received', 'criteria_key' => 'positive_reply', 'points' => 30],
        ['name' => 'Meeting Requested or Booked', 'criteria_key' => 'meeting_requested', 'points' => 40],
    ];

    /**
     * Get or initialize workspace scoring rules.
     */
    public function getRules(Workspace $workspace)
    {
        $rules = LeadScoringRule::where('workspace_id', $workspace->id)->get();
        if ($rules->isEmpty()) {
            foreach (self::DEFAULT_RULES as $r) {
                LeadScoringRule::create([
                    'workspace_id' => $workspace->id,
                    'name' => $r['name'],
                    'criteria_key' => $r['criteria_key'],
                    'points' => $r['points'],
                    'is_active' => true,
                ]);
            }
            $rules = LeadScoringRule::where('workspace_id', $workspace->id)->get();
        }

        return $rules;
    }

    /**
     * Calculate and persist the lead's score and category.
     */
    public function calculateScore(Lead $lead): int
    {
        $workspace = $lead->workspace;
        if (!$workspace) {
            return $lead->score ?? 0;
        }

        $rules = $this->getRules($workspace)->where('is_active', true);
        $score = 0;

        foreach ($rules as $rule) {
            $matched = false;

            switch ($rule->criteria_key) {
                case 'valid_email':
                    $matched = filter_var($lead->email, FILTER_VALIDATE_EMAIL) !== false;
                    break;

                case 'website_available':
                    $matched = !empty($lead->website) || (!empty($lead->company) && is_object($lead->company) && !empty($lead->company->website));
                    break;

                case 'relevant_industry':
                    $matched = !empty($lead->industry);
                    break;

                case 'relevant_job_title':
                    if (!empty($lead->job_title)) {
                        $title = strtolower($lead->job_title);
                        $matched = preg_match('/(ceo|cto|cmo|founder|owner|director|head|vp|president|manager|partner|chief|lead)/i', $title) === 1;
                    }
                    break;

                case 'linkedin_available':
                    $matched = !empty($lead->linkedin_url);
                    break;

                case 'phone_available':
                    $matched = !empty($lead->phone) || !empty($lead->mobile);
                    break;

                case 'positive_reply':
                    $matched = $lead->messages()
                        ->whereIn('reply_classification', ['interested', 'positive'])
                        ->exists();
                    break;

                case 'meeting_requested':
                    $matched = $lead->messages()
                        ->where('reply_classification', 'meeting_requested')
                        ->exists() || $lead->pipeline_stage === 'meeting_scheduled';
                    break;
            }

            if ($matched) {
                $score += $rule->points;
            }
        }

        // Cap at 100
        $score = min(100, max(0, $score));

        // Determine category
        $category = 'cold';
        if ($lead->status === 'unqualified') {
            $category = 'unqualified';
        } elseif ($lead->status === 'converted' || $lead->pipeline_stage === 'won') {
            $category = 'converted';
        } elseif ($score >= 70) {
            $category = 'hot';
        } elseif ($score >= 50) {
            $category = 'interested';
        } elseif ($score >= 30) {
            $category = 'qualified';
        } else {
            $category = 'cold';
        }

        $lead->update([
            'score' => $score,
            'category' => $category,
        ]);

        return $score;
    }
}
