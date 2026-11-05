<?php

namespace App\Http\Controllers\Companies;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CompanyEnrichmentService;
use Illuminate\Http\Request;

class CompanyEnrichmentController extends Controller
{
    public function __construct(
        protected CompanyEnrichmentService $enrichmentService
    ) {}

    /**
     * Enrich a single company.
     */
    public function enrichOne(Request $request, Company $company)
    {
        $this->authorize('update', $company);

        $workspace = $request->user()->currentWorkspace;

        if ($company->workspace_id !== $workspace->id) {
            abort(403, 'Unauthorized');
        }

        $success = $this->enrichmentService->enrichFromDomain($company, $workspace);

        return back()->with(
            $success ? 'success' : 'info',
            $success ? 'Company enriched successfully.' : 'No new information found or company already verified.'
        );
    }

    /**
     * Enrich all unenriched companies (up to 10 at a time to avoid timeouts).
     */
    public function enrichAll(Request $request)
    {
        $workspace = $request->user()->currentWorkspace;

        $stats = $this->enrichmentService->enrichBatch($workspace, 10);

        return back()->with('success', "Enrichment complete: {$stats['enriched']} enriched, {$stats['failed']} failed.");
    }

    /**
     * Flag a company for manual review.
     */
    public function flagForReview(Request $request, Company $company)
    {
        $this->authorize('update', $company);

        $company->update([
            'enrichment_status' => 'flagged_for_review',
        ]);

        return back()->with('success', 'Company flagged for review.');
    }

    /**
     * Mark a company as verified (locks data from AI overwrites).
     */
    public function markVerified(Request $request, Company $company)
    {
        $this->authorize('update', $company);

        $company->update([
            'enrichment_status'  => 'verified',
            'verification_status'=> 'verified',
        ]);

        return back()->with('success', 'Company marked as verified.');
    }
}
