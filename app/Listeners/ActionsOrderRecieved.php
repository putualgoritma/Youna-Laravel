<?php

namespace App\Listeners;

use App\Events\OrderRecieved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use DB;
use App\Classes\OrderClass;

class ActionsOrderRecieved
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
     * @param  \App\Events\OrderRecieved  $event
     * @return void
     */
    public function handle(OrderRecieved $event)
    {
        $orderID = $event->orderID;
        // $orderClass = new OrderClass($orderID);
        // //return
        // $response = array();
        // $response['message'] = $orderClass->order->code;
        // $response['status'] = true;
        // return (object) $response;

        $orderClass = new OrderClass($orderID);

        return $orderClass->orderRecieved();
    }
}
