<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Models\Campaign;
use App\Models\TokenUsageRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminCostController extends Controller
{
    protected array $defaultRates = [
        'gpt-4o-mini' => [
            'provider' => 'OpenAI',
            'name' => 'GPT-4o Mini',
            'input_per_m' => 0.15,
            'output_per_m' => 0.60,
            'cached_per_m' => 0.075,
        ],
        'gpt-4o' => [
            'provider' => 'OpenAI',
            'name' => 'GPT-4o',
            'input_per_m' => 2.50,
            'output_per_m' => 10.00,
            'cached_per_m' => 1.25,
        ],
        'gemini-1.5-flash' => [
            'provider' => 'Google Gemini',
            'name' => 'Gemini 1.5 Flash',
            'input_per_m' => 0.075,
            'output_per_m' => 0.30,
            'cached_per_m' => 0.01875,
        ],
        'claude-3-5-sonnet' => [
            'provider' => 'Anthropic',
            'name' => 'Claude 3.5 Sonnet',
            'input_per_m' => 3.00,
            'output_per_m' => 15.00,
            'cached_per_m' => 0.30,
        ],
    ];

    public function index(Request $request): Response
    {
        $customRates = AdminSetting::get('ai_pricing_rates', []);
        $rates = array_merge($this->defaultRates, $customRates);

        // Budget settings
        $monthlyBudget = (float) AdminSetting::get('monthly_token_budget', 100.00);

        // Cost this month
        $costThisMonth = (float) TokenUsageRecord::where('created_at', '>=', Carbon::now()->startOfMonth())->sum('estimated_cost');
        $costLastMonth = (float) TokenUsageRecord::whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth(),
        ])->sum('estimated_cost');

        // Total cost all time
        $totalCostAllTime = (float) TokenUsageRecord::sum('estimated_cost');
        $actualProviderReported = (float) TokenUsageRecord::whereNotNull('actual_cost')->sum('actual_cost');

        // Cost per user
        $costPerUser = TokenUsageRecord::join('users', 'token_usage_records.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.email', DB::raw('SUM(total_tokens) as tokens'), DB::raw('SUM(estimated_cost) as cost'))
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('cost')
            ->take(10)
            ->get();

        // Cost per campaign
        $costPerCampaign = TokenUsageRecord::join('campaigns', 'token_usage_records.campaign_id', '=', 'campaigns.id')
            ->select('campaigns.id', 'campaigns.name', DB::raw('SUM(total_tokens) as tokens'), DB::raw('SUM(estimated_cost) as cost'))
            ->groupBy('campaigns.id', 'campaigns.name')
            ->orderByDesc('cost')
            ->take(10)
            ->get();

        // Cost per provider
        $costPerProvider = TokenUsageRecord::select('provider', DB::raw('SUM(total_tokens) as tokens'), DB::raw('SUM(estimated_cost) as cost'))
            ->groupBy('provider')
            ->get();

        // Daily cost trend over last 30 days
        $dailyCost = TokenUsageRecord::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(estimated_cost) as cost'), DB::raw('SUM(total_tokens) as tokens'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return Inertia::render('Admin/Costs/Index', [
            'rates' => $rates,
            'budget' => [
                'monthlyBudget' => $monthlyBudget,
                'costThisMonth' => round($costThisMonth, 2),
                'costLastMonth' => round($costLastMonth, 2),
                'remainingBudget' => max(0, round($monthlyBudget - $costThisMonth, 2)),
                'totalCostAllTime' => round($totalCostAllTime, 2),
                'actualProviderReported' => round($actualProviderReported, 2),
            ],
            'costPerUser' => $costPerUser,
            'costPerCampaign' => $costPerCampaign,
            'costPerProvider' => $costPerProvider,
            'dailyCost' => $dailyCost,
        ]);
    }

    public function updateRates(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rates' => 'required|array',
            'monthly_token_budget' => 'required|numeric|min:0|max:50000',
        ]);

        AdminSetting::set('ai_pricing_rates', $validated['rates']);
        AdminSetting::set('monthly_token_budget', $validated['monthly_token_budget']);

        activity()
            ->causedBy(auth()->user())
            ->log('AI token pricing rates and monthly budget updated');

        return back()->with('success', 'Token pricing models and budget updated successfully.');
    }
}
