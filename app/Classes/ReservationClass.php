<?php

namespace App\Classes;

use App\Classes\NotifClass;
use App\Order;
use App\Traits\TraitModel;

class ReservationClass
{
    use TraitModel;
    public $reservationRequest;

    public function __construct($request)
    {
        $this->reservationRequest = $request;
    }

    public function main()
    {
        //create order
        $order = $this->orderCreate(0);
        //set order availability
        $this->orderReservationSet($order);
        //push notif
        $notifClass = new NotifClass();
        $memo = 'Reservasi Masuk dari ' . $order->memo;
        //$notifClass->notifSend($this->reservationRequest->agents_id, $memo);
        //return
        $response = array();
        $response['data'] = $order;
        $response['message'] = "Reservasi Berhasil!";
        $response['status'] = true;
        return (object) $response;
    }

    public function orderCreate($ledger_id)
    {
        $register = date("Y-m-d");
        $last_code = $this->get_last_code('order-reservation');
        $order_code = acc_code_generate($last_code, 8, 3);
        $data = array('memo' => $this->reservationRequest->memo, 'total' => 0, 'type' => 'reservation', 'status' => 'pending', 'ledgers_id' => $ledger_id, 'customers_id' => $this->reservationRequest->customers_id, 'agents_id' => 0, 'payment_type' => 'point', 'code' => $order_code, 'register' => $register, 'bv_activation_amount' => 0, 'customers_activation_id' => $this->reservationRequest->customers_id, 'bv_total' => 0, 'activation_type_id' => 0);
        $order = Order::create($data);
        return $order;
    }

    public function orderReservationSet($order)
    {
        $cart_arr = $this->reservationRequest->cart['item'];
        $count_cart = count($cart_arr);
        for ($i = 0; $i < $count_cart; $i++) {
            //set order availabilities
            $order->availabilities()->attach($cart_arr[$i]['id'], ['date' => $cart_arr[$i]['date']]);
        }
    }
}
