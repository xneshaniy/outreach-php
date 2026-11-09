<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Models\LinkedInJobApplication;
use App\Models\LinkedInJobSearch;
use App\Models\LinkedInNetworkInvite;
use App\Models\LinkedInInboundMessage;
use App\Models\Workspace;
use App\Services\LinkedInApplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class LinkedInApplierController extends Controller
{
    public function __construct(
        protected LinkedInApplierService $applierService
    ) {}

    // -------------------------------------------------------------------------
    // Helper: resolve workspace with multi-level fallback (never crashes)
    // -------------------------------------------------------------------------
    protected function getWorkspace(Request $request): ?Workspace
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return $user->currentWorkspace
            ?? $user->workspaces()->first()
            ?? Workspace::first();
    }

    // -------------------------------------------------------------------------
    // Helper: ensure LinkedIn tables exist, auto-migrate once if they're missing
    // -------------------------------------------------------------------------
    protected function ensureTables(): void
    {
        if (! Schema::hasTable('linkedin_job_searches')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::error('LinkedInApplier auto-migrate failed: ' . $e->getMessage());
            }
        }
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------
    public function index(Request $request): Response
    {
        $workspace = $this->getWorkspace($request);

        // Guard: no workspace yet → return empty page so Vue can render gracefully
        if (! $workspace) {
            return Inertia::render('Tools/LinkedInApplier/Index', [
                'applications'  => ['data' => []],
                'networkInvites' => [],
                'inboxMessages' => [],
                'searches'      => [],
            ]);
        }

        // Auto-migrate if tables are missing
        $this->ensureTables();

        // ---- queries (each wrapped in try/catch) ----------------------------

        $applications = ['data' => []];
        try {
            if (Schema::hasTable('linkedin_job_applications')) {
                $applications = LinkedInJobApplication::where('workspace_id', $workspace->id)
                    ->latest()
                    ->paginate(15);
            }
        } catch (\Throwable $e) {
            Log::warning('LinkedInApplier: linkedin_job_applications query failed – ' . $e->getMessage());
        }

        $networkInvites = [];
        try {
            if (Schema::hasTable('linkedin_network_invites')) {
                $networkInvites = LinkedInNetworkInvite::where('workspace_id', $workspace->id)
                    ->latest()
                    ->limit(30)
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::warning('LinkedInApplier: linkedin_network_invites query failed – ' . $e->getMessage());
        }

        $inboxMessages = [];
        try {
            if (Schema::hasTable('linkedin_inbound_messages')) {
                $inboxMessages = LinkedInInboundMessage::where('workspace_id', $workspace->id)
                    ->latest()
                    ->limit(30)
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::warning('LinkedInApplier: linkedin_inbound_messages query failed – ' . $e->getMessage());
        }

        $searches = [];
        try {
            if (Schema::hasTable('linkedin_job_searches')) {
                $searches = LinkedInJobSearch::where('workspace_id', $workspace->id)
                    ->latest()
                    ->limit(5)
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::warning('LinkedInApplier: linkedin_job_searches query failed – ' . $e->getMessage());
        }

        return Inertia::render('Tools/LinkedInApplier/Index', [
            'applications'   => $applications,
            'networkInvites' => $networkInvites,
            'inboxMessages'  => $inboxMessages,
            'searches'       => $searches,
        ]);
    }

    // -------------------------------------------------------------------------
    // search
    // -------------------------------------------------------------------------
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'query'            => 'required|string|max:200',
            'location'         => 'nullable|string|max:100',
            'job_type'         => 'nullable|string',
            'experience_level' => 'nullable|string',
        ]);

        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }

        $search = $this->applierService->searchJobs(
            $request->input('query'),
            $request->input('location'),
            $request->input('job_type'),
            $request->input('experience_level'),
            $workspace,
            $request->user()
        );

        return response()->json([
            'search'       => $search,
            'applications' => $search->applications,
            'message'      => "Found {$search->results_count} job vacancies matching your criteria.",
        ]);
    }

    // -------------------------------------------------------------------------
    // tailor
    // -------------------------------------------------------------------------
    public function tailor(Request $request, LinkedInJobApplication $application): \Illuminate\Http\JsonResponse
    {
        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }
        if ($application->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $candidateProfile = $request->input('candidate_profile', [
            'skills'             => ['Outreach', 'Cold Email', 'LinkedIn Sales Navigator', 'Pipeline Generation', 'CRM Architecture', 'High Ticket Closing'],
            'experience_summary' => '7+ years leading enterprise outbound sales teams, scaling pipeline from $0 to $3M ARR with automated multi-channel sequences.',
        ]);

        $customization = $this->applierService->tailorApplication($application, $candidateProfile);

        return response()->json([
            'customization' => $customization,
            'application'   => $application->fresh(),
            'message'       => 'Resume bullets, matched keywords, and cover pitch tailored successfully.',
        ]);
    }

    // -------------------------------------------------------------------------
    // solveQuestions
    // -------------------------------------------------------------------------
    public function solveQuestions(Request $request, LinkedInJobApplication $application): \Illuminate\Http\JsonResponse
    {
        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }
        if ($application->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $questions = $request->input('questions', [
            'How many years of experience do you have with outbound B2B sales?',
            'What are your compensation / salary expectations?',
            'Are you legally authorized to work in the country without sponsorship?',
            'What is your earliest possible start date or notice period?',
        ]);

        $candidateProfile = $request->input('candidate_profile', [
            'years_experience'   => '6+ years',
            'salary_expectation' => '$130,000 / year',
            'work_authorization' => 'Authorized / Citizen',
            'notice_period'      => '2 weeks',
        ]);

        $answers = $this->applierService->solveScreeningQuestions($application, $questions, $candidateProfile);

        return response()->json([
            'answers'     => $answers,
            'application' => $application->fresh(),
            'message'     => 'Screening questions resolved with optimal candidate responses.',
        ]);
    }

    // -------------------------------------------------------------------------
    // generateLion
    // -------------------------------------------------------------------------
    public function generateLion(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'industry' => 'required|string|max:100',
            'role'     => 'nullable|string|max:100',
        ]);

        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }

        $invites = $this->applierService->generateLionNetworkers(
            $request->input('industry'),
            $request->input('role', 'Sales'),
            $workspace,
            $request->user(),
            8
        );

        return response()->json([
            'invites' => $invites,
            'message' => 'Discovered 8 high-value L.I.O.N. open networkers with personalized invite notes.',
        ]);
    }

    // -------------------------------------------------------------------------
    // classifyMessage
    // -------------------------------------------------------------------------
    public function classifyMessage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'sender_name'  => 'required|string|max:100',
            'message_text' => 'required|string',
        ]);

        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }

        $inbound = $this->applierService->classifyAndDraftMessage(
            $request->input('sender_name'),
            $request->input('message_text'),
            $workspace,
            $request->user()
        );

        return response()->json([
            'message_item' => $inbound,
            'message'      => 'Inbound message analyzed and smart reply drafted.',
        ]);
    }

    // -------------------------------------------------------------------------
    // updateStatus
    // -------------------------------------------------------------------------
    public function updateStatus(Request $request, LinkedInJobApplication $application): \Illuminate\Http\JsonResponse
    {
        $workspace = $this->getWorkspace($request);
        if (! $workspace) {
            return response()->json(['message' => 'No workspace found.'], 422);
        }
        if ($application->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate(['status' => 'required|string|in:discovered,tailored,applied,interviewing,offered,rejected']);
        $application->update([
            'status'     => $request->input('status'),
            'applied_at' => $request->input('status') === 'applied' ? now() : $application->applied_at,
        ]);

        return response()->json([
            'application' => $application->fresh(),
            'message'     => 'Application status updated.',
        ]);
    }
}
