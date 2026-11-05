<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadList;
use App\Services\CampaignService;
use App\Services\GoogleMapsScraperService;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoogleMapsScraperController extends Controller
{
    public function __construct(
        protected GoogleMapsScraperService $mapsScraperService,
        protected LeadService $leadService,
        protected CampaignService $campaignService
    ) {}

    /**
     * Show Google Maps Scraper view.
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

        return Inertia::render('Leads/GoogleMapsScraper', [
            'campaigns' => $campaigns,
            'leadLists' => $leadLists,
        ]);
    }

    /**
     * Scrape businesses from Google Maps for given query & location.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query'           => 'required|string|max:1000',
            'location'        => 'nullable|string|max:255',
            'limit'           => 'nullable|integer|min:1|max:1000',
            'rating'          => 'nullable|string|max:50',
            'enrich_websites' => 'nullable|boolean',
        ]);

        $workspace = $request->user()->currentWorkspace;
        $query = $request->input('query');
        $location = $request->input('location', '');
        $limit = (int) $request->input('limit', 20);
        $rating = $request->input('rating', 'all');
        $enrich = (bool) $request->input('enrich_websites', true);

        $result = $this->mapsScraperService->scrapeGoogleMaps(
            $workspace,
            $query,
            $location,
            $limit,
            $enrich,
            $rating
        );

        return response()->json($result);
    }

    /**
     * Save one or multiple scraped Google Maps leads into CRM.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'leads'          => 'required|array|min:1',
            'leads.*.company_name' => 'nullable|string|max:255',
            'leads.*.email'        => 'nullable|email|max:255',
            'leads.*.phone'        => 'nullable|string|max:50',
            'leads.*.website'      => 'nullable|string|max:255',
            'leads.*.country'      => 'nullable|string|max:100',
            'leads.*.city'         => 'nullable|string|max:100',
            'leads.*.address'      => 'nullable|string|max:255',
            'leads.*.first_name'   => 'nullable|string|max:100',
            'leads.*.last_name'    => 'nullable|string|max:100',
            'leads.*.job_title'    => 'nullable|string|max:150',
            'leads.*.notes'        => 'nullable|string',
            'campaign_id'    => 'nullable|exists:campaigns,id',
            'lead_list_id'   => 'nullable|exists:lead_lists,id',
        ]);

        $workspace = $request->user()->currentWorkspace;
        $user = $request->user();
        $importedCount = 0;
        $createdLeadIds = [];

        $campaign = null;
        if ($request->filled('campaign_id')) {
            $campaign = Campaign::where('workspace_id', $workspace->id)->find($request->input('campaign_id'));
        }

        $leadList = null;
        if ($request->filled('lead_list_id')) {
            $leadList = LeadList::where('workspace_id', $workspace->id)->find($request->input('lead_list_id'));
        }

        foreach ($request->input('leads') as $item) {
            $email = trim($item['email'] ?? '');
            $website = trim($item['website'] ?? '');
            $companyName = trim($item['company_name'] ?? '');
            $domain = '';

            if (!empty($website)) {
                $domain = parse_url($website, PHP_URL_HOST) ?? $website;
                $domain = preg_replace('/^www\./i', '', $domain);
            }

            // Fallback email if totally empty: synthesize a valid unique contact placeholder so lead is never dropped
            if (empty($email)) {
                if (!empty($domain)) {
                    $email = 'contact@' . $domain;
                } else {
                    $slug = \Illuminate\Support\Str::slug($companyName ?: 'maps-lead-' . time() . '-' . rand(100, 999));
                    $email = $slug . '@lead.outreach.click';
                }
            }

            // Build detailed social & profile notes
            $socialNotes = [];
            if (!empty($item['facebook_url'])) {
                $socialNotes[] = "Facebook: " . $item['facebook_url'];
            }
            if (!empty($item['instagram_url'])) {
                $socialNotes[] = "Instagram: " . $item['instagram_url'];
            }
            if (!empty($item['linkedin_url'])) {
                $socialNotes[] = "LinkedIn: " . $item['linkedin_url'];
            }
            if (!empty($item['twitter_url'])) {
                $socialNotes[] = "X/Twitter: " . $item['twitter_url'];
            }
            if (!empty($item['rating'])) {
                $socialNotes[] = "Rating: " . $item['rating'] . "★ (" . ($item['reviews'] ?? '0') . " reviews)";
            }

            $combinedNotes = trim(($item['notes'] ?? '') . "\n" . implode("\n", $socialNotes));

            // 1. Find or create company
            $companyId = null;
            if (!empty($companyName) || !empty($domain)) {
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
                        'website'      => $website,
                        'country'      => $item['country'] ?? '',
                        'city'         => $item['city'] ?? '',
                        'address'      => $item['address'] ?? '',
                        'phone'        => $item['phone'] ?? '',
                        'linkedin_url' => $item['linkedin_url'] ?? '',
                        'description'  => $combinedNotes ?: 'Imported from Google Maps Scraper',
                    ]);
                }
                $companyId = $company?->id;
            }

            // 2. Lead record
            $lead = Lead::where('workspace_id', $workspace->id)
                ->where('normalized_email', Lead::normalizeEmail($email))
                ->first();

            $leadPayload = [
                'first_name'           => $item['first_name'] ?: ($companyName ?: 'Local'),
                'last_name'            => $item['last_name'] ?: 'Business',
                'email'                => $email,
                'phone'                => $item['phone'] ?? '',
                'website'              => $website,
                'job_title'            => $item['job_title'] ?: 'Owner / Manager',
                'country'              => $item['country'] ?? '',
                'city'                 => $item['city'] ?? '',
                'company_id'           => $companyId,
                'source'               => 'google_maps',
                'linkedin_url'         => $item['linkedin_url'] ?? '',
                'notes'                => $combinedNotes,
                'assigned_campaign_id' => $campaign?->id,
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

            $importedCount++;
            $createdLeadIds[] = $lead->id;

            // 3. Attach to list
            if ($leadList) {
                $this->leadService->addToList($lead, $leadList, $user);
            }
        }

        // 4. Enroll all leads in campaign
        if ($campaign && !empty($createdLeadIds)) {
            $this->campaignService->addRecipients($campaign, $createdLeadIds);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully imported {$importedCount} Google Maps leads into CRM!",
            'count'   => $importedCount,
        ]);
    }
}
