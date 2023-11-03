<?php

namespace App\Listeners;

use App\Classes\MemberActivationClass;
use App\Events\MemberActivated;

class ActionsMemberActivated
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
     * @param  \App\Events\MemberActivated  $event
     * @return void
     */
    public function handle(MemberActivated $event)
    {
        $request = $event->request;

        $memberActivationClass = new MemberActivationClass($request);

        return $memberActivationClass->main();
    }
}
