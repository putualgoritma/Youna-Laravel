<?php

namespace App\Classes;

use App\BVPairingQueue;
use App\Classes\AgentClass;
use App\Classes\CartClass;
use App\Classes\MemberClass;
use App\Classes\NotifClass;
use App\Ledger;
use App\Member;
use App\NetworkFee;
use App\Order;
use App\OrderPoint;
use App\Traits\TraitModel;

class OrderClass
{
    use TraitModel;
    public $orderMember;
    public $orderCart;
    public $orderVar;
    public $order;
    public $orderMemberClass;
    public $orderAgent;
    public $orderNetworkFee;
    public $orderMercyStatus = false;

    public function __construct($orderID)
    {
        $order = Order::where('id', $orderID)->with('products')->first();
        $cartClass = new CartClass($order->products, 1);
        $this->orderCart = $cartClass->cart;
        $this->order = $order;
        $memberClass = new MemberClass($order->customers_activation_id);
        $this->orderMemberClass = $memberClass;
        $this->orderMember = $memberClass->member;
        $this->orderVar = $cartClass->cartVar;
        $agentClass = new AgentClass($order->agents_id);
        $this->orderAgent = $agentClass->agent;
        $networkFee = NetworkFee::select('*')
            ->Where('type', '=', 'activation')
            ->Where('activation_type_id', '=', $this->orderMember->activation_type_id)
            ->first();
        $this->orderNetworkFee = $networkFee;
        $activationType = $memberClass->activationType($this->order->activation_type_id);
        if ($activationType == 'mercy') {
            $this->orderMercyStatus = true;
        }
    }

    public function orderRecieved()
    {
        if ($this->order->status == 'approved' && $this->order->status_delivery == 'delivered') {
            //set feeNetwork & ledger
            if ($this->order->bv_automaintain_amount > 0) {
                //automaintain
            } else if ($this->order->bv_reseller_amount > 0) {
                //reseller
            } else {
                //regular
                $this->orderRegularSet();
            }
            //check if activation member
            $this->memberStatusActivypeUpd();
            //update relatedStatusUpd
            $this->memberRelatedStatusUpd();
            //order notifSend
            $notifClass = new NotifClass();
            //get relate point & send notif
            $order_points_arr = OrderPoint::where('orders_id', $this->order->id)->get();
            foreach ($order_points_arr as $order_points_id) {
                $memo = $order_points_id->memo;
                $notifClass->notifSend($order_points_id->customers_id, $memo);
            }
            //send notif to agent
            $memo = 'Hallo ' . $this->orderAgent->name . ', Order ' . $this->order->code . ' sudah diterima pelanggan.';
            $notifClass->notifSend($this->order->agents_id, $memo);
            //return
            $response = array();
            $response['message'] = 'Pesanan Sudah Diterima.';
            $response['status'] = true;
            return (object) $response;
        } else {
            $response = array();
            $response['message'] = 'Update Delivery Status Gagal.';
            $response['status'] = false;
            return (object) $response;
        }
    }

    public function memberStatusActivypeUpd()
    {
        //if activate new member
        if ($this->order->type == 'activation_member') {
            $this->orderMember->status = 'active';
            $this->orderMember->save();
        }
        //if upgrade
        if ($this->order->type == 'activation_member' && $this->order->activation_type_id_old > 0) {
            $this->orderMember->activation_type_id = $this->order->activation_type_id;
            $this->orderMember->save();
        }
        $this->order->status_delivery = 'received';
        $this->order->save();
    }

    public function memberRelatedStatusUpd()
    {
        //update pivot points
        $orderpoints = OrderPoint::where('orders_id', $this->order->id)->get();
        foreach ($orderpoints as $key => $orderpoint) {
            $orderpoint_upd = OrderPoint::find($orderpoint->id);
            $orderpoint_upd->status = 'onhand';
            $orderpoint_upd->save();
        }
        //update pivot BVPairingQueue
        $pairingqueues = BVPairingQueue::where('order_id', $this->order->id)->get();
        foreach ($pairingqueues as $key => $pairingqueue) {
            $pairingqueue_upd = BVPairingQueue::find($pairingqueue->id);
            $pairingqueue_upd->status = 'active';
            $pairingqueue_upd->save();
        }
        //update pivot products details
        $ids = $this->order->productdetails()->allRelatedIds();
        foreach ($ids as $products_id) {
            $this->order->productdetails()->updateExistingPivot($products_id, ['status' => 'onhand']);
        }
        //update ledger
        $ledger = Ledger::find($this->order->ledgers_id);
        $ledger->status = 'approved';
        $ledger->save();
    }

    public function orderRegularSet()
    {
        //GET CASHBACK AGENT
        $orderAgentCashback = $this->orderAgentCashback();
        //GET PAIRING FEE
        $fee_pairing = $this->orderPairingBinFee();
        //GET REFFERAL FEE
        $orderRefferalFee = $this->orderRefferalFee();
        //GET GENERATION FEE
        $orderGenerationFee = $this->orderGenerationFee();
        //GET POINT FEE
        $orderPointFee = $this->orderPointFee();
        //GET LEVEL FEE
        $orderLevelFee = $this->orderLevelFee();

        //get netfee_amount
        $package_type = 0;
        $bvcv = (($this->orderVar->bvcv_amount) / 100) * $this->orderVar->bv_total;
        $bv_nett = $this->orderVar->bv_total - $this->orderVar->bvcv;
        if ($package_type == 0) {
            $res_netfee_amount = $orderRefferalFee->ref1_fee_point_sale + $orderRefferalFee->ref1_fee_point_upgrade + $orderRefferalFee->ref2_fee_point_sale + $orderRefferalFee->ref2_fee_point_upgrade + $fee_pairing + $orderRefferalFee->ref1_flush_out + $orderGenerationFee + $orderPointFee + $orderLevelFee;
        } else {
            $res_netfee_amount = $orderRefferalFee->ref_fee_lev;
        }

        //set account
        $acc_points = $this->account_lock_get('acc_points'); //'67'
        $acc_res_netfee = $this->account_lock_get('acc_res_netfee'); //'70'
        $acc_res_cashback = $this->account_lock_get('acc_res_cashback');
        $points_amount = $res_netfee_amount + $orderAgentCashback->cba2 + $orderAgentCashback->cbmart;
        $accounts = array($acc_points, $acc_res_netfee, $acc_res_cashback);
        $amounts = array($points_amount, $res_netfee_amount, $orderAgentCashback->cba2 + $orderAgentCashback->cbmart);
        $types = array('C', 'D', 'D');
        //order & ledger
        $ledger = Ledger::find($this->order->ledgers_id);
        //ledger entries
        for ($account = 0; $account < count($accounts); $account++) {
            if ($accounts[$account] != '') {
                $ledger->accounts()->attach($accounts[$account], ['entry_type' => $types[$account], 'amount' => $amounts[$account]]);
            }
        }
    }

    public function orderLevelFee()
    {
        $feeLevel = 0;
        if (!$this->orderMercyStatus) {
            //check if this member is left
            if ($this->orderMember->slot_y % 2 != 0) {
                //check if member's parent is right
                $parent_slot_x = $this->orderMember->slot_x - 1;
                $parent_slot_y = ceil($this->orderMember->slot_y / 2);
                if ($parent_slot_y % 2 == 0) {
                    //get perent of member's parent
                    $gparent_slot_x = $parent_slot_x - 1;
                    $gparent_slot_y = ceil($parent_slot_y / 2);
                    $gparent = Member::select('customers.code', 'customers.id')
                        ->leftjoin('activation_type', 'activation_type.id', '=', 'customers.activation_type_id')
                        ->where('activation_type.type', '=', 'business')
                        ->where('customers.status', 'active')
                        ->where('customers.slot_x', $gparent_slot_x)
                        ->where('customers.slot_y', $gparent_slot_y)
                        ->first();
                    //set fee
                    if ($gparent) {
                        $feeAmount = ($this->orderNetworkFee->levelingbv / 100) * $this->orderVar->bv_total;
                        $feeLevel += $feeAmount;
                        $this->order->points()->attach($this->orderVar->points_fee_id, ['amount' => $feeAmount, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Poin Komisi (Leveling) dari transaksi ' . $this->order->memo, 'customers_id' => $gparent->id]);

                        // echo $this->order->id . "-" . $this->orderMember->code . "-" . $gparent->id . "-" . $gparent->code . "-" .  $this->orderNetworkFee->levelingbv . "-" .  $this->orderVar->bv_total . "-" .  $feeAmount;
                        // echo "<br>";
                    }
                }
            }
        }
        return $feeLevel;
    }

    public function orderPointFee()
    {
        $feePoint = 0;
        if (!$this->orderMercyStatus) {
            //get list upline
            $uplineList = $this->get_list_upline($this->orderMember->slot_x, $this->orderMember->slot_y, $this->orderNetworkFee->deep_point);
            //get fee
            $feeAmount = (($this->orderNetworkFee->pointbv / 100) * $this->orderVar->bv_total) / $this->orderNetworkFee->deep_point;
            foreach ($uplineList as $key => $upline) {
                $memberClass = new MemberClass($upline['id']);
                $uplineRow = $memberClass->member;
                if ($uplineRow->activation_type_name == 'business' && $uplineRow->status = 'active') {
                    $feePoint += $feeAmount;
                    $this->order->points()->attach($this->orderVar->points_fee_id, ['amount' => $feeAmount, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Poin Komisi (Titik) dari transaksi ' . $this->order->memo, 'customers_id' => $upline['id']]);

                    // echo $upline['id'] . "-" . $upline['code'] . " - " . $key . " - " . $uplineRow->activation_type_name . " - " . $feeAmount;
                    // echo "<br>";
                }
            }
        }
        return $feePoint;
    }

    public function orderGenerationFee()
    {
        $feeGeneration = 0;
        if (!$this->orderMercyStatus) {
            //get list upline
            $upArr = array();
            $uplineList = $this->get_list_refferal($this->orderMember->id, $upArr, 0, 5);
            foreach ($uplineList as $key => $upline) {
                $memberClass = new MemberClass($upline);
                $uplineRow = $memberClass->member;
                if ($uplineRow->activation_type_name == 'business' && $uplineRow->status = 'active') {
                    $index = $key + 1;
                    $varDyn = 'gen' . $index;
                    //set upline generation fee
                    $genBV = ($this->orderNetworkFee->genbv / 100) * $this->orderVar->bv_total;
                    $feeAmount = ($this->orderNetworkFee->{$varDyn} / 100) * $genBV;
                    $feeGeneration += $feeAmount;

                    $this->order->points()->attach($this->orderVar->points_fee_id, ['amount' => $feeAmount, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Poin Komisi (Generasi) dari transaksi ' . $this->order->memo, 'customers_id' => $upline]);

                    // echo $upline . " - " . $key . " - " . $uplineRow->activation_type_name . " - " . $this->orderNetworkFee->{$varDyn} . " - " . $feeAmount;
                    // echo "<br>";
                }
            }
        }
        return $feeGeneration;
    }

    public function orderPairingBinFee()
    {
        //init
        $fee_out = 0;
        if (!$this->orderMercyStatus) {
            //get order detail
            $order = $this->order;
            //get member detail
            $member = $this->orderMember;
            $lev = $member->slot_x - 0;
            $slot_prev_x = $member->slot_x;
            $slot_prev_y = $member->slot_y;
            //get max level
            $member_pairing_row = NetworkFee::select('*')
                ->Where('type', '=', 'pairing')
                ->Where('activation_type_id', '=', $member->activation_type_id)
                ->first();
            $pairing_lev_max = $member_pairing_row->deep_level;
            $lev_count = 1;
            //loop upline
            for ($i = 0; $i < $lev; $i++) {
                $slot_x = $slot_prev_x - 1;
                $slot_y = ceil($slot_prev_y / 2);
                //echo $slot_x." - ".$slot_y;
                //get upline detail
                $orderLegBalance = $this->orderMemberClass->memberLegBalance(0, $slot_x, $slot_y);
                $upline = $orderLegBalance->data;

                if ($upline) {
                    //get upline queue
                    $bv_queue = $this->get_bv_queue($upline->id);
                    $bv_pairing_r = $bv_queue['r'];
                    $bv_pairing_l = $bv_queue['l'];

                    //get prev position
                    if ($slot_prev_y % 2 == 0) {
                        $bv_pairing_r += $this->orderVar->bv_total;
                        $position = 'R';
                    } else {
                        $bv_pairing_l += $this->orderVar->bv_total;
                        $position = 'L';
                    }
                    $bv_pairing = $bv_pairing_r;
                    if ($bv_pairing_l < $bv_pairing_r) {
                        $bv_pairing = $bv_pairing_l;
                    }
                    $bv_pairing = ($bv_pairing - $bv_queue['c']);
                    //compare min pairing
                    //get network fee pairing -> upline activation type
                    $nf_upline_pairing_row = NetworkFee::select('*')
                        ->Where('type', '=', 'pairing')
                        ->Where('activation_type_id', '=', $upline->activation_type_id)
                        ->first();
                    //memo
                    $memo = $upline->code . " - " . $upline->name;
                    //get min bv pairing -> upline activation type
                    $min_bv_pairing = $nf_upline_pairing_row->bv_min_pairing * $this->orderVar->bvpo;
                    // echo $this->orderVar->bvpo . '-' . $this->orderVar->bv_total . '-' . $upline->code . '-' . $bv_pairing . '-' . $min_bv_pairing . '-' . $upline->status . '-' . $upline->type;
                    // echo '</br>';
                    //check if reach lev max
                    if (($lev_count <= $pairing_lev_max) || $pairing_lev_max == 0) {
                        $pairing_sbv = $member_pairing_row->sbv;
                    } else {
                        $pairing_sbv = $member_pairing_row->sbv2;
                    }
                    //mod bv pairing
                    $bv_pairing_index = floor($bv_pairing / $min_bv_pairing);
                    $bv_pairing = $min_bv_pairing * $bv_pairing_index;
                    if (($bv_pairing >= $min_bv_pairing) && $pairing_sbv > 0 && $orderLegBalance->status) {
                        if ($upline->status == 'active' && $upline->type != "user") {
                            $upline_fee_pairing = (($pairing_sbv) / 100) * $bv_pairing;
                            $upline_amount = $upline_fee_pairing;
                            //hitung total bv_amount hari ini yang sudah di pairing di tbl pairing {bvarp_paired}
                            $reg_today = date('Y-m-d');
                            $daily_amount = $this->get_bv_daily_queue($upline->id, $reg_today);
                            $daily_amount_paired = $daily_amount + $upline_fee_pairing;
                            if ($daily_amount_paired <= $nf_upline_pairing_row->fee_day_max) {
                                $fee_out += (float) $upline_fee_pairing;
                                $this->fee_pairing_amount += (float) $upline_fee_pairing;
                                $order->points()->attach($this->orderVar->points_fee_id, ['amount' => $upline_fee_pairing, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Poin Komisi (Pairing) dari group ' . $memo, 'customers_id' => $upline->id]);
                            } else {
                                $upline_fee_pairing = $nf_upline_pairing_row->fee_day_max - $daily_amount;
                                if ($upline_fee_pairing > 0) {
                                    $fee_out += (float) $upline_fee_pairing;
                                    $this->fee_pairing_amount += (float) $upline_fee_pairing;
                                    $order->points()->attach($this->orderVar->points_fee_id, ['amount' => $upline_fee_pairing, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Poin Komisi (Pairing) dari group ' . $memo, 'customers_id' => $upline->id]);
                                }
                            }
                            //insert into tbl queue C
                            $data = ['order_id' => $order->id, 'customer_id' => $upline->id, 'bv_amount' => $bv_pairing, 'position' => 'N', 'status' => 'active', 'type' => 'C', 'pairing_amount' => $upline_fee_pairing];
                            $queue_crt = BVPairingQueue::create($data);
                        }
                    }
                    //insert into tbl queue D
                    $data = ['order_id' => $order->id, 'customer_id' => $upline->id, 'bv_amount' => $this->orderVar->bv_total, 'position' => $position, 'status' => 'active', 'type' => 'D'];
                    $queue_crt = BVPairingQueue::create($data);
                }
                $lev_count++;
                //set prev
                $slot_prev_x = $slot_x;
                $slot_prev_y = $slot_y;
            }
        }
        return $fee_out;
    }

    public function orderAgentCashback()
    {
        $networkfee1_row = $this->network_fee('CBA01');
        $cba1 = (($networkfee1_row->amount) / 100) * $this->orderVar->total;
        //chech if agent has referal
        $cbmart = 0;
        //CBA 2
        $networkfee2_row = $this->network_fee('CBA02');
        $cba2 = (($networkfee2_row->amount) / 100) * $this->orderVar->total;
        //set trf points cashback agent
        $this->order->points()->attach($this->orderVar->points_id, ['amount' => $cba2, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Penambahan Poin (Cashback Agen 02) dari ' . $this->order->memo, 'customers_id' => $this->order->agents_id]);
        //set trf points cashback agent ubb mart
        if ($cbmart > 0) {
            $this->order->points()->attach($this->orderVar->points_id, ['amount' => $cbmart, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Penambahan Poin (Komisi Agen UBB Mart) dari ' . $this->order->memo, 'customers_id' => $this->orderAgent->ref_id]);
        }
        //return
        $return_arr['cba1'] = $cba1;
        $return_arr['cbmart'] = $cbmart;
        $return_arr['cba2'] = $cba2;
        return (object) $return_arr;
    }

    public function orderRefferalFee()
    {
        //init
        $order = $this->order;
        $ref1_fee_point_sale = 0;
        $ref1_fee_point_upgrade = 0;
        $ref1_flush_out = 0;
        $ref2_fee_point_sale = 0;
        $ref2_fee_point_upgrade = 0;
        $member_get_flush_out = 0;

        if (!$this->orderMercyStatus) {
            //package
            $package_network_row = NetworkFee::select('*')
                ->Where('type', '=', 'activation')
                ->Where('activation_type_id', '=', $this->order->activation_type_id)
                ->get();
            $sbv_percen = $package_network_row[0]->sbv;
            $rsbv_g1_percen = $package_network_row[0]->rsbv_g1;
            $ref1_fee_point_sale_def = ($rsbv_g1_percen / 100) * ($sbv_percen / 100) * $this->orderVar->bv_nett;
            if (($this->orderVar->bv_nett > $this->orderVar->min_plat) && $this->order->activation_type_id < 4) {
                $ref1_fee_point_sale_def = ($rsbv_g1_percen / 100) * ($sbv_percen / 100) * $this->orderVar->min_plat;
            }
            //ref 1
            $ref1_fee_point_sale = 0;
            $ref1_fee_point_upgrade = 0;
            $ref1_flush_out = 0;
            $ref1_row = Member::find($this->orderMember->ref_bin_id);
            //ref 1 row
            if (!empty($ref1_row) && $ref1_row->ref_bin_id > 1) {
                $ref1_fee_row = NetworkFee::select('*')
                    ->Where('type', '=', 'activation')
                    ->Where('activation_type_id', '=', $ref1_row->activation_type_id)
                    ->first();
                $sbv1_percen = $ref1_fee_row->sbv;
                $rsbv_g1_percen = $ref1_fee_row->rsbv_g1;
                $ref1_fee_point_sale = ($rsbv_g1_percen / 100) * ($sbv1_percen / 100) * $this->orderVar->bv_nett;
                if (($this->orderVar->bv_nett > $this->orderVar->min_plat) && $ref1_row->activation_type_id < 4) {
                    //$ref1_fee_point_sale = ($rsbv_g1_percen / 100) * ($sbv1_percen / 100) * $this->orderVar->min_plat;
                }
                if ($ref1_fee_point_sale_def > $ref1_fee_point_sale) {
                    //$ref1_flush_out = $ref1_fee_point_sale_def - $ref1_fee_point_sale;
                }
            }

            //ref 2
            $ref2_fee_point_sale = 0;
            $ref2_fee_point_upgrade = 0;
            $member_get_flush_out = 0;
            // $ref2_row = Member::find($ref1_row->ref_id);
            // //ref 2 row
            // if (!empty($ref2_row) && $ref2_row->ref_id > 0) {
            //     $ref2_fee_row = NetworkFee::select('*')
            //         ->Where('type', '=', 'activation')
            //         ->Where('activation_type_id', '=', $ref2_row->activation_type_id)
            //         ->get();
            //     $sbv2_percen = $ref2_fee_row[0]->sbv;
            //     $rsbv_g2_percen = $ref2_fee_row[0]->rsbv_g2;
            //     $ref2_fee_point_sale = ($rsbv_g2_percen / 100) * ($sbv2_percen / 100) * $this->orderVar->bv_nett;
            //     if (($this->orderVar->bv_nett > $this->orderVar->min_plat) && $ref2_row->activation_type_id < 4) {
            //         $ref2_fee_point_sale = ($rsbv_g2_percen / 100) * ($sbv2_percen / 100) * $this->orderVar->min_plat;
            //     }
            //     $member_get_flush_out = $ref2_row->id;
            //     if ($ref1_row->activation_type_id >= $ref2_row->activation_type_id) {
            //         $member_get_flush_out = 0;
            //     }
            // }

            if ($member_get_flush_out == 0) {
                $ref1_flush_out = 0;
            }

        } else {
            $ref1_fee_point_sale = $this->orderVar->bv_nett;
        }

        //set ref1 fee
        //point sale
        if ($ref1_fee_point_sale > 0) {
            $order->points()->attach($this->orderVar->points_fee_id, ['amount' => $ref1_fee_point_sale, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Poin Komisi (Refferal) dari ' . $order->memo, 'customers_id' => $this->orderMember->ref_bin_id]);
        }
        //point upgrade
        if ($ref1_fee_point_upgrade > 0) {
            $order->points()->attach($this->orderVar->points_upg_id, ['amount' => $ref1_fee_point_upgrade, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Poin (Upgrade) Komisi (Refferal) dari ' . $order->memo, 'customers_id' => $this->orderMember->ref_bin_id]);
        }

        //set ref2 fee
        //point sale
        if ($ref2_fee_point_sale > 0) {
            $order->points()->attach($this->orderVar->points_fee_id, ['amount' => $ref2_fee_point_sale, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Poin Komisi (Refferal) dari ' . $order->memo, 'customers_id' => $ref2_id]);
        }
        //point upgrade
        if ($ref2_fee_point_upgrade > 0) {
            $order->points()->attach($this->orderVar->points_upg_id, ['amount' => $ref2_fee_point_upgrade, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Poin (Upgrade) Komisi (Refferal) dari ' . $order->memo, 'customers_id' => $ref2_id]);
        }
        //point flush out
        if ($ref1_flush_out > 0) {
            $order->points()->attach($this->orderVar->points_fee_id, ['amount' => $ref1_flush_out, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Poin Komisi (Flush Out) dari ' . $order->memo, 'customers_id' => $member_get_flush_out]);
        }

        $return_arr['ref1_fee_point_sale'] = $ref1_fee_point_sale;
        $return_arr['ref1_fee_point_upgrade'] = $ref1_fee_point_upgrade;
        $return_arr['ref1_flush_out'] = $ref1_flush_out;
        $return_arr['ref2_fee_point_sale'] = $ref2_fee_point_sale;
        $return_arr['ref2_fee_point_upgrade'] = $ref2_fee_point_upgrade;
        $return_arr['member_get_flush_out'] = $member_get_flush_out;
        return (object) $return_arr;
    }
}
