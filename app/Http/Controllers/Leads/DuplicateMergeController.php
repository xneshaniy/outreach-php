<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\DuplicateDetectionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DuplicateMergeController extends Controller
{
    public function __construct(
        protected DuplicateDetectionService $duplicateService
    ) {}

    /**
     * Show the Duplicate Detection & Safe Merge Review Center.
     */
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;
        if (!$workspace) {
            return Inertia::render('Leads/Duplicates', [
                'duplicateGroups' => [],
                'totalGroups'     => 0,
            ]);
        }

        $duplicates = [];
        try {
            $duplicates = $this->duplicateService->findDuplicates($workspace);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DuplicateMergeController: ' . $e->getMessage());
        }

        return Inertia::render('Leads/Duplicates', [
            'duplicateGroups' => $duplicates,
            'totalGroups'     => count($duplicates),
        ]);
    }

    /**
     * Execute a safe merge of two duplicate leads.
     */
    public function merge(Request $request)
    {
        $request->validate([
            'primary_id'       => 'required|exists:leads,id',
            'secondary_id'     => 'required|exists:leads,id|different:primary_id',
            'field_selections' => 'nullable|array',
        ]);

        $workspace = $request->user()->currentWorkspace;

        $primary = Lead::where('workspace_id', $workspace->id)->findOrFail($request->primary_id);
        $secondary = Lead::where('workspace_id', $workspace->id)->findOrFail($request->secondary_id);

        $merged = $this->duplicateService->mergeLeads(
            $primary,
            $secondary,
            $request->input('field_selections', []),
            $request->user()
        );

        return back()->with('success', "Leads successfully merged into {$merged->display_name}.");
    }
}
