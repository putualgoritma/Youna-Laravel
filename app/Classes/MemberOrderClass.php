<?php

namespace App\Classes;

use App\Classes\AgentClass;
use App\Classes\CartClass;
use App\Classes\MemberClass;
use App\CustomerApi;
use App\Ledger;
use App\Order;
use App\Package;
use App\Product;
use App\Traits\TraitModel;
use App\Classes\NotifClass;

class MemberOrderClass
{
    use TraitModel;
    public $memberOrderRequest;
    public $memberOrderCart;
    public $memberOrderVar;
    public $memberPointBalance;
    public $agentStockStatus;
    public $memberOrderMemo;
    public $member;

    public function __construct($request)
    {
        $this->memberOrderRequest = $request;
        $cartClass = new CartClass($request->cart['item'], 0);
        $this->memberOrderCart = $cartClass->cart;
        $this->memberOrderVar = $cartClass->cartVar;
        $memberClass = new MemberClass($request->id);
        $this->member = $memberClass->member;
        $this->memberPointBalance = $memberClass->pointBalance();
        $agentClass = new AgentClass($request->agents_id);
        $this->agentStockStatus = $agentClass->stockStatus($this->memberOrderCart);
        $this->memberOrderMemo = 'Transaksi Marketplace Member ' . $memberClass->member->code . "-" . $memberClass->member->name;
    }    

    public function main()
    {
        if (($this->memberPointBalance >= $this->memberOrderVar->total || $this->memberOrderRequest->tokensale != '') && $this->agentStockStatus->status == 'true') {
            //create ledger
            $ledger = $this->ledgerCreate();
            //create order
            $order = $this->orderCreate($ledger->id);
            //set order product
            $this->orderProductsSet($order);
            //transfer point order
            $this->orderPointTrsf($order);
            //push notif
            $notifClass = new NotifClass();
            $memo = 'Order Masuk dari ' . $this->memberOrderMemo;
            $notifClass->notifSend($this->memberOrderRequest->agents_id, $memo);
            //return
            $response = array();
            $response['data'] = $member;
            $response['message'] = "Repeat Order Member Berhasil!";
            $response['status'] = true;
            return (object) $response;
        } else {
            //return
            $response = array();
            $response['data'] = [];
            $response['message'] = "Poin atau Stok Barang Tidak Mencukupi! atau Member sudah aktif! Poin Balance: " . $this->memberPointBalance . " Total package: " . $this->memberOrderVar->total . " Stok Agent: " . $this->agentStockStatus->stock_balance . " Member Satus: " . $this->member->status;
            $response['status'] = false;
            return (object) $response;
        }
    }

    public function ledgerCreate()
    {
        $register = date("Y-m-d");
        /* proceed ledger */
        $data = ['register' => $register, 'title' => $this->memberOrderMemo, 'memo' => $this->memberOrderMemo, 'status' => 'pending'];
        $ledger = Ledger::create($data);
        return $ledger;
    }

    public function orderCreate($ledger_id, $warehouses_id = 1)
    {
        $register = date("Y-m-d");
        $last_code = $this->get_last_code('order-agent');
        $order_code = acc_code_generate($last_code, 8, 3);
        //check if token
        $payment_type = 'point';
        $status_delivery = 'received';
        $status_order = 'pending';
        if ($this->memberOrderRequest->tokensale != '') {
            $payment_type = 'token';
            $status_delivery = 'delivered';
            $status_order = 'approved';
        }
        $data = array('memo' => $this->memberOrderMemo, 'total' => $this->memberOrderVar->total, 'type' => 'activation_member', 'status' => $status_order, 'ledgers_id' => $ledger_id, 'customers_id' => $this->sponsor->id, 'agents_id' => $this->memberOrderRequest->agents_id, 'payment_type' => $payment_type, 'code' => $order_code, 'register' => $register, 'bv_activation_amount' => $this->memberOrderVar->bv_nett, 'customers_activation_id' => $this->member->id, 'bv_total' => $this->memberOrderVar->bv_total, 'activation_type_id' => $this->memberOrderRequest->activationtype, 'status_delivery' => $status_delivery, 'bv_ro_amount' => $this->memberOrderVar->bv_total);
        $order = Order::create($data);
        return $order;
    }

    public function orderProductsSet($order, $warehouses_id = 1)
    {
        $cart_arr = $this->memberOrderCart;
        $count_cart = count($cart_arr);
        for ($i = 0; $i < $count_cart; $i++) {
            //set order products
            $order->products()->attach($cart_arr[$i]['id'], ['quantity' => $cart_arr[$i]['qty'], 'price' => $cart_arr[$i]['harga']]);
            //set order order details (inventory stock)
            //check if package
            $products_type = Product::select('type')
                ->where('id', $cart_arr[$i]['id'])
                ->get();
            $products_type = json_decode($products_type, false);
            if ($products_type[0]->type == 'package') {
                $package_items = Package::with('products')
                    ->where('id', $cart_arr[$i]['id'])
                    ->get();
                $package_items = json_decode($package_items, false);
                $package_items = $package_items[0]->products;
                //loop items
                foreach ($package_items as $key => $value) {
                    $order->productdetails()->attach($value->id, ['quantity' => $cart_arr[$i]['qty'] * $value->pivot->quantity, 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $this->member->id]);
                    $order->productdetails()->attach($value->id, ['quantity' => $cart_arr[$i]['qty'] * $value->pivot->quantity, 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $this->memberOrderRequest->agents_id]);
                }
            } else {
                $order->productdetails()->attach($cart_arr[$i]['id'], ['quantity' => $cart_arr[$i]['qty'], 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $this->member->id]);
                $order->productdetails()->attach($cart_arr[$i]['id'], ['quantity' => $cart_arr[$i]['qty'], 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $this->memberOrderRequest->agents_id]);
            }
        }
    }    

    public function orderPointTrsf($order)
    {
        //set trf points from member to Usadha Bhakti (pending points)
        $order->points()->attach($this->memberOrderVar->points_id, ['amount' => $this->memberOrderVar->total, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Penambahan Poin dari (Pending Order) ' . $this->memberOrderMemo, 'customers_id' => $this->memberOrderVar->com_id]);
        $order->points()->attach($this->memberOrderVar->points_id, ['amount' => $this->memberOrderVar->total, 'type' => 'C', 'status' => 'onhand', 'memo' => 'Pemotongan Poin dari ' . $this->memberOrderMemo, 'customers_id' => $this->member->id]);
        //set trf points from member to agent (onhold)
        $order->points()->attach($this->memberOrderVar->points_id, ['amount' => $this->memberOrderVar->total, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin dari (Penjualan Paket) ' . $this->memberOrderMemo, 'customers_id' => $this->memberOrderRequest->agents_id]);
        $order->points()->attach($this->memberOrderVar->points_id, ['amount' => $this->memberOrderVar->total, 'type' => 'C', 'status' => 'onhold', 'memo' => 'Balik Poin dari ' . $this->memberOrderMemo, 'customers_id' => $this->memberOrderVar->com_id]);
    }
}
