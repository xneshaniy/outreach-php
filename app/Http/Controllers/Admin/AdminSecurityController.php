<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminSecurityController extends Controller
{
    public function index(Request $request): Response
    {
        $securityAudit = [
            'https_enabled' => $request->isSecure(),
            'app_debug' => config('app.debug'),
            'app_env' => config('app.env'),
            'session_driver' => config('session.driver'),
            'session_secure' => config('session.secure'),
            'session_http_only' => config('session.http_only'),
            'session_same_site' => config('session.same_site'),
            'php_version' => PHP_VERSION,
            'active_sessions_count' => DB::table('sessions')->count(),
        ];

        $securitySettings = AdminSetting::get('security_settings', [
            'password_min_length' => 8,
            'require_uppercase' => true,
            'require_numeric' => true,
            'require_symbols' => false,
            'session_lifetime_minutes' => 120,
            'max_login_attempts' => 5,
            'lockout_duration_minutes' => 15,
        ]);

        return Inertia::render('Admin/Security/Index', [
            'audit' => $securityAudit,
            'settings' => $securitySettings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password_min_length' => 'required|integer|min:8|max:32',
            'require_uppercase' => 'boolean',
            'require_numeric' => 'boolean',
            'require_symbols' => 'boolean',
            'session_lifetime_minutes' => 'required|integer|min:15|max:1440',
            'max_login_attempts' => 'required|integer|min:3|max:10',
            'lockout_duration_minutes' => 'required|integer|min:1|max:60',
        ]);

        AdminSetting::set('security_settings', $validated);

        activity()
            ->causedBy(auth()->user())
            ->log('System security settings updated');

        return back()->with('success', 'Security policies saved successfully.');
    }
}
