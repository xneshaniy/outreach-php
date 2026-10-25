<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCampaignController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Campaign::with(['creator:id,name,email', 'workspace:id,name'])
            ->withCount(['recipients']);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $campaigns = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Campaigns/Index', [
            'campaigns' => $campaigns,
            'filters' => $request->only(['search', 'status']),
            'summary' => [
                'total' => Campaign::count(),
                'active' => Campaign::where('status', 'active')->count(),
                'paused' => Campaign::where('status', 'paused')->count(),
                'completed' => Campaign::where('status', 'completed')->count(),
                'failed' => Campaign::where('status', 'failed')->count(),
                'draft' => Campaign::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function pause(Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== 'active') {
            return back()->withErrors(['error' => 'Only active campaigns can be paused.']);
        }

        $campaign->update(['status' => 'paused']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($campaign)
            ->log('Campaign paused by Admin');

        return back()->with('success', 'Campaign has been paused.');
    }

    public function resume(Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== 'paused') {
            return back()->withErrors(['error' => 'Only paused campaigns can be resumed.']);
        }

        $campaign->update(['status' => 'active']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($campaign)
            ->log('Campaign resumed by Admin');

        return back()->with('success', 'Campaign resumed successfully.');
    }

    public function cancel(Campaign $campaign): RedirectResponse
    {
        $campaign->update(['status' => 'cancelled']);

        // Cancel any pending messages
        Message::where('campaign_id', $campaign->id)
            ->where('status', 'queued')
            ->update(['status' => 'failed', 'error_message' => 'Campaign cancelled by Super Admin']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($campaign)
            ->log('Campaign cancelled by Admin');

        return back()->with('success', 'Campaign cancelled and pending jobs halted.');
    }
}
