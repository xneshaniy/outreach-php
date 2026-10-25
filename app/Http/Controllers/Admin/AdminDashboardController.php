<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\EmailAccount;
use App\Models\Invitation;
use App\Models\Message;
use App\Models\TokenUsageRecord;
use App\Models\User;
use App\Models\Workspace;
use App\Models\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. User stats
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $suspendedUsers = User::where('is_active', false)->count();
        $superAdmins = User::where('is_super_admin', true)->count();
        $newUsersThisMonth = User::where('created_at', '>=', Carbon::now()->startOfMonth())->count();

        // 2. Invitation stats
        $pendingInvites = Invitation::where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->count();
        $acceptedInvites = Invitation::where('status', 'accepted')->count();

        // 3. Email accounts
        $totalEmailAccounts = EmailAccount::count();
        $connectedEmailAccounts = EmailAccount::where('status', 'active')->count();
        $pausedEmailAccounts = EmailAccount::where('status', 'paused')->count();
        $errorEmailAccounts = EmailAccount::where('status', 'error')->count();
        $emailsSentToday = (int) EmailAccount::sum('sent_today');
        $totalDailyQuota = (int) EmailAccount::where('status', 'active')->sum('daily_limit');

        // 4. Campaigns
        $totalCampaigns = Campaign::count();
        $activeCampaigns = Campaign::where('status', 'active')->count();
        $pausedCampaigns = Campaign::where('status', 'paused')->count();
        $completedCampaigns = Campaign::where('status', 'completed')->count();
        $failedCampaigns = Campaign::where('status', 'failed')->count();
        $draftCampaigns = Campaign::where('status', 'draft')->count();

        // 5. Message delivery stats
        $totalMessages = Message::count();
        $sentMessages = Message::whereIn('status', ['sent', 'delivered'])->count();
        $failedMessages = Message::where('status', 'failed')->count();
        $bouncedMessages = Message::where('status', 'bounced')->count();
        $openedMessages = Message::whereNotNull('opened_at')->count();
        $clickedMessages = Message::whereNotNull('clicked_at')->count();
        $repliedMessages = Message::whereNotNull('replied_at')->count();

        // Delivery rates
        $deliveryRate = $totalMessages > 0 ? round(($sentMessages / $totalMessages) * 100, 1) : 0;
        $openRate = $sentMessages > 0 ? round(($openedMessages / $sentMessages) * 100, 1) : 0;
        $clickRate = $sentMessages > 0 ? round(($clickedMessages / $sentMessages) * 100, 1) : 0;
        $replyRate = $sentMessages > 0 ? round(($repliedMessages / $sentMessages) * 100, 1) : 0;
        $bounceRate = $totalMessages > 0 ? round(($bouncedMessages / $totalMessages) * 100, 1) : 0;

        // 6. Token & AI Cost stats
        $totalAiTokens = (int) TokenUsageRecord::sum('total_tokens');
        $totalInputTokens = (int) TokenUsageRecord::sum('input_tokens');
        $totalOutputTokens = (int) TokenUsageRecord::sum('output_tokens');
        $totalEstimatedCost = (float) TokenUsageRecord::sum('estimated_cost');
        $monthlyBudget = (float) AdminSetting::get('monthly_token_budget', 100.00);

        $tokensUsedThisMonth = (int) TokenUsageRecord::where('created_at', '>=', Carbon::now()->startOfMonth())->sum('total_tokens');
        $costThisMonth = (float) TokenUsageRecord::where('created_at', '>=', Carbon::now()->startOfMonth())->sum('estimated_cost');
        $remainingBudget = max(0, round($monthlyBudget - $costThisMonth, 2));

        // 7. Last 14 days email volume trend
        $daysRange = collect(range(13, 0))->map(fn($d) => Carbon::now()->subDays($d)->format('Y-m-d'));
        $dailySentData = DB::table('messages')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays(14))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('count', 'date');

        $dailyEmailsTrend = $daysRange->map(function ($date) use ($dailySentData) {
            return [
                'date' => Carbon::parse($date)->format('M d'),
                'count' => $dailySentData->get($date, 0),
            ];
        });

        // 8. Last 14 days token usage trend
        $dailyTokenData = DB::table('token_usage_records')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_tokens) as tokens'), DB::raw('SUM(estimated_cost) as cost'))
            ->where('created_at', '>=', Carbon::now()->subDays(14))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('date');

        $dailyTokensTrend = $daysRange->map(function ($date) use ($dailyTokenData) {
            $item = $dailyTokenData->get($date);
            return [
                'date' => Carbon::parse($date)->format('M d'),
                'tokens' => $item ? (int) $item->tokens : 0,
                'cost' => $item ? round((float) $item->cost, 4) : 0,
            ];
        });

        // 9. Recent activities
        $recentUsers = User::orderByDesc('created_at')->take(5)->get(['id', 'name', 'email', 'is_active', 'is_super_admin', 'created_at']);
        $recentCampaigns = Campaign::with(['user:id,name'])->orderByDesc('created_at')->take(5)->get();

        // 10. Queue & System health
        $queueBacklog = DB::table('jobs')->count();
        $failedJobsCount = DB::table('failed_jobs')->count();

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'suspended' => $suspendedUsers,
                    'super_admins' => $superAdmins,
                    'new_this_month' => $newUsersThisMonth,
                    'pending_invitations' => $pendingInvites,
                    'accepted_invitations' => $acceptedInvites,
                ],
                'emailAccounts' => [
                    'total' => $totalEmailAccounts,
                    'connected' => $connectedEmailAccounts,
                    'paused' => $pausedEmailAccounts,
                    'error' => $errorEmailAccounts,
                    'sent_today' => $emailsSentToday,
                    'daily_quota' => $totalDailyQuota,
                ],
                'campaigns' => [
                    'total' => $totalCampaigns,
                    'active' => $activeCampaigns,
                    'paused' => $pausedCampaigns,
                    'completed' => $completedCampaigns,
                    'failed' => $failedCampaigns,
                    'draft' => $draftCampaigns,
                ],
                'messages' => [
                    'total' => $totalMessages,
                    'sent' => $sentMessages,
                    'failed' => $failedMessages,
                    'bounced' => $bouncedMessages,
                    'opened' => $openedMessages,
                    'clicked' => $clickedMessages,
                    'replied' => $repliedMessages,
                    'delivery_rate' => $deliveryRate,
                    'open_rate' => $openRate,
                    'click_rate' => $clickRate,
                    'reply_rate' => $replyRate,
                    'bounce_rate' => $bounceRate,
                ],
                'tokens' => [
                    'total_tokens' => $totalAiTokens,
                    'input_tokens' => $totalInputTokens,
                    'output_tokens' => $totalOutputTokens,
                    'total_cost' => round($totalEstimatedCost, 2),
                    'tokens_this_month' => $tokensUsedThisMonth,
                    'cost_this_month' => round($costThisMonth, 2),
                    'monthly_budget' => $monthlyBudget,
                    'remaining_budget' => $remainingBudget,
                ],
                'system' => [
                    'queue_backlog' => $queueBacklog,
                    'failed_jobs' => $failedJobsCount,
                    'php_version' => PHP_VERSION,
                    'environment' => config('app.env'),
                ],
            ],
            'dailyEmailsTrend' => $dailyEmailsTrend,
            'dailyTokensTrend' => $dailyTokensTrend,
            'recentUsers' => $recentUsers,
            'recentCampaigns' => $recentCampaigns,
        ]);
    }
}
