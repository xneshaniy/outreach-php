<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\GoogleImportLog;
use App\Models\GoogleIntegration;
use App\Models\GoogleSheetSync;
use App\Services\GoogleOAuthService;
use App\Services\GoogleSheetsApiService;
use App\Services\UnifiedMatchingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoogleIntegrationController extends Controller
{
    public function __construct(
        protected GoogleOAuthService     $oauthService,
        protected GoogleSheetsApiService $sheetsService,
        protected UnifiedMatchingService $matchingService
    ) {}

    /**
     * Show Google integration status page.
     */
    public function index(Request $request)
    {
        $workspace   = $request->user()->currentWorkspace;
        $integration = GoogleIntegration::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->latest()
            ->first();

        $syncs = GoogleSheetSync::where('workspace_id', $workspace->id)
            ->latest()
            ->limit(10)
            ->get();

        $logs = GoogleImportLog::where('workspace_id', $workspace->id)
            ->where('source_type', 'like', 'google%')
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('Integrations/GoogleSheets', [
            'configured'  => $this->oauthService->isConfigured(),
            'integration' => $integration,
            'syncs'       => $syncs,
            'importLogs'  => $logs,
        ]);
    }

    /**
     * Redirect to Google OAuth consent screen.
     */
    public function connect(Request $request)
    {
        if (!$this->oauthService->isConfigured()) {
            return back()->withErrors(['google' => 'Google OAuth is not configured. Contact the administrator.']);
        }

        $includeGmail = $request->boolean('gmail', false);
        $url = $this->oauthService->getAuthUrl($includeGmail);

        return redirect($url);
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('settings.integrations')
                ->withErrors(['google' => 'Google authorisation was denied: ' . $request->get('error')]);
        }

        try {
            $tokenData = $this->oauthService->exchangeCode($request->get('code'));
            $userInfo  = $this->oauthService->getUserInfo($tokenData['access_token']);

            $workspace   = $request->user()->currentWorkspace;
            $integration = $this->oauthService->storeIntegration($workspace, $request->user(), $tokenData, $userInfo);

            return redirect()->route('google.sheets.index')
                ->with('success', "Connected Google account: {$userInfo['email']}");
        } catch (\Exception $e) {
            return redirect()->route('settings.integrations')
                ->withErrors(['google' => 'Google connection failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Revoke and disconnect the Google integration.
     */
    public function disconnect(Request $request)
    {
        $workspace   = $request->user()->currentWorkspace;
        $integration = GoogleIntegration::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->first();

        if ($integration) {
            $this->oauthService->revokeToken($integration);
        }

        return redirect()->route('settings.integrations')
            ->with('success', 'Google account disconnected.');
    }

    /**
     * List Google Spreadsheets.
     */
    public function listSheets(Request $request)
    {
        $workspace   = $request->user()->currentWorkspace;
        $integration = $this->getActiveIntegration($workspace);

        $sheets = $this->sheetsService->listSpreadsheets($integration);

        return response()->json(['sheets' => $sheets]);
    }

    /**
     * List worksheets (tabs) in a spreadsheet.
     */
    public function listWorksheets(Request $request, string $spreadsheetId)
    {
        $workspace   = $request->user()->currentWorkspace;
        $integration = $this->getActiveIntegration($workspace);

        $worksheets = $this->sheetsService->listWorksheets($integration, $spreadsheetId);

        return response()->json(['worksheets' => $worksheets]);
    }

    /**
     * Preview rows from a sheet using provided column mapping.
     */
    public function previewSheet(Request $request)
    {
        $request->validate([
            'spreadsheet_id' => 'required|string',
            'worksheet_name' => 'required|string',
            'column_mapping' => 'required|array',
        ]);

        $workspace   = $request->user()->currentWorkspace;
        $integration = $this->getActiveIntegration($workspace);

        $preview = $this->sheetsService->previewSheet(
            $integration,
            $request->spreadsheet_id,
            $request->worksheet_name,
            $request->column_mapping,
            5
        );

        return response()->json($preview);
    }

    /**
     * Import records from a Google Sheet into the CRM.
     */
    public function importSheet(Request $request)
    {
        $request->validate([
            'spreadsheet_id'   => 'required|string',
            'spreadsheet_name' => 'nullable|string',
            'worksheet_name'   => 'required|string',
            'column_mapping'   => 'required|array',
        ]);

        $workspace   = $request->user()->currentWorkspace;
        $integration = $this->getActiveIntegration($workspace);
        $user        = $request->user();

        $range = "'{$request->worksheet_name}'";
        $data  = $this->sheetsService->readSheet($integration, $request->spreadsheet_id, $range);
        $rows  = $this->sheetsService->mapRows($data['rows'], $request->column_mapping);

        $stats = $this->matchingService->processNewData($workspace, $rows, 'google_sheets', $user);

        // Save or update sync record
        GoogleSheetSync::updateOrCreate(
            [
                'workspace_id'   => $workspace->id,
                'spreadsheet_id' => $request->spreadsheet_id,
                'worksheet_name' => $request->worksheet_name,
            ],
            [
                'google_integration_id' => $integration->id,
                'spreadsheet_name'      => $request->spreadsheet_name,
                'column_mapping'        => $request->column_mapping,
                'last_synced_at'        => now(),
                'last_status'           => empty($stats['errors']) ? 'success' : 'partial',
                'records_imported'      => $stats['imported'],
                'records_updated'       => $stats['updated'],
            ]
        );

        // Log import
        GoogleImportLog::create([
            'workspace_id'           => $workspace->id,
            'user_id'                => $user->id,
            'source_type'            => 'google_sheets',
            'source_identifier'      => $request->spreadsheet_id . '/' . $request->worksheet_name,
            'status'                 => empty($stats['errors']) ? 'success' : 'partial',
            'imported_count'         => $stats['imported'],
            'updated_count'          => $stats['updated'],
            'matched_companies_count'=> $stats['matched_companies'],
            'created_companies_count'=> $stats['created_companies'],
            'error_details'          => $stats['errors'] ?: null,
        ]);

        $integration->update(['last_synced_at' => now()]);

        return back()->with('success',
            "Import complete: {$stats['imported']} new leads, {$stats['updated']} updated, " .
            "{$stats['matched_companies']} companies matched, {$stats['created_companies']} companies created."
        );
    }

    /**
     * Show import & sync history.
     */
    public function syncHistory(Request $request)
    {
        $workspace = $request->user()->currentWorkspace;
        $logs = GoogleImportLog::where('workspace_id', $workspace->id)
            ->latest()
            ->paginate(20);

        return response()->json(['logs' => $logs]);
    }

    /**
     * Toggle scheduled sync for a sheet sync config.
     */
    public function toggleScheduledSync(Request $request, GoogleSheetSync $sync)
    {
        $request->validate(['sync_mode' => 'required|in:manual,hourly,daily,weekly']);

        $sync->update(['sync_mode' => $request->sync_mode]);

        return back()->with('success', 'Sync schedule updated.');
    }

    // -------------------------------------------------------------------------

    private function getActiveIntegration($workspace): GoogleIntegration
    {
        $integration = GoogleIntegration::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->latest()
            ->first();

        if (!$integration) {
            abort(422, 'No active Google integration found. Please connect your Google account first.');
        }

        return $integration;
    }
}
