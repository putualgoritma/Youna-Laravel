<?php

namespace App\Classes;

use App\Activation;
use App\Product;
use App\Traits\TraitModel;
use App\Member;
use App\NetworkFee;

class CartClass
{
    use TraitModel;
    public $cart;
    public $cartVar;

    public function __construct($products, $type)
    {
        if ($type == 1) {
            $var_arr = array();
            foreach ($products as $key => $product) {
                $var_arr['item'][$key]['id'] = $product->pivot->product_id;
                $var_arr['item'][$key]['qty'] = $product->pivot->quantity;
                $var_arr['item'][$key]['harga'] = $product->pivot->price;
            }
            $this->cart = $var_arr['item'];
        } else {
            $this->cart = $products;
        }

        $total = 0;
        $cogs_total = 0;
        $bv_total = 0;
        $cart_arr = $this->cart;
        $count_cart = count($cart_arr);
        for ($i = 0; $i < $count_cart; $i++) {
            $total += $cart_arr[$i]['qty'] * $cart_arr[$i]['harga'];
            $product = Product::find($cart_arr[$i]['id']);
            $cogs_total += $cart_arr[$i]['qty'] * $product->cogs;
            $bv_total += $cart_arr[$i]['qty'] * $product->bv;
        }

        //BVCV
        $bvcv_row = $this->network_fee('BVCV');
        //BVPO
        $bvpo_row = $this->network_fee('BVPO');
        $bvpo = $bvpo_row->amount;
        $bvcv_amount = $bvcv_row->amount;
        $bvcv = ($bvcv_amount / 100) * $bv_total;
        $bv_nett = $bv_total - $bvcv;
        $min_plat_row = Activation::select('bv_min', 'bv_max')
            ->Where('id', '=', 4)
            ->first();
        $min_plat = $min_plat_row->bv_min * $bvpo;
        $com_row = Member::select('*')
            ->where('def', '=', '1')
            ->get();
        $com_id = $com_row[0]->id;

        $cartVar['total'] = $total;
        $cartVar['cogs_total'] = $cogs_total;
        $cartVar['bv_total'] = $bv_total;
        $cartVar['bvpo'] = $bvpo;
        $cartVar['bvcv'] = $bvcv;
        $cartVar['bv_nett'] = $bv_nett;
        $cartVar['min_plat'] = $min_plat;
        $cartVar['bvcv_amount'] = $bvcv_amount;
        $cartVar['points_id'] = 1;
        $cartVar['points_upg_id'] = 2;
        $cartVar['points_fee_id'] = 4;
        $cartVar['com_id'] = $com_id;
        $this->cartVar = (object) $cartVar;
    }
}
