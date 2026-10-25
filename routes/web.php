<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Leads\LeadController;
use App\Http\Controllers\Companies\CompanyController;
use App\Http\Controllers\Campaigns\CampaignController;
use App\Http\Controllers\EmailAccounts\EmailAccountController;
use App\Http\Controllers\Pipeline\PipelineController;
use App\Http\Controllers\Tasks\TaskController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\AI\AIController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminInvitationController;
use App\Http\Controllers\Admin\AdminEmailAccountController;
use App\Http\Controllers\Admin\AdminCampaignController;
use App\Http\Controllers\Admin\AdminTokenController;
use App\Http\Controllers\Admin\AdminCostController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminSecurityController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => redirect()->route('dashboard'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'workspace.selected', 'workspace.auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Leads
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('index');
        Route::get('/create', [LeadController::class, 'create'])->name('create');
        Route::post('/', [LeadController::class, 'store'])->name('store');
        Route::get('/import', [LeadController::class, 'importForm'])->name('import.form');
        Route::post('/import/preview', [LeadController::class, 'importPreview'])->name('import.preview');
        Route::post('/import', [LeadController::class, 'importProcess'])->name('import.process');
        Route::get('/imports', [LeadController::class, 'importsIndex'])->name('imports.index');
        Route::get('/imports/{import}', [LeadController::class, 'importShow'])->name('imports.show');
        Route::get('/export', [LeadController::class, 'export'])->name('export');
        Route::post('/bulk-action', [LeadController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/remove-duplicates', [LeadController::class, 'removeDuplicates'])->name('remove-duplicates');
        Route::get('/{lead}', [LeadController::class, 'show'])->name('show');
        Route::get('/{lead}/edit', [LeadController::class, 'edit'])->name('edit');
        Route::put('/{lead}', [LeadController::class, 'update'])->name('update');
        Route::delete('/{lead}', [LeadController::class, 'destroy'])->name('destroy');
        Route::post('/{lead}/validate-email', [LeadController::class, 'validateEmail'])->name('validate-email');
        Route::post('/{lead}/assign-campaign', [LeadController::class, 'assignCampaign'])->name('assign-campaign');
        Route::post('/{lead}/notes', [LeadController::class, 'addNote'])->name('notes.store');
        Route::post('/{lead}/recalculate-score', [LeadController::class, 'recalculateScore'])->name('recalculate-score');
        Route::post('/{lead}/pipeline-stage', [LeadController::class, 'updatePipelineStage'])->name('pipeline-stage');
        Route::post('/{lead}/research', [AIController::class, 'researchLead'])->name('research');
        Route::post('/{lead}/personalize', [AIController::class, 'personalizeLead'])->name('personalize');
    });

    // Companies
    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('index');
        Route::get('/create', [CompanyController::class, 'create'])->name('create');
        Route::post('/', [CompanyController::class, 'store'])->name('store');
        Route::get('/{company}', [CompanyController::class, 'show'])->name('show');
        Route::get('/{company}/edit', [CompanyController::class, 'edit'])->name('edit');
        Route::put('/{company}', [CompanyController::class, 'update'])->name('update');
        Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('destroy');
    });

    // Campaigns
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::get('/create', [CampaignController::class, 'create'])->name('create');
        Route::post('/', [CampaignController::class, 'store'])->name('store');
        Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show');
        Route::get('/{campaign}/edit', [CampaignController::class, 'edit'])->name('edit');
        Route::put('/{campaign}', [CampaignController::class, 'update'])->name('update');
        Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->name('destroy');
        Route::post('/{campaign}/duplicate', [CampaignController::class, 'duplicate'])->name('duplicate');
        Route::get('/{campaign}/report', [CampaignController::class, 'report'])->name('report');
        Route::get('/{campaign}/report/export', [CampaignController::class, 'exportReport'])->name('report.export');
        Route::post('/{campaign}/send-batch', [CampaignController::class, 'sendBatch'])->name('send-batch');
        Route::post('/{campaign}/recipients', [CampaignController::class, 'addRecipients'])->name('recipients.add');
        Route::post('/{campaign}/recipients/list', [CampaignController::class, 'addRecipientsFromList'])->name('recipients.add-from-list');
        Route::post('/{campaign}/schedule', [CampaignController::class, 'schedule'])->name('schedule');
        Route::post('/{campaign}/activate', [CampaignController::class, 'activate'])->name('activate');
        Route::post('/{campaign}/pause', [CampaignController::class, 'pause'])->name('pause');
        Route::post('/{campaign}/resume', [CampaignController::class, 'resume'])->name('resume');
        Route::post('/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('cancel');
        Route::post('/{campaign}/test', [CampaignController::class, 'testSend'])->name('test');
    });

    // Email Accounts
    Route::prefix('email-accounts')->name('email-accounts.')->group(function () {
        Route::get('/', [EmailAccountController::class, 'index'])->name('index');
        Route::get('/create', [EmailAccountController::class, 'create'])->name('create');
        Route::post('/', [EmailAccountController::class, 'store'])->name('store');
        Route::post('/test-connection', [EmailAccountController::class, 'testUnsaved'])->name('test-connection');
        Route::get('/{account}', [EmailAccountController::class, 'show'])->name('show');
        Route::get('/{account}/edit', [EmailAccountController::class, 'edit'])->name('edit');
        Route::put('/{account}', [EmailAccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [EmailAccountController::class, 'destroy'])->name('destroy');
        Route::post('/{account}/test', [EmailAccountController::class, 'test'])->name('test');
        Route::post('/{account}/toggle', [EmailAccountController::class, 'toggle'])->name('toggle');
        Route::post('/{account}/set-default', [EmailAccountController::class, 'setDefault'])->name('set-default');
    });

    // Pipeline
    Route::prefix('pipeline')->name('pipeline.')->group(function () {
        Route::get('/', [PipelineController::class, 'index'])->name('index');
        Route::post('/', [PipelineController::class, 'storePipeline'])->name('store');
        Route::get('/{pipeline}', [PipelineController::class, 'show'])->name('show');
        Route::put('/{pipeline}', [PipelineController::class, 'updatePipeline'])->name('update');
        Route::delete('/{pipeline}', [PipelineController::class, 'destroyPipeline'])->name('destroy');
        Route::post('/{pipeline}/set-default', [PipelineController::class, 'setDefaultPipeline'])->name('set-default');

        // Stages
        Route::post('/{pipeline}/stages', [PipelineController::class, 'storeStage'])->name('stages.store');
        Route::put('/stages/{stage}', [PipelineController::class, 'updateStage'])->name('stages.update');
        Route::delete('/stages/{stage}', [PipelineController::class, 'destroyStage'])->name('stages.destroy');
        Route::post('/{pipeline}/stages/reorder', [PipelineController::class, 'reorderStages'])->name('stages.reorder');

        // Deals
        Route::post('/{pipeline}/deals', [PipelineController::class, 'storeDeal'])->name('deals.store');
        Route::get('/deals/{deal}', [PipelineController::class, 'showDeal'])->name('deals.show');
        Route::put('/deals/{deal}', [PipelineController::class, 'updateDeal'])->name('deals.update');
        Route::delete('/deals/{deal}', [PipelineController::class, 'destroyDeal'])->name('deals.destroy');
        Route::post('/deals/{deal}/move', [PipelineController::class, 'moveDeal'])->name('deals.move');
        Route::post('/deals/{deal}/win', [PipelineController::class, 'winDeal'])->name('deals.win');
        Route::post('/deals/{deal}/lose', [PipelineController::class, 'loseDeal'])->name('deals.lose');
        Route::post('/leads/{lead}/move-stage', [PipelineController::class, 'moveLeadStage'])->name('leads.move-stage');
    });

    // Tasks
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::get('/create', [TaskController::class, 'create'])->name('create');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/{task}', [TaskController::class, 'show'])->name('show');
        Route::get('/{task}/edit', [TaskController::class, 'edit'])->name('edit');
        Route::put('/{task}', [TaskController::class, 'update'])->name('update');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
        Route::post('/{task}/complete', [TaskController::class, 'complete'])->name('complete');
        Route::post('/{task}/start', [TaskController::class, 'start'])->name('start');
        Route::post('/bulk-complete', [TaskController::class, 'bulkComplete'])->name('bulk-complete');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/leads', [ReportController::class, 'leads'])->name('leads');
        Route::get('/campaigns', [ReportController::class, 'campaigns'])->name('campaigns');
        Route::get('/deals', [ReportController::class, 'deals'])->name('deals');
        Route::get('/team', [ReportController::class, 'team'])->name('team');
        Route::get('/email', [ReportController::class, 'email'])->name('email');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
        Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
        Route::get('/workspace', [SettingsController::class, 'workspace'])->name('workspace');
        Route::put('/workspace', [SettingsController::class, 'updateWorkspace'])->name('workspace.update');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
        Route::get('/appearance', [SettingsController::class, 'appearance'])->name('appearance');
        Route::put('/appearance', [SettingsController::class, 'updateAppearance'])->name('appearance.update');
        Route::get('/integrations', [SettingsController::class, 'integrations'])->name('integrations');
        Route::get('/ai', [AIController::class, 'settings'])->name('ai');
        Route::put('/ai', [AIController::class, 'updateSettings'])->name('ai.update');
        Route::post('/ai/test', [AIController::class, 'testConnection'])->name('ai.test');
        Route::put('/ai/scoring-rules', [AIController::class, 'updateScoringRules'])->name('ai.scoring-rules');
        Route::get('/users', [SettingsController::class, 'users'])->name('users');
        Route::post('/users/invite', [SettingsController::class, 'inviteUser'])->name('users.invite');
        Route::delete('/users/{user}', [SettingsController::class, 'removeUser'])->name('users.remove');
        Route::put('/users/{user}/role', [SettingsController::class, 'updateUserRole'])->name('users.role.update');

        // Workspace switcher
        Route::get('/workspace/switch', [AuthController::class, 'showWorkspaceSwitcher'])->name('workspace.switch');
        Route::post('/workspace/switch', [AuthController::class, 'switchWorkspace'])->name('workspace.switch.post');
    });

    // AI Assistant routes
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::post('/assistant-suggestions', [AIController::class, 'assistantSuggestions'])->name('assistant-suggestions');
        Route::post('/rewrite', [AIController::class, 'rewriteContent'])->name('rewrite');
    });

    // Notification routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
    });
});

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Public Tracking Routes (no auth required)
|--------------------------------------------------------------------------
*/

Route::get('/track/open/{message}/{token}', function ($message, $token) {
    // Track email open
    $msg = \App\Models\Message::find($message);
    if ($msg) {
        $service = app(\App\Services\EmailService::class);
        if ($service->verifyTrackingToken($msg, $token)) {
            $msg->markOpened();
        }
    }
    // Return 1x1 transparent pixel
    return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'))
        ->header('Content-Type', 'image/gif')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
})->name('tracking.open');

Route::get('/track/click/{message}/{token}', function ($message, $token, Request $request) {
    // Track link click
    $msg = \App\Models\Message::find($message);
    if ($msg) {
        $service = app(\App\Services\EmailService::class);
        if ($service->verifyTrackingToken($msg, $token)) {
            $msg->markClicked();
        }
    }
    $url = $request->query('url');
    if ($url && is_string($url)) {
        $decoded = base64_decode($url, true);

        // Only redirect to absolute HTTP(S) URLs; never to javascript: or data:.
        if ($decoded !== false && filter_var($decoded, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($decoded, PHP_URL_SCHEME));

            if (in_array($scheme, ['http', 'https'], true)) {
                return redirect()->away($decoded);
            }
        }
    }

    abort(404);
})->name('tracking.click');

Route::get('/unsubscribe/{campaign}/{lead}/{token}', function ($campaign, $lead, $token) {
    $campaign = \App\Models\Campaign::find($campaign);
    $lead = \App\Models\Lead::find($lead);

    if ($campaign && $lead) {
        $expected = hash_hmac('sha256', $lead->email, config('app.key'));
        if (hash_equals($expected, $token)) {
            $recipient = \App\Models\CampaignRecipient::where('campaign_id', $campaign->id)
                ->where('lead_id', $lead->id)
                ->first();

            if ($recipient) {
                app(\App\Services\CampaignService::class)->handleUnsubscribe($recipient);
                return view('emails.unsubscribed', ['email' => $lead->email]);
            }
        }
    }

    abort(404);
})->name('unsubscribe');

/*
|--------------------------------------------------------------------------
| Public Invitation Acceptance Routes
|--------------------------------------------------------------------------
*/
Route::get('/invite/{token}', [InvitationController::class, 'showAcceptForm'])->name('invitations.accept.form');
Route::post('/invite/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');

/*
|--------------------------------------------------------------------------
| Super Admin Control Center Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn() => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');
        Route::post('/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('status');
        Route::put('/{user}/limits', [AdminUserController::class, 'updateRoleAndLimits'])->name('limits');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
    });

    // Invitations
    Route::prefix('invitations')->name('invitations.')->group(function () {
        Route::get('/', [AdminInvitationController::class, 'index'])->name('index');
        Route::post('/', [AdminInvitationController::class, 'store'])->name('store');
        Route::post('/{invitation}/resend', [AdminInvitationController::class, 'resend'])->name('resend');
        Route::delete('/{invitation}', [AdminInvitationController::class, 'revoke'])->name('revoke');
    });

    // Email Accounts
    Route::prefix('email-accounts')->name('email-accounts.')->group(function () {
        Route::get('/', [AdminEmailAccountController::class, 'index'])->name('index');
        Route::post('/{account}/test', [AdminEmailAccountController::class, 'testConnection'])->name('test');
        Route::post('/{account}/toggle', [AdminEmailAccountController::class, 'toggleStatus'])->name('toggle');
        Route::put('/{account}/limits', [AdminEmailAccountController::class, 'updateLimits'])->name('limits');
    });

    // Campaigns
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [AdminCampaignController::class, 'index'])->name('index');
        Route::post('/{campaign}/pause', [AdminCampaignController::class, 'pause'])->name('pause');
        Route::post('/{campaign}/resume', [AdminCampaignController::class, 'resume'])->name('resume');
        Route::post('/{campaign}/cancel', [AdminCampaignController::class, 'cancel'])->name('cancel');
    });

    // Token Management
    Route::prefix('tokens')->name('tokens.')->group(function () {
        Route::get('/', [AdminTokenController::class, 'index'])->name('index');
        Route::delete('/api/{id}', [AdminTokenController::class, 'revokeApiToken'])->name('revoke-api');
    });

    // Cost Monitoring & Rates
    Route::prefix('costs')->name('costs.')->group(function () {
        Route::get('/', [AdminCostController::class, 'index'])->name('index');
        Route::put('/rates', [AdminCostController::class, 'updateRates'])->name('rates.update');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
        Route::put('/', [AdminNotificationController::class, 'update'])->name('update');
        Route::post('/test', [AdminNotificationController::class, 'sendTest'])->name('test');
    });

    // Logs
    Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');

    // Security
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/', [AdminSecurityController::class, 'index'])->name('index');
        Route::put('/', [AdminSecurityController::class, 'update'])->name('update');
    });
});