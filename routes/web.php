<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Leads\LeadController;
use App\Http\Controllers\Companies\CompanyController;
use App\Http\Controllers\Campaigns\CampaignController;
use App\Http\Controllers\EmailAccounts\EmailAccountController;
use App\Http\Controllers\Pipeline\PipelineController;
use App\Http\Controllers\Tasks\TaskController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Settings\SettingsController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => redirect()->route('dashboard'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'workspace.selected', 'workspace.auth'])->group(function () {
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
        Route::get('/{lead}', [LeadController::class, 'show'])->name('show');
        Route::get('/{lead}/edit', [LeadController::class, 'edit'])->name('edit');
        Route::put('/{lead}', [LeadController::class, 'update'])->name('update');
        Route::delete('/{lead}', [LeadController::class, 'destroy'])->name('destroy');
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
        Route::post('/{campaign}/recipients', [CampaignController::class, 'addRecipients'])->name('recipients.add');
        Route::post('/{campaign}/recipients/list', [CampaignController::class, 'addRecipientsFromList'])->name('recipients.add-from-list');
        Route::post('/{campaign}/schedule', [CampaignController::class, 'schedule'])->name('schedule');
        Route::post('/{campaign}/activate', [CampaignController::class, 'activate'])->name('activate');
        Route::post('/{campaign}/pause', [CampaignController::class, 'pause'])->name('pause');
        Route::post('/{campaign}/resume', [CampaignController::class, 'resume'])->name('resume');
        Route::post('/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('cancel');
        Route::post('/{campaign}/test', [CampaignController::class, 'testSend'])->name('test');
        Route::get('/{campaign}/steps/{step}/preview', [CampaignController::class, 'preview'])->name('preview');
    });

    // Email Accounts
    Route::prefix('email-accounts')->name('email-accounts.')->group(function () {
        Route::get('/', [EmailAccountController::class, 'index'])->name('index');
        Route::get('/create', [EmailAccountController::class, 'create'])->name('create');
        Route::post('/', [EmailAccountController::class, 'store'])->name('store');
        Route::get('/{emailAccount}', [EmailAccountController::class, 'show'])->name('show');
        Route::get('/{emailAccount}/edit', [EmailAccountController::class, 'edit'])->name('edit');
        Route::put('/{emailAccount}', [EmailAccountController::class, 'update'])->name('update');
        Route::delete('/{emailAccount}', [EmailAccountController::class, 'destroy'])->name('destroy');
        Route::post('/{emailAccount}/test', [EmailAccountController::class, 'test'])->name('test');
        Route::post('/{emailAccount}/set-default', [EmailAccountController::class, 'setDefault'])->name('set-default');
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
        Route::get('/users', [SettingsController::class, 'users'])->name('users');
        Route::post('/users/invite', [SettingsController::class, 'inviteUser'])->name('users.invite');
        Route::delete('/users/{user}', [SettingsController::class, 'removeUser'])->name('users.remove');
        Route::put('/users/{user}/role', [SettingsController::class, 'updateUserRole'])->name('users.role.update');

        // Workspace switcher
        Route::get('/workspace/switch', [AuthController::class, 'showWorkspaceSwitcher'])->name('workspace.switch');
        Route::post('/workspace/switch', [AuthController::class, 'switchWorkspace'])->name('workspace.switch.post');
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
    if ($msg && \App\Services\EmailService::class) {
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
    if ($url) {
        return redirect(base64_decode($url));
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