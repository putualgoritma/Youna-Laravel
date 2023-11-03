<?php

namespace App\Listeners;

use App\Events\MemberUpgrade;
use App\Classes\MemberUpgradeClass;

class ActionsMemberUpgrade
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
     * @param  \App\Events\MemberUpgrade  $event
     * @return void
     */
    public function handle(MemberUpgrade $event)
    {
        $request = $event->request;

        // //$req_all=(object) $request->all();
        // $response = array();
        // $response['message'] = 'test lgi lol...';
        // $response['status'] = false;
        // $response['data'] = $request;
        // return (object) $response;

        $memberUpgradeClass = new MemberUpgradeClass($request);

        return $memberUpgradeClass->main();
    }
}
