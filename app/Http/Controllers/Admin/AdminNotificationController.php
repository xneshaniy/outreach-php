<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class AdminNotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $defaultPreferences = [
            'admin_email' => auth()->user()->email,
            'notify_daily_usage' => false,
            'notify_budget_thresholds' => true,
            'notify_campaign_failed' => true,
            'notify_smtp_failed' => true,
            'notify_excessive_bounces' => true,
            'bounce_threshold_percent' => 5,
            'notify_new_user' => true,
            'notify_invitation_accepted' => true,
        ];

        $settings = AdminSetting::get('admin_notifications', $defaultPreferences);

        // Check if SMTP is configured in .env or default mailer
        $smtpConfigured = !empty(config('mail.mailers.smtp.host')) && !empty(config('mail.mailers.smtp.username'));

        return Inertia::render('Admin/Notifications/Index', [
            'settings' => array_merge($defaultPreferences, (array) $settings),
            'smtpConfigured' => $smtpConfigured,
            'mailDriver' => config('mail.default'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_email' => 'required|email|max:255',
            'notify_daily_usage' => 'boolean',
            'notify_budget_thresholds' => 'boolean',
            'notify_campaign_failed' => 'boolean',
            'notify_smtp_failed' => 'boolean',
            'notify_excessive_bounces' => 'boolean',
            'bounce_threshold_percent' => 'required|integer|min:1|max:50',
            'notify_new_user' => 'boolean',
            'notify_invitation_accepted' => 'boolean',
        ]);

        AdminSetting::set('admin_notifications', $validated);

        activity()
            ->causedBy(auth()->user())
            ->log('Admin notification preferences updated');

        return back()->with('success', 'Notification preferences saved successfully.');
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $settings = AdminSetting::get('admin_notifications', []);
        $email = $settings['admin_email'] ?? auth()->user()->email;

        try {
            Mail::raw("Hello from Outreach CRM!\n\nThis is a test notification confirming that your admin email alert system is functioning properly.\n\nTimestamp: " . now()->toIso8601String(), function ($message) use ($email) {
                $message->to($email)->subject('[Outreach CRM] Admin Notification Test');
            });

            return back()->with('success', "Test email sent successfully to {$email}!");
        } catch (\Throwable $e) {
            return back()->withErrors([
                'smtp_warning' => "Could not send test email. Reason: {$e->getMessage()}. The application will continue operating normally.",
            ]);
        }
    }
}
