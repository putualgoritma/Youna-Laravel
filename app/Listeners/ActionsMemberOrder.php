<?php

namespace App\Listeners;

use App\Classes\MemberOrderClass;
use App\Events\MemberOrder;

class ActionsMemberOrder
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\MemberOrder  $event
     * @return void
     */
    public function handle(MemberOrder $event)
    {
        $request = $event->request;

        $memberOrderClass = new MemberOrderClass($request);

        return $memberOrderClass->main();
    }
}
