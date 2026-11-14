<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Http\Requests\Lead\BulkActionRequest;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\Company;
use App\Models\ImportMappingTemplate;
use App\Models\LeadImport;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Services\LeadService;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected ImportService $importService
    ) {}

    protected function ensureImportTables(): void
    {
        try {
            if (!Schema::hasTable('lead_imports')) {
                Schema::create('lead_imports', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                    $table->foreignId('lead_list_id')->nullable()->constrained('lead_lists')->nullOnDelete();
                    $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                    $table->string('original_filename');
                    $table->string('stored_path');
                    $table->string('status')->default('pending');
                    $table->integer('total_rows')->default(0);
                    $table->integer('processed_rows')->default(0);
                    $table->integer('successful_rows')->default(0);
                    $table->integer('failed_rows')->default(0);
                    $table->integer('duplicate_rows')->default(0);
                    $table->json('column_mapping')->nullable();
                    $table->json('errors')->nullable();
                    $table->timestamp('started_at')->nullable();
                    $table->timestamp('completed_at')->nullable();
                    $table->timestamps();
                    $table->softDeletes();
                });
            }
            if (!Schema::hasTable('import_mapping_templates')) {
                Schema::create('import_mapping_templates', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                    $table->string('name');
                    $table->json('mapping');
                    $table->timestamps();
                    $table->index(['workspace_id', 'name']);
                });
            }
        } catch (\Throwable $e) {
            Log::warning('LeadController ensureImportTables failed: ' . $e->getMessage());
        }
    }

    protected function getWorkspace(Request $request): ?Workspace
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $workspace = $user->currentWorkspace
            ?? $user->workspaces()->first()
            ?? Workspace::where('is_active', true)->first()
            ?? Workspace::first();

        if ($workspace) {
            session(['current_workspace_id' => $workspace->id]);
            try {
                $user->setCurrentWorkspace($workspace);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $workspace;
    }

    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $filters = $request->only(['search', 'status', 'pipeline_stage', 'country_code', 'owner_id', 'source', 'tags', 'needs_follow_up', 'company_id']);
        $sort = $request->only(['field', 'direction']);
        $perPage = (int) $request->input('per_page', 25);

        $leads = $this->leadService->search($workspace, $filters, $sort, $perPage);

        $owners = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'avatar_path']);

        $companies = Company::where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $leadLists = LeadList::where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $sources = Lead::where('workspace_id', $workspace->id)
            ->whereNotNull('source')
            ->distinct()
            ->pluck('source');

        $allTags = Lead::where('workspace_id', $workspace->id)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'filters' => $filters,
            'sort' => $sort,
            'owners' => $owners,
            'companies' => $companies,
            'leadLists' => $leadLists,
            'sources' => $sources,
            'allTags' => $allTags,
            'statuses' => ['new', 'contacted', 'qualified', 'unqualified', 'converted', 'archived'],
            'pipelineStages' => [
                'new_lead',
                'qualified',
                'email_ready',
                'email_sent',
                'open_recorded',
                'replied',
                'interested',
                'not_interested',
                'unsubscribed',
                'bounced',
                'archived',
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $owners = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        $companies = Company::where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Leads/Create', [
            'owners' => $owners,
            'companies' => $companies,
            'statuses' => ['new', 'contacted', 'qualified', 'unqualified', 'converted', 'archived'],
            'pipelineStages' => [
                'new_lead',
                'qualified',
                'email_ready',
                'email_sent',
                'open_recorded',
                'replied',
                'interested',
                'not_interested',
                'unsubscribed',
                'bounced',
                'archived',
            ],
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $lead = $this->leadService->createLead(
            $request->validated(),
            $request->user()->currentWorkspace,
            $request->user()
        );

        if ($request->has('lead_list_id')) {
            $list = LeadList::find($request->lead_list_id);
            if ($list && $list->workspace_id === $request->user()->currentWorkspace->id) {
                $this->leadService->addToList($lead, $list, $request->user());
            }
        }

        return redirect()->route('leads.show', $lead)
            ->with('success', 'Lead created successfully.');
    }

    public function show(Request $request, Lead $lead): Response
    {
        $this->authorize('view', $lead);

        $lead->load([
            'company',
            'owner',
            'creator',
            'lists',
            'assignedCampaign',
            'research',
            'internalNotes.user',
            'campaignRecipients.campaign',
            'campaignRecipients.currentStep',
            'tasks' => fn($q) => $q->with('assignee')->latest(),
            'messages' => fn($q) => $q->with('emailAccount')->latest()->limit(30),
            'conversations' => fn($q) => $q->with('messages')->latest(),
            'deals' => fn($q) => $q->with('stage.pipeline')->latest(),
        ]);

        $workspace = $request->user()->currentWorkspace;
        $campaigns = \App\Models\Campaign::where('workspace_id', $workspace->id)
            ->whereIn('status', ['active', 'draft', 'scheduled', 'paused'])
            ->get(['id', 'name']);

        $emailAccounts = \App\Models\EmailAccount::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'daily_send_limit', 'sent_today']);

        return Inertia::render('Leads/Show', [
            'lead' => $lead,
            'campaigns' => $campaigns,
            'emailAccounts' => $emailAccounts,
        ]);
    }

    public function edit(Request $request, Lead $lead): Response
    {
        $this->authorize('update', $lead);

        $workspace = $request->user()->currentWorkspace;

        $owners = User::whereHas('workspaces', fn($q) => $q->where('workspace_id', $workspace->id))
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        $companies = Company::where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Leads/Edit', [
            'lead' => $lead,
            'owners' => $owners,
            'companies' => $companies,
            'statuses' => ['new', 'contacted', 'qualified', 'unqualified', 'converted', 'archived'],
            'pipelineStages' => [
                'new_lead',
                'qualified',
                'email_ready',
                'email_sent',
                'open_recorded',
                'replied',
                'interested',
                'not_interested',
                'unsubscribed',
                'bounced',
                'archived',
            ],
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $this->leadService->updateLead($lead, $request->validated());

        return redirect()->route('leads.show', $lead)
            ->with('success', 'Lead updated successfully.');
    }

    public function destroy(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return redirect()->route('leads.index')
            ->with('success', 'Lead deleted successfully.');
    }

    public function bulkAction(BulkActionRequest $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $action = $request->action;
        $leadIds = $request->lead_ids;

        $query = Lead::where('workspace_id', $workspace->id)
            ->whereIn('id', $leadIds);

        // Archived leads are soft deleted, so they must be looked up
        // explicitly when the requested action is "restore".
        if ($action === 'restore') {
            $query->withTrashed();
        }

        $leads = $query->get();

        $count = 0;
        foreach ($leads as $lead) {
            $this->authorize('update', $lead);

            match ($action) {
                'archive' => $this->leadService->archiveLead($lead),
                'restore' => $this->leadService->restoreLead($lead),
                'delete' => $lead->delete(),
                'assign' => $lead->update(['owner_id' => $request->owner_id]),
                'add_to_list' => $this->leadService->addToList($lead, LeadList::find($request->lead_list_id), $request->user()),
                'remove_from_list' => $this->leadService->removeFromList($lead, LeadList::find($request->lead_list_id)),
                default => null,
            };
            $count++;
        }

        return back()->with('success', "Action completed on {$count} leads.");
    }

    public function importForm(Request $request): Response
    {
        $this->ensureImportTables();
        $workspace = $this->getWorkspace($request);

        $availableFields = [
            ['key' => 'email', 'label' => 'Email Address', 'required' => true, 'description' => 'Primary email for outreach & deduplication'],
            ['key' => 'first_name', 'label' => 'First Name', 'required' => false, 'description' => 'Given name of lead'],
            ['key' => 'last_name', 'label' => 'Last Name', 'required' => false, 'description' => 'Surname or family name'],
            ['key' => 'phone', 'label' => 'Phone Number', 'required' => false, 'description' => 'Direct or office phone'],
            ['key' => 'mobile', 'label' => 'Mobile Number', 'required' => false, 'description' => 'Cell or personal phone'],
            ['key' => 'job_title', 'label' => 'Job Title', 'required' => false, 'description' => 'Role or position in company'],
            ['key' => 'company', 'label' => 'Company Name', 'required' => false, 'description' => 'Account / organization name'],
            ['key' => 'company_domain', 'label' => 'Company Domain', 'required' => false, 'description' => 'Website domain e.g. acme.com'],
            ['key' => 'website', 'label' => 'Website URL', 'required' => false, 'description' => 'Full company or personal link'],
            ['key' => 'industry', 'label' => 'Industry', 'required' => false, 'description' => 'Sector or vertical'],
            ['key' => 'country', 'label' => 'Country', 'required' => false, 'description' => 'Country name or code'],
            ['key' => 'city', 'label' => 'City', 'required' => false, 'description' => 'City or municipality'],
            ['key' => 'state', 'label' => 'State / Region', 'required' => false, 'description' => 'State, province, or region'],
            ['key' => 'address', 'label' => 'Street Address', 'required' => false, 'description' => 'Physical or street address'],
            ['key' => 'linkedin_url', 'label' => 'LinkedIn Profile', 'required' => false, 'description' => 'Personal or company LinkedIn URL'],
            ['key' => 'source', 'label' => 'Lead Source', 'required' => false, 'description' => 'Acquisition origin or channel'],
            ['key' => 'tags', 'label' => 'Tags', 'required' => false, 'description' => 'Comma separated tag labels'],
            ['key' => 'notes', 'label' => 'Notes', 'required' => false, 'description' => 'Internal lead notes & comments'],
        ];

        if (! $workspace) {
            return Inertia::render('Leads/Import', [
                'leadLists' => [],
                'mappingTemplates' => [],
                'availableFields' => $availableFields,
            ]);
        }

        $leadLists = [];
        try {
            if (Schema::hasTable('lead_lists')) {
                $leadLists = LeadList::where('workspace_id', $workspace->id)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
        } catch (\Throwable $e) {
            Log::warning('LeadController@importForm: lead_lists query failed - ' . $e->getMessage());
        }

        $mappingTemplates = [];
        try {
            if (Schema::hasTable('import_mapping_templates')) {
                $mappingTemplates = ImportMappingTemplate::where('workspace_id', $workspace->id)
                    ->latest()
                    ->get(['id', 'name', 'mapping', 'created_at']);
            }
        } catch (\Throwable $e) {
            Log::warning('LeadController@importForm: import_mapping_templates query failed - ' . $e->getMessage());
        }

        return Inertia::render('Leads/Import', [
            'leadLists' => $leadLists,
            'mappingTemplates' => $mappingTemplates,
            'availableFields' => $availableFields,
        ]);
    }

    public function importPreview(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'file' => 'nullable|file|max:20480',
            'google_sheet_url' => ['nullable', 'string', 'max:500', 'regex:/https:\/\/docs\.google\.com\/spreadsheets\//'],
        ]);

        if (!$request->hasFile('file') && !$request->filled('google_sheet_url')) {
            return response()->json(['message' => 'Please provide a CSV/Excel file or a valid Google Sheets URL.'], 422);
        }

        // Ensure storage directories exist
        try {
            Storage::disk('local')->makeDirectory('imports');
            Storage::disk('local')->makeDirectory('imports/temp');
        } catch (\Throwable $e) {
            // ignore directory creation error
        }

        $fileType = 'csv';

        if ($request->filled('google_sheet_url')) {
            try {
                $sheetData = app(\App\Services\GoogleSheetsService::class)->fetchAndStoreCsv($request->input('google_sheet_url'));
                $path = $sheetData['stored_path'];
                $originalFilename = $sheetData['original_filename'];
                $fileType = 'google_sheets';
            } catch (\Throwable $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        } else {
            $uploaded = $request->file('file');
            if (!$uploaded || !$uploaded->isValid()) {
                return response()->json(['message' => 'Uploaded file is invalid or exceeded server upload limit.'], 422);
            }
            $originalFilename = $uploaded->getClientOriginalName();
            $ext = strtolower($uploaded->getClientOriginalExtension());

            if (in_array($ext, ['xlsx', 'xls'])) {
                $fileType = 'excel';
                $tempStored = $uploaded->store('imports/temp', 'local');
                $tempFullPath = Storage::disk('local')->path($tempStored);

                try {
                    $path = $this->importService->convertExcelToCsv($tempFullPath);
                    @unlink($tempFullPath);
                } catch (\Throwable $e) {
                    return response()->json(['message' => 'Could not read Excel file: ' . $e->getMessage()], 422);
                }
            } else {
                $path = $uploaded->store('imports', 'local');
            }
        }

        $fullPath = Storage::disk('local')->path($path);
        try {
            $columns = $this->importService->detectColumns($fullPath);
            $preview = $this->importService->previewImport($fullPath, 10);
            $suggestedResult = $this->importService->suggestMappingWithConfidence($columns);

            return response()->json([
                'columns' => $columns,
                'preview' => $preview,
                'suggested_mapping' => $suggestedResult['mapping'],
                'suggested_details' => $suggestedResult['details'],
                'stored_path' => $path,
                'original_filename' => $originalFilename,
                'file_type' => $fileType,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Import preview failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Could not process file: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function importLivePreview(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'stored_path' => 'required|string',
            'column_mapping' => 'required|array',
        ]);

        $fullPath = Storage::disk('local')->path($request->input('stored_path'));
        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'Uploaded file session expired or not found. Please re-upload.'], 404);
        }

        $this->ensureImportTables();
        $workspace = $this->getWorkspace($request);
        if (!$workspace) {
            return response()->json(['message' => 'No active workspace found.'], 422);
        }

        $mapping = $request->input('column_mapping');

        try {
            $result = $this->importService->generateLivePreview($fullPath, $mapping, $workspace, 20);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to generate live preview: ' . $e->getMessage()], 422);
        }
    }

    public function storeTemplate(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'mapping' => 'required|array',
        ]);

        $this->ensureImportTables();
        $workspace = $this->getWorkspace($request);
        if (!$workspace) {
            return response()->json(['message' => 'No active workspace found.'], 422);
        }

        if (!Schema::hasTable('import_mapping_templates')) {
            return response()->json(['message' => 'Mapping templates feature is not configured yet.'], 422);
        }

        try {
            $template = ImportMappingTemplate::updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'name' => trim($request->input('name')),
                ],
                [
                    'user_id' => $request->user()?->id,
                    'mapping' => $request->input('mapping'),
                ]
            );

            return response()->json([
                'template' => $template,
                'message' => 'Mapping template saved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not save template: ' . $e->getMessage()], 422);
        }
    }

    public function destroyTemplate(Request $request, ImportMappingTemplate $template): \Illuminate\Http\JsonResponse
    {
        $this->ensureImportTables();
        $workspace = $this->getWorkspace($request);
        if (!$workspace || $template->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $template->delete();

        return response()->json([
            'message' => 'Template removed successfully.',
        ]);
    }

    public function importProcess(Request $request): RedirectResponse
    {
        $request->validate([
            'stored_path' => 'required|string',
            'lead_list_id' => 'nullable',
            'column_mapping' => 'required|array',
            'original_filename' => 'required|string',
            'duplicate_handling' => 'nullable|string|in:skip,update',
        ]);

        $this->ensureImportTables();
        $workspace = $this->getWorkspace($request);
        if (!$workspace) {
            return back()->with('error', 'No active workspace found.');
        }

        $mapping = $request->input('column_mapping');
        $mapping['_options'] = [
            'duplicate_handling' => $request->input('duplicate_handling', 'skip'),
        ];

        $import = LeadImport::create([
            'workspace_id' => $workspace->id,
            'lead_list_id' => (!empty($request->lead_list_id) && is_numeric($request->lead_list_id)) ? (int)$request->lead_list_id : null,
            'created_by' => $request->user()?->id ?? $workspace->users()->first()?->id ?? User::first()?->id ?? 1,
            'original_filename' => $request->original_filename,
            'stored_path' => $request->stored_path,
            'column_mapping' => $mapping,
            'status' => 'pending',
        ]);

        try {
            \App\Jobs\ProcessLeadImportJob::dispatchSync($import->id);
        } catch (\Throwable $e) {
            Log::warning('Synchronous import failed, queueing: ' . $e->getMessage());
            try {
                \App\Jobs\ProcessLeadImportJob::dispatch($import->id);
            } catch (\Throwable $qe) {
                Log::error('Dispatching import job failed: ' . $qe->getMessage());
            }
        }

        return redirect()->route('leads.imports.show', $import)
            ->with('success', 'Import completed successfully.');
    }

    public function importsIndex(Request $request): Response
    {
        $this->ensureImportTables();
        $workspace = $this->getWorkspace($request);
        if (!$workspace) {
            return Inertia::render('Leads/Imports', [
                'imports' => ['data' => []],
            ]);
        }

        $imports = ['data' => []];
        try {
            if (Schema::hasTable('lead_imports')) {
                $imports = LeadImport::where('workspace_id', $workspace->id)
                    ->with(['creator', 'leadList'])
                    ->latest()
                    ->paginate(15);
            }
        } catch (\Throwable $e) {
            Log::warning('LeadController@importsIndex failed: ' . $e->getMessage());
            $imports = ['data' => []];
        }

        return Inertia::render('Leads/Imports', [
            'imports' => $imports,
        ]);
    }

    public function importShow(Request $request, LeadImport $import): Response
    {
        $this->ensureImportTables();
        $workspace = $this->getWorkspace($request);
        if ($workspace && $import->workspace_id !== $workspace->id) {
            abort(403);
        }

        try {
            $import->load(['leadList', 'creator', 'errors']);
        } catch (\Throwable $e) {
            Log::warning('LeadController@importShow load failed: ' . $e->getMessage());
        }

        return Inertia::render('Leads/ImportShow', [
            'import' => $import,
        ]);
    }

    public function export(Request $request)
    {
        $workspace = $request->user()->currentWorkspace;
        $filters = $request->only(['search', 'status', 'owner_id', 'source', 'tags', 'company_id']);

        $scope = $request->input('scope', 'filtered');
        $format = strtolower($request->input('format', 'csv'));
        $columns = $request->input('columns', []);
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        }
        $columns = array_filter(array_map('trim', (array)$columns));

        if ($scope === 'all') {
            $leads = Lead::where('workspace_id', $workspace->id)
                ->with(['company:id,name', 'owner:id,name'])
                ->latest()
                ->get();
        } elseif ($scope === 'selected' && $request->filled('selected_ids')) {
            $ids = is_array($request->selected_ids) ? $request->selected_ids : explode(',', $request->selected_ids);
            $leads = Lead::where('workspace_id', $workspace->id)
                ->whereIn('id', $ids)
                ->with(['company:id,name', 'owner:id,name'])
                ->latest()
                ->get();
        } else {
            $leads = $this->leadService->export($workspace, $filters);
        }

        $isExcel = in_array($format, ['xlsx', 'excel']);
        $extension = $isExcel ? 'xlsx' : 'csv';
        $writerType = $isExcel ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV;
        $filename = 'leads-export-' . now()->format('Y-m-d_His') . '.' . $extension;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LeadsExport($leads, $columns),
            $filename,
            $writerType
        );
    }

    public function validateEmail(Request $request, Lead $lead): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $lead);

        $email = $lead->email;
        $isValidSyntax = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $domain = substr(strrchr($email, "@"), 1);
        $hasMx = false;

        if ($isValidSyntax && !empty($domain)) {
            $hasMx = checkdnsrr($domain, "MX");
        }

        $isValid = $isValidSyntax && $hasMx;
        $status = $isValid ? 'valid' : ($isValidSyntax ? 'no_mx_record' : 'invalid_syntax');

        $lead->update([
            'email_verified' => $isValid,
            'email_verification_status' => $status,
        ]);

        app(\App\Services\LeadScoreService::class)->calculateScore($lead);

        return response()->json([
            'success' => true,
            'is_valid' => $isValid,
            'status' => $status,
            'score' => $lead->fresh()->score,
        ]);
    }

    public function removeDuplicates(Request $request): \Illuminate\Http\RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        $duplicates = Lead::where('workspace_id', $workspace->id)
            ->select('normalized_email')
            ->groupBy('normalized_email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_email');

        $removedCount = 0;
        foreach ($duplicates as $email) {
            $leads = Lead::where('workspace_id', $workspace->id)
                ->where('normalized_email', $email)
                ->orderByDesc('score')
                ->orderByDesc('created_at')
                ->get();

            // Keep first, delete the rest
            $toDelete = $leads->slice(1);
            foreach ($toDelete as $dup) {
                $dup->delete();
                $removedCount++;
            }
        }

        return back()->with('success', "Removed {$removedCount} duplicate lead(s).");
    }

    public function assignCampaign(Request $request, Lead $lead): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $lead);

        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
        ]);

        $campaign = \App\Models\Campaign::findOrFail($request->campaign_id);

        $lead->update(['assigned_campaign_id' => $campaign->id]);

        // Add to campaign recipients if not already enrolled
        app(\App\Services\CampaignService::class)->addRecipients($campaign, [$lead->id]);

        return back()->with('success', "Lead assigned to campaign '{$campaign->name}' successfully.");
    }

    public function addNote(Request $request, Lead $lead): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $lead);

        $request->validate([
            'content' => 'required|string',
            'remind_at' => 'nullable|date',
        ]);

        $lead->internalNotes()->create([
            'workspace_id' => $request->user()->currentWorkspace->id,
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'remind_at' => $request->remind_at,
        ]);

        return back()->with('success', 'Note added successfully.');
    }

    public function recalculateScore(Request $request, Lead $lead): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $lead);

        $score = app(\App\Services\LeadScoreService::class)->calculateScore($lead);

        return response()->json([
            'success' => true,
            'score' => $score,
            'category' => $lead->fresh()->category,
        ]);
    }

    public function updatePipelineStage(Request $request, Lead $lead): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $lead);

        $request->validate([
            'pipeline_stage' => 'required|string',
        ]);

        $stage = $request->pipeline_stage;
        $updates = ['pipeline_stage' => $stage];

        if ($stage === 'won') {
            $updates['status'] = 'converted';
            $updates['converted_at'] = now();
        } elseif ($stage === 'lost') {
            $updates['status'] = 'unqualified';
        }

        $lead->update($updates);
        app(\App\Services\LeadScoreService::class)->calculateScore($lead);

        return response()->json([
            'success' => true,
            'stage' => $stage,
            'score' => $lead->fresh()->score,
            'category' => $lead->fresh()->category,
        ]);
    }

    public function sendEmail(Request $request, Lead $lead, \App\Services\EmailService $emailService): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('view', $lead);

        $workspace = $request->user()->currentWorkspace;
        abort_if($lead->workspace_id !== $workspace->id, 403);

        $validated = $request->validate([
            'subject'          => 'required|string|max:255',
            'body'             => 'required|string',
            'email_account_id' => 'nullable|exists:email_accounts,id',
        ]);

        if (empty($lead->email)) {
            return back()->withErrors(['error' => 'This lead does not have a valid email address.']);
        }

        // Select email account
        $account = null;
        if (!empty($validated['email_account_id'])) {
            $account = \App\Models\EmailAccount::where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->find($validated['email_account_id']);
        }

        if (!$account) {
            $accounts = \App\Models\EmailAccount::where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->get();

            $account = $accounts->first(fn($a) => $a->canSendToday()) ?? $accounts->first();
        }

        if (!$account) {
            return back()->withErrors(['error' => 'No active email mailbox found. Please connect an email account first.']);
        }

        if (!$account->canSendToday()) {
            return back()->withErrors(['error' => "Mailbox {$account->email} has reached its daily limit of {$account->daily_send_limit} emails."]);
        }

        $htmlBody = nl2br(e($validated['body']));
        $toName = trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')) ?: ($lead->company_name ?: $lead->email);

        try {
            $result = $emailService->send(
                account: $account,
                to: [['email' => $lead->email, 'name' => $toName]],
                subject: $validated['subject'],
                html: $htmlBody,
                text: $validated['body'],
                options: [
                    'reply_to' => $account->reply_to ? ['email' => $account->reply_to] : null,
                ]
            );

            if (!$result['success']) {
                return back()->withErrors(['error' => 'SMTP failed: ' . ($result['error'] ?? 'Unknown error')]);
            }

            // Create message record
            \App\Models\Message::create([
                'workspace_id'        => $workspace->id,
                'email_account_id'    => $account->id,
                'lead_id'             => $lead->id,
                'message_id'          => $result['message_id'] ?? null,
                'subject'             => $validated['subject'],
                'body_html'           => $htmlBody,
                'body_text'           => $validated['body'],
                'from_email'          => $account->email,
                'from_name'           => $account->from_name ?? $account->name,
                'to_emails'           => [['email' => $lead->email, 'name' => $toName]],
                'status'              => \App\Models\Message::STATUS_SENT,
                'sent_at'             => now(),
            ]);

            // Increment sent counter on mailbox
            $account->incrementSent();

            // Progress pipeline stage if new/qualified
            if (in_array($lead->pipeline_stage, ['new_lead', 'qualified', 'email_ready', null])) {
                $lead->update([
                    'pipeline_stage'   => 'email_sent',
                    'last_activity_at' => now(),
                ]);
            }

            return back()->with('success', "Direct message sent successfully to {$lead->email} via {$account->email}!");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed to send message: ' . $e->getMessage()]);
        }
    }
}
