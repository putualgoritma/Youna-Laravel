<?php

namespace App\Listeners;

use App\Events\OrderClosed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use DB;
use App\Classes\OrderClass;

class ActionsOrderClosed
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
     * @param  \App\Events\OrderClosed  $event
     * @return void
     */
    public function handle(OrderClosed $event)
    {
        $order = $event->order;

        $orderClass = new OrderClass($order->id);

        $out_return = array();
        $out_return['data'] = $orderClass->orderVar;
        $out_return['status'] = true;
        return (object) $out_return;

        //init class Order
        // $orderClass = new Order($order);
        // $orderClass->memberStatusActivypeUpd();
        // $orderClass->relatedStatusUpd();
        // $orderClass->ledgerStatusUpd();
        // $orderClass->notifSend();
    }
}
