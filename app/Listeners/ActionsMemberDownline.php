<?php

namespace App\Listeners;

use App\Events\MemberDownline;
use App\Classes\MemberDownlineClass;

class ActionsMemberDownline
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
     * @param  \App\Events\MemberDownline  $event
     * @return void
     */
    public function handle(MemberDownline $event)
    {
        $request = $event->request;

        // //$req_all=(object) $request->all();
        // $response = array();
        // $response['message'] = 'test lgi lol...';
        // $response['status'] = false;
        // $response['data'] = $request;
        // return (object) $response;

        $memberDownlineClass = new MemberDownlineClass($request);

        return $memberDownlineClass->main();
    }
}
