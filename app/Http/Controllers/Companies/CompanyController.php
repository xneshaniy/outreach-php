<?php

namespace App\Http\Controllers\Companies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $query = Company::where('workspace_id', $workspace->id)
            ->with(['creator', 'leads' => fn($q) => $q->select('id', 'company_id')])
            ->withCount(['leads', 'deals']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('domain', 'like', "%{$request->search}%")
                    ->orWhere('industry', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('industry')) {
            $query->where('industry', $request->industry);
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        $sortField = $request->get('sort_field', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $allowedSorts = ['name', 'domain', 'industry', 'country', 'created_at', 'leads_count', 'deals_count'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $companies = $query->paginate(25)->withQueryString();

        $industries = Company::where('workspace_id', $workspace->id)
            ->whereNotNull('industry')
            ->distinct()
            ->pluck('industry')
            ->sort();

        $countries = Company::where('workspace_id', $workspace->id)
            ->whereNotNull('country')
            ->distinct()
            ->pluck('country')
            ->sort();

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
            'filters' => $request->only(['search', 'industry', 'country']),
            'industries' => $industries,
            'countries' => $countries,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Companies/Create');
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = Company::create(array_merge($request->validated(), [
            'workspace_id' => $request->user()->currentWorkspace->id,
            'created_by' => $request->user()->id,
        ]));

        return redirect()->route('companies.show', $company)
            ->with('success', 'Company created successfully.');
    }

    public function show(Request $request, Company $company): Response
    {
        $this->authorize('view', $company);

        $company->load([
            'creator',
            'leads' => fn($q) => $q->with('owner')->latest()->limit(10),
            'deals' => fn($q) => $q->with('stage.pipeline')->latest()->limit(10),
            'tasks' => fn($q) => $q->with('assignee')->latest()->limit(10),
        ]);

        return Inertia::render('Companies/Show', [
            'company' => $company,
        ]);
    }

    public function edit(Request $request, Company $company): Response
    {
        $this->authorize('update', $company);

        return Inertia::render('Companies/Edit', [
            'company' => $company,
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $company->update($request->validated());

        return redirect()->route('companies.show', $company)
            ->with('success', 'Company updated successfully.');
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Company deleted successfully.');
    }
}