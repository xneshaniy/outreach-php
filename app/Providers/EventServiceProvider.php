<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use App\Events\CampaignSent;
use App\Events\EmailOpened;
use App\Events\EmailClicked;
use App\Events\EmailReplied;
use App\Events\EmailBounced;
use App\Listeners\LogLeadActivity;
use App\Listeners\LogCampaignActivity;
use App\Listeners\UpdateLeadLastActivity;
use App\Listeners\HandleEmailReply;
use App\Listeners\HandleEmailBounce;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        LeadCreated::class => [
            LogLeadActivity::class,
            UpdateLeadLastActivity::class,
        ],
        LeadUpdated::class => [
            LogLeadActivity::class,
            UpdateLeadLastActivity::class,
        ],
        CampaignSent::class => [
            LogCampaignActivity::class,
        ],
        EmailOpened::class => [
            LogCampaignActivity::class,
        ],
        EmailClicked::class => [
            LogCampaignActivity::class,
        ],
        EmailReplied::class => [
            LogCampaignActivity::class,
            HandleEmailReply::class,
        ],
        EmailBounced::class => [
            LogCampaignActivity::class,
            HandleEmailBounce::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}