<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\SuppressionList;
use App\Services\GDPRComplianceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceController extends Controller
{
    public function __construct(
        protected GDPRComplianceService $complianceService
    ) {}

    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $suppressions = SuppressionList::where('workspace_id', $workspace->id)
            ->latest()
            ->paginate(15);

        return Inertia::render('Settings/Compliance', [
            'suppressions'     => $suppressions,
            'regionalFooters'  => GDPRComplianceService::REGIONAL_FOOTERS,
            'suppressionCount' => SuppressionList::where('workspace_id', $workspace->id)->count(),
        ]);
    }

    public function exportDossier(Request $request, Lead $lead)
    {
        $workspace = $request->user()->currentWorkspace;
        abort_if($lead->workspace_id !== $workspace->id, 403);

        $dossier = $this->complianceService->exportLeadDossier($lead);

        $filename = "lead_data_export_{$lead->id}_" . date('Ymd_His') . ".json";

        return response()->streamDownload(function () use ($dossier) {
            echo json_encode($dossier, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function eraseLead(Request $request, Lead $lead)
    {
        $workspace = $request->user()->currentWorkspace;
        abort_if($lead->workspace_id !== $workspace->id, 403);

        $this->complianceService->executeErasure($lead, $request->user());

        return redirect()->route('leads.index')->with('success', 'Lead permanently erased under GDPR Article 17 and added to suppression list.');
    }

    public function addSuppression(Request $request)
    {
        $request->validate([
            'email'  => 'required|email',
            'reason' => 'required|in:unsubscribe,bounce,complaint,manual',
        ]);

        $workspace = $request->user()->currentWorkspace;

        SuppressionList::suppress(
            $workspace->id,
            $request->email,
            $request->reason,
            'Manually added via Compliance Center'
        );

        return back()->with('success', "Email {$request->email} added to suppression list.");
    }
}
