<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AIConfiguration;
use App\Models\Lead;
use App\Models\LeadResearch;
use App\Models\LeadScoringRule;
use App\Services\AIService;
use App\Services\LeadScoreService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AIController extends Controller
{
    public function __construct(
        protected AIService $aiService,
        protected LeadScoreService $leadScoreService
    ) {}

    /**
     * AI Settings & Lead Scoring Rules page.
     */
    public function settings(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $config = AIConfiguration::firstOrCreate(
            ['workspace_id' => $workspace->id],
            [
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'is_active' => true,
                'daily_token_limit' => 100000,
            ]
        );

        $rules = $this->leadScoreService->getRules($workspace);

        return Inertia::render('Settings/AI', [
            'config' => [
                'id' => $config->id,
                'provider' => $config->provider,
                'model' => $config->model,
                'has_api_key' => !empty($config->api_key),
                'is_active' => (bool)$config->is_active,
                'daily_token_limit' => $config->daily_token_limit,
                'tokens_used_today' => $config->tokens_used_today,
                'last_tested_at' => $config->last_tested_at?->diffForHumans(),
                'last_test_status' => $config->last_test_status,
                'last_test_message' => $config->last_test_message,
            ],
            'rules' => $rules,
            'providers' => [
                ['id' => 'openai', 'name' => 'OpenAI (GPT-4o / GPT-4o-mini)', 'default_model' => 'gpt-4o-mini'],
                ['id' => 'gemini', 'name' => 'Google Gemini (Gemini 1.5 Flash / Pro)', 'default_model' => 'gemini-1.5-flash'],
                ['id' => 'custom', 'name' => 'OpenAI-Compatible Custom Endpoint', 'default_model' => 'gpt-4o-mini'],
            ],
            'models' => [
                'openai' => ['gpt-4o-mini', 'gpt-4o', 'gpt-3.5-turbo'],
                'gemini' => ['gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-2.0-flash-exp'],
                'custom' => ['gpt-4o-mini', 'custom-model'],
            ],
        ]);
    }

    /**
     * Update AI Configuration.
     */
    public function updateSettings(Request $request)
    {
        $workspace = $request->user()->currentWorkspace;

        $request->validate([
            'provider' => 'required|string|in:openai,gemini,custom',
            'model' => 'required|string',
            'api_key' => 'nullable|string',
            'is_active' => 'boolean',
            'daily_token_limit' => 'nullable|integer|min:1000|max:10000000',
        ]);

        $config = AIConfiguration::firstOrNew(['workspace_id' => $workspace->id]);
        $config->provider = $request->provider;
        $config->model = $request->model;
        $config->is_active = $request->boolean('is_active', true);
        if ($request->filled('daily_token_limit')) {
            $config->daily_token_limit = (int)$request->daily_token_limit;
        }

        if ($request->filled('api_key')) {
            $config->api_key = $request->api_key;
        }

        $config->save();

        return back()->with('success', 'AI settings updated successfully.');
    }

    /**
     * Test live AI API connectivity.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;

        $request->validate([
            'provider' => 'required|string',
            'model' => 'required|string',
            'api_key' => 'nullable|string',
        ]);

        $config = $this->aiService->getConfig($workspace);
        $apiKey = $request->filled('api_key') ? $request->api_key : ($config?->api_key ?? '');

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide an API Key to test.',
            ], 422);
        }

        $result = $this->aiService->testConnection($request->provider, $apiKey, $request->model);

        if ($config) {
            $config->update([
                'last_tested_at' => now(),
                'last_test_status' => $result['success'] ? 'success' : 'failed',
                'last_test_message' => $result['message'],
            ]);
        }

        return response()->json($result);
    }

    /**
     * Update Scoring Rules.
     */
    public function updateScoringRules(Request $request)
    {
        $workspace = $request->user()->currentWorkspace;

        $request->validate([
            'rules' => 'required|array',
            'rules.*.id' => 'required|exists:lead_scoring_rules,id',
            'rules.*.points' => 'required|integer|min:0|max:100',
            'rules.*.is_active' => 'required|boolean',
        ]);

        foreach ($request->rules as $r) {
            LeadScoringRule::where('workspace_id', $workspace->id)
                ->where('id', $r['id'])
                ->update([
                    'points' => (int)$r['points'],
                    'is_active' => (bool)$r['is_active'],
                ]);
        }

        return back()->with('success', 'Lead scoring rules saved successfully.');
    }

    /**
     * Email Writing Assistant generation endpoint.
     */
    public function assistantSuggestions(Request $request): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;

        $result = $this->aiService->generateAssistantSuggestions($workspace, $request->only([
            'objective', 'audience', 'product', 'tone', 'length', 'cta'
        ]));

        return response()->json($result);
    }

    /**
     * Rewrite email content (shorter, more professional, more friendly, improve subject, follow up).
     */
    public function rewriteContent(Request $request): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;

        $request->validate([
            'content' => 'required|string',
            'mode' => 'required|string',
            'instructions' => 'nullable|string',
        ]);

        $result = $this->aiService->rewriteContent(
            $workspace,
            $request->content,
            $request->mode,
            $request->input('instructions', '')
        );

        return response()->json($result);
    }

    /**
     * Research a lead and save/return structured insights.
     */
    public function researchLead(Request $request, Lead $lead): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;

        $leadData = array_merge([
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'company' => $lead->company ?: ($lead->company?->name ?? ''),
            'website' => $lead->website ?: ($request->input('website', '')),
            'job_title' => $lead->job_title,
            'linkedin_url' => $lead->linkedin_url ?: ($request->input('linkedin_url', '')),
            'industry' => $lead->industry,
        ], $request->only(['company', 'website', 'job_title', 'linkedin_url']));

        $result = $this->aiService->researchLead($workspace, $leadData);

        if ($result['success'] && !empty($result['data'])) {
            $data = $result['data'];

            LeadResearch::updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'lead_id' => $lead->id,
                ],
                [
                    'created_by' => $request->user()->id,
                    'company_overview' => $data['company_overview'] ?? null,
                    'industry' => $data['industry'] ?? null,
                    'potential_needs' => $data['potential_needs'] ?? null,
                    'relevant_services' => $data['relevant_services'] ?? null,
                    'outreach_angle' => $data['outreach_angle'] ?? null,
                    'suggested_subject' => $data['suggested_subject'] ?? null,
                    'suggested_opening' => $data['suggested_opening'] ?? null,
                    'raw_analysis' => $data,
                    'verified' => false,
                ]
            );

            // If lead missing industry or website, fill from research
            if (empty($lead->industry) && !empty($data['industry'])) {
                $lead->update(['industry' => $data['industry']]);
            }
        }

        return response()->json($result);
    }

    /**
     * Personalize email preview for a specific lead.
     */
    public function personalizeLead(Request $request, Lead $lead): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;

        $result = $this->aiService->generatePersonalizedEmail(
            $workspace,
            $lead,
            $request->input('objective', 'General Sales Outreach'),
            $request->input('template', '')
        );

        return response()->json($result);
    }
}
