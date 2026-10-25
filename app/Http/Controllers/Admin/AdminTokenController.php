<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailAccount;
use App\Models\Invitation;
use App\Models\TokenUsageRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class AdminTokenController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. API Tokens (Sanctum)
        $apiTokens = DB::table('personal_access_tokens')
            ->join('users', 'personal_access_tokens.tokenable_id', '=', 'users.id')
            ->select(
                'personal_access_tokens.id',
                'personal_access_tokens.name',
                'personal_access_tokens.last_used_at',
                'personal_access_tokens.created_at',
                'users.id as user_id',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->orderByDesc('personal_access_tokens.created_at')
            ->get();

        // 2. AI Token Usage summary by provider and model
        $aiUsageSummary = TokenUsageRecord::select(
                'provider',
                'model',
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(input_tokens) as total_input'),
                DB::raw('SUM(output_tokens) as total_output'),
                DB::raw('SUM(cached_tokens) as total_cached'),
                DB::raw('SUM(total_tokens) as total_tokens'),
                DB::raw('SUM(estimated_cost) as total_cost')
            )
            ->groupBy('provider', 'model')
            ->get();

        // 3. SMTP & OAuth Credentials status
        $emailCredentials = EmailAccount::with(['user:id,name,email', 'workspace:id,name'])
            ->select('id', 'user_id', 'workspace_id', 'name', 'email', 'provider', 'host as smtp_host', 'port as smtp_port', 'encryption as smtp_encryption', 'is_active', 'last_tested_at', 'last_test_status', 'last_test_message as last_error')
            ->orderBy('email')
            ->get();

        // 4. Invitation Tokens
        $invitationTokens = Invitation::with(['invitedBy:id,name'])
            ->select('id', 'email', 'role', 'status', 'expires_at', 'created_at', 'invited_by_id')
            ->orderByDesc('created_at')
            ->take(15)
            ->get();

        return Inertia::render('Admin/Tokens/Index', [
            'apiTokens' => $apiTokens,
            'aiUsageSummary' => $aiUsageSummary,
            'emailCredentials' => $emailCredentials,
            'invitationTokens' => $invitationTokens,
            'tokenMetrics' => [
                'totalApiTokens' => $apiTokens->count(),
                'totalTokensConsumed' => (int) TokenUsageRecord::sum('total_tokens'),
                'totalEstimatedCost' => (float) TokenUsageRecord::sum('estimated_cost'),
                'connectedSmtp' => $emailCredentials->where('status', 'active')->count(),
            ],
        ]);
    }

    public function revokeApiToken(int $id): RedirectResponse
    {
        DB::table('personal_access_tokens')->where('id', $id)->delete();

        activity()
            ->causedBy(auth()->user())
            ->log("Personal API token (ID: {$id}) revoked by Super Admin");

        return back()->with('success', 'API token has been revoked.');
    }
}
