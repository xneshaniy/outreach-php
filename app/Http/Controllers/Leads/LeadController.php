<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Http\Requests\Lead\BulkActionRequest;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\Company;
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

    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $filters = $request->only(['search', 'status', 'owner_id', 'source', 'tags', 'needs_follow_up', 'company_id']);
        $sort = $request->only(['field', 'direction']);
        $perPage = $request->get('per_page', 25);

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
            'campaignRecipients.campaign',
            'campaignRecipients.currentStep',
            'tasks' => fn($q) => $q->with('assignee')->latest(),
            'messages' => fn($q) => $q->with('emailAccount')->latest()->limit(20),
            'conversations' => fn($q) => $q->with('messages')->latest(),
            'deals' => fn($q) => $q->with('stage.pipeline')->latest(),
        ]);

        return Inertia::render('Leads/Show', [
            'lead' => $lead,
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

        $leads = Lead::where('workspace_id', $workspace->id)
            ->whereIn('id', $leadIds)
            ->get();

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
        $workspace = $request->user()->currentWorkspace;

        $leadLists = LeadList::where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Leads/Import', [
            'leadLists' => $leadLists,
        ]);
    }

    public function importPreview(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $path = $request->file('file')->store('imports', 'local');
        $columns = $this->importService->detectColumns(storage_path('app/' . $path));
        $preview = $this->importService->previewImport(storage_path('app/' . $path));
        $suggestedMapping = $this->importService->suggestMapping($columns);

        return response()->json([
            'columns' => $columns,
            'preview' => $preview,
            'suggested_mapping' => $suggestedMapping,
            'stored_path' => $path,
        ]);
    }

    public function importProcess(Request $request): RedirectResponse
    {
        $request->validate([
            'stored_path' => 'required|string',
            'lead_list_id' => 'nullable|exists:lead_lists,id',
            'column_mapping' => 'required|array',
            'original_filename' => 'required|string',
        ]);

        $workspace = $request->user()->currentWorkspace;

        $import = \App\Models\LeadImport::create([
            'workspace_id' => $workspace->id,
            'lead_list_id' => $request->lead_list_id,
            'created_by' => $request->user()->id,
            'original_filename' => $request->original_filename,
            'stored_path' => $request->stored_path,
            'column_mapping' => $request->column_mapping,
            'status' => 'pending',
        ]);

        \App\Jobs\ProcessLeadImportJob::dispatch($import->id);

        return redirect()->route('leads.imports.show', $import)
            ->with('success', 'Import started. You will be notified when complete.');
    }

    public function importsIndex(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $imports = \App\Models\LeadImport::where('workspace_id', $workspace->id)
            ->with(['leadList', 'creator'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Leads/Imports', [
            'imports' => $imports,
        ]);
    }

    public function importShow(Request $request, \App\Models\LeadImport $import): Response
    {
        $this->authorize('view', $import);

        $import->load(['leadList', 'creator', 'errors']);

        return Inertia::render('Leads/ImportShow', [
            'import' => $import,
        ]);
    }

    public function export(Request $request)
    {
        $workspace = $request->user()->currentWorkspace;
        $filters = $request->only(['search', 'status', 'owner_id', 'source', 'tags', 'company_id']);

        $leads = $this->leadService->export($workspace, $filters);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LeadsExport($leads),
            'leads-' . now()->format('Y-m-d') . '.csv'
        );
    }
}