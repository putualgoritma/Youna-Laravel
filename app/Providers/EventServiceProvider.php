<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\OrderClosed;
use App\Listeners\ActionsOrderClosed;
use App\Events\MemberActivated;
use App\Listeners\ActionsMemberActivated;
use App\Events\OrderRecieved;
use App\Listeners\ActionsOrderRecieved;
use App\Events\MemberDownline;
use App\Listeners\ActionsMemberDownline;
use App\Events\MemberUpgrade;
use App\Listeners\ActionsMemberUpgrade;
use App\Events\MemberOrder;
use App\Listeners\ActionsMemberOrder;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        OrderClosed::class => [
            ActionsOrderClosed::class,
        ],
        MemberActivated::class => [
            ActionsMemberActivated::class,
        ],
        OrderRecieved::class => [
            ActionsOrderRecieved::class,
        ],
        MemberDownline::class => [
            ActionsMemberDownline::class,
        ],
        MemberUpgrade::class => [
            ActionsMemberUpgrade::class,
        ],
        MemberOrder::class => [
            ActionsMemberOrder::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
