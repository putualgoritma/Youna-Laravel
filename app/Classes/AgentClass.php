<?php

namespace App\Classes;

use App\CustomerApi;
use App\OrderDetails;
use App\OrderPoint;
use App\Package;
use App\Product;
use App\Traits\TraitModel;

class AgentClass
{
    use TraitModel;
    public $agent;

    public function __construct($agentID)
    {
        $this->agent = CustomerApi::where('id', $agentID)->with(['activations', 'refferal', 'provinces', 'city'])->first();
    }

    public function pointBalance()
    {
        $agent_id = $this->agent->id;
        $points_debit = OrderPoint::where('customers_id', '=', $agent_id)
            ->where('type', '=', 'D')
            ->where('status', '=', 'onhand')
            ->where('points_id', '=', 1)
            ->sum('amount');
        $points_credit = OrderPoint::where('customers_id', '=', $agent_id)
            ->where('type', '=', 'C')
            ->where('status', '=', 'onhand')
            ->where('points_id', '=', 1)
            ->sum('amount');
        $points_balance = $points_debit - $points_credit;
        return $points_balance;
    }

    public function stockStatus($cart)
    {
        $agent_id = $this->agent->id;
        $stock_status = 'true';
        $cart_arr = $cart;
        $count_cart = count($cart_arr);
        for ($i = 0; $i < $count_cart; $i++) {
            $product_type = Product::select('type')
                ->where('id', $cart_arr[$i]['id'])
                ->first();
            if ($product_type->type == 'package') {
                $package_items = Package::with('products')
                    ->where('id', $cart_arr[$i]['id'])
                    ->first();
                //loop items
                foreach ($package_items->products as $key => $value) {
                    $stock_debit = OrderDetails::where('owner', '=', $agent_id)
                        ->where('type', '=', 'D')
                        ->where('status', '=', 'onhand')
                        ->where('products_id', $value->id)
                        ->sum('quantity');
                    $stock_credit = OrderDetails::where('owner', '=', $agent_id)
                        ->where('type', '=', 'C')
                        ->where('status', '=', 'onhand')
                        ->where('products_id', $value->id)
                        ->sum('quantity');
                    $stock_balance = $stock_debit - $stock_credit;
                    if ($stock_balance < $value->pivot->quantity) {
                        $stock_status = 'false';
                    }
                }
            } else {
                $stock_debit = OrderDetails::where('owner', '=', $agent_id)
                    ->where('type', '=', 'D')
                    ->where('status', '=', 'onhand')
                    ->where('products_id', $cart_arr[$i]['id'])
                    ->sum('quantity');
                $stock_credit = OrderDetails::where('owner', '=', $agent_id)
                    ->where('type', '=', 'C')
                    ->where('status', '=', 'onhand')
                    ->where('products_id', $cart_arr[$i]['id'])
                    ->sum('quantity');
                $stock_balance = $stock_debit - $stock_credit;
                if ($stock_balance < $cart_arr[$i]['qty']) {
                    $stock_status = 'false';
                }
            }
        }
        $response = array();
        $response['stock_balance'] = $stock_balance;
        $response['status'] = $stock_status;
        return (object) $response;
    }
}
