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

class MemberActivationClass
{
    use TraitModel;
    public $memberActivationRequest;
    public $memberActivationCart;
    public $memberActivationVar;
    public $memberPointBalance;
    public $agentStockStatus;
    public $memberActivationMemo;
    public $member;

    public function __construct($request)
    {
        $this->memberActivationRequest = $request;
        $cartClass = new CartClass($request->cart['item'], 0);
        $this->memberActivationCart = $cartClass->cart;
        $this->memberActivationVar = $cartClass->cartVar;
        $memberClass = new MemberClass($request->id);
        $this->member = $memberClass->member;
        $this->memberPointBalance = $memberClass->pointBalance();
        $agentClass = new AgentClass($request->agents_id);
        $this->agentStockStatus = $agentClass->stockStatus($this->memberActivationCart);
        $this->memberActivationMemo = 'Aktivasi Member ' . $memberClass->member->code . "-" . $memberClass->member->name;
    }    

    public function main()
    {
        if ($this->memberPointBalance >= $this->memberActivationVar->total && $this->member->status == 'pending') {
            //create ledger
            $ledger = $this->ledgerCreate();
            //create order
            $order = $this->orderCreate($ledger->id);
            //set order product
            $this->orderProductsSet($order);
            //member update
            $member = $this->orderMemberUpdate();
            //transfer point order
            $this->orderPointTrsf($order);
            //push notif
            $notifClass = new NotifClass();
            $memo = 'Order Masuk dari ' . $this->memberActivationMemo;
            $notifClass->notifSend($this->memberActivationRequest->agents_id, $memo);
            //return
            $response = array();
            $response['data'] = $member;
            $response['message'] = "Aktivasi Member Berhasil!";
            $response['status'] = true;
            return (object) $response;
        } else {
            //return
            $response = array();
            $response['data'] = [];
            $response['message'] = "Poin atau Stok Barang Tidak Mencukupi! atau Member sudah aktif! Poin Balance: " . $this->memberPointBalance . " Total package: " . $this->memberActivationVar->total . " Stok Agent: " . $this->agentStockStatus->stock_balance . " Member Satus: " . $this->member->status;
            $response['status'] = false;
            return (object) $response;
        }
    }

    public function ledgerCreate()
    {
        $register = date("Y-m-d");
        /* proceed ledger */
        $data = ['register' => $register, 'title' => $this->memberActivationMemo, 'memo' => $this->memberActivationMemo, 'status' => 'pending'];
        $ledger = Ledger::create($data);
        return $ledger;
    }

    public function orderCreate($ledger_id, $warehouses_id = 1)
    {
        $register = date("Y-m-d");
        $last_code = $this->get_last_code('order-agent');
        $order_code = acc_code_generate($last_code, 8, 3);
        $data = array('memo' => $this->memberActivationMemo, 'total' => $this->memberActivationVar->total, 'type' => 'activation_member', 'status' => 'pending', 'ledgers_id' => $ledger_id, 'customers_id' => $this->member->id, 'agents_id' => $this->memberActivationRequest->agents_id, 'payment_type' => 'point', 'code' => $order_code, 'register' => $register, 'bv_activation_amount' => $this->memberActivationVar->bv_nett, 'customers_activation_id' => $this->member->id, 'bv_total' => $this->memberActivationVar->bv_total, 'activation_type_id' => $this->memberActivationRequest->activationtype);
        $order = Order::create($data);
        return $order;
    }

    public function orderProductsSet($order, $warehouses_id = 1)
    {
        $cart_arr = $this->memberActivationCart;
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
                    $order->productdetails()->attach($value->id, ['quantity' => $cart_arr[$i]['qty'] * $value->pivot->quantity, 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $this->memberActivationRequest->agents_id]);
                }
            } else {
                $order->productdetails()->attach($cart_arr[$i]['id'], ['quantity' => $cart_arr[$i]['qty'], 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $this->member->id]);
                $order->productdetails()->attach($cart_arr[$i]['id'], ['quantity' => $cart_arr[$i]['qty'], 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $this->memberActivationRequest->agents_id]);
            }
        }
    }

    public function orderMemberUpdate()
    {
        /*update member */
        $member = $this->member;
        $request = $this->memberActivationRequest;
        //check if slot is null
        if ($member->slot_x < 1) {
            //get auto slot
            //get x & y referal
            $reff_user = CustomerApi::where('id', $member->ref_bin_id)->first();
            $slot_arr = array();
            $get_slot_empty = $this->get_slot_empty($reff_user->slot_x, $reff_user->slot_y, 1, $slot_arr);
            $member->slot_x = $get_slot_empty['ex'];
            $member->slot_y = $get_slot_empty['ey'];
        }
        $parent_id = $this->set_parent($member->ref_id);
        $activation_at = date('Y-m-d H:i:s');
        $member->parent_id = $parent_id;
        $member->activation_at = $activation_at;
        $member->status = 'pending';
        $member->activation_type_id = $this->memberActivationRequest->activationtype;
        $member->name = $request->name;
        if ($member->id == $member->owner_id) {
            $member->phone = $request->phone;
            $member->email = $request->email;
        }
        $member->address = $request->address;
        $member->save();
        $this->member = $member;
        return $member;
    }

    public function orderPointTrsf($order)
    {
        //set trf points from member to Usadha Bhakti (pending points)
        $order->points()->attach($this->memberActivationVar->points_id, ['amount' => $this->memberActivationVar->total, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Penambahan Poin dari (Pending Order) ' . $this->memberActivationMemo, 'customers_id' => $this->memberActivationVar->com_id]);
        $order->points()->attach($this->memberActivationVar->points_id, ['amount' => $this->memberActivationVar->total, 'type' => 'C', 'status' => 'onhand', 'memo' => 'Pemotongan Poin dari ' . $this->memberActivationMemo, 'customers_id' => $this->member->id]);
        //set trf points from member to agent (onhold)
        $order->points()->attach($this->memberActivationVar->points_id, ['amount' => $this->memberActivationVar->total, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin dari (Penjualan Paket) ' . $this->memberActivationMemo, 'customers_id' => $this->memberActivationRequest->agents_id]);
        $order->points()->attach($this->memberActivationVar->points_id, ['amount' => $this->memberActivationVar->total, 'type' => 'C', 'status' => 'onhold', 'memo' => 'Balik Poin dari ' . $this->memberActivationMemo, 'customers_id' => $this->memberActivationVar->com_id]);
    }
}
