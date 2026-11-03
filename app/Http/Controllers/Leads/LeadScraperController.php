<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadList;
use App\Services\CampaignService;
use App\Services\LeadService;
use App\Services\WebScraperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadScraperController extends Controller
{
    public function __construct(
        protected WebScraperService $scraperService,
        protected LeadService $leadService,
        protected CampaignService $campaignService
    ) {}

    /**
     * Show the Lead Web Scraper UI.
     */
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $campaigns = Campaign::where('workspace_id', $workspace->id)
            ->whereIn('status', ['active', 'draft', 'scheduled', 'paused'])
            ->orderBy('name')
            ->get(['id', 'name', 'status']);

        $leadLists = LeadList::where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Leads/Scraper', [
            'campaigns' => $campaigns,
            'leadLists' => $leadLists,
        ]);
    }

    /**
     * Preview scrape on a single website domain/URL.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|string|max:500',
        ]);

        $workspace = $request->user()->currentWorkspace;
        $result = $this->scraperService->scrapeWebsite($workspace, $request->input('url'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to scrape website.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $result['data'],
        ]);
    }

    /**
     * Save the scraped lead into CRM, auto-create company, assign to list and campaign.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email'          => 'required|email|max:255',
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'nullable|string|max:100',
            'company_name'   => 'nullable|string|max:255',
            'website'        => 'nullable|string|max:255',
            'domain'         => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:50',
            'job_title'      => 'nullable|string|max:150',
            'country'        => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:100',
            'industry'       => 'nullable|string|max:100',
            'linkedin_url'   => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
            'campaign_id'    => 'nullable|exists:campaigns,id',
            'lead_list_id'   => 'nullable|exists:lead_lists,id',
        ]);

        $workspace = $request->user()->currentWorkspace;
        $user = $request->user();

        // 1. Find or create company by domain or company_name
        $companyId = null;
        $domain = $request->input('domain');
        $companyName = $request->input('company_name');

        if (!empty($domain) || !empty($companyName)) {
            $company = Company::where('workspace_id', $workspace->id)
                ->where(function ($q) use ($domain, $companyName) {
                    if (!empty($domain)) {
                        $q->where('domain', $domain);
                    }
                    if (!empty($companyName)) {
                        $q->orWhere('name', $companyName);
                    }
                })->first();

            if (!$company && !empty($companyName)) {
                $company = Company::create([
                    'workspace_id' => $workspace->id,
                    'created_by'   => $user->id,
                    'name'         => $companyName,
                    'domain'       => $domain,
                    'website'      => $request->input('website'),
                    'country'      => $request->input('country'),
                    'city'         => $request->input('city'),
                    'industry'     => $request->input('industry'),
                    'description'  => $request->input('notes'),
                ]);
            }
            $companyId = $company?->id;
        }

        // 2. Check if lead already exists with this email in the workspace
        $lead = Lead::where('workspace_id', $workspace->id)
            ->where('normalized_email', Lead::normalizeEmail($request->input('email')))
            ->first();

        $leadPayload = [
            'first_name'           => $request->input('first_name'),
            'last_name'            => $request->input('last_name') ?: '',
            'email'                => $request->input('email'),
            'phone'                => $request->input('phone'),
            'job_title'            => $request->input('job_title'),
            'website'              => $request->input('website'),
            'country'              => $request->input('country'),
            'city'                 => $request->input('city'),
            'industry'             => $request->input('industry'),
            'linkedin_url'         => $request->input('linkedin_url'),
            'company_id'           => $companyId,
            'source'               => 'web_scraper',
            'notes'                => $request->input('notes'),
            'assigned_campaign_id' => $request->input('campaign_id'),
        ];

        if ($lead) {
            $lead->update(array_filter($leadPayload, fn($v) => !is_null($v)));
        } else {
            $lead = Lead::create(array_merge($leadPayload, [
                'workspace_id' => $workspace->id,
                'created_by'   => $user->id,
                'status'       => 'new',
            ]));
        }

        // 3. Attach to Lead List if requested
        if ($request->filled('lead_list_id')) {
            $list = LeadList::where('workspace_id', $workspace->id)->find($request->input('lead_list_id'));
            if ($list) {
                $this->leadService->addToList($lead, $list, $user);
            }
        }

        // 4. Enroll in Campaign if selected
        if ($request->filled('campaign_id')) {
            $campaign = Campaign::where('workspace_id', $workspace->id)->find($request->input('campaign_id'));
            if ($campaign) {
                $this->campaignService->addRecipients($campaign, [$lead->id]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Lead '{$lead->full_name}' saved and linked to company successfully!",
            'lead_id' => $lead->id,
        ]);
    }
}
