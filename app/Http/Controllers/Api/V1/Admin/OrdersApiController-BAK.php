<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Activation;
use App\BVPairingQueue;
use App\Customer;
use App\Http\Controllers\Controller;
use App\Ledger;
use App\LogNotif;
use App\Mail\OrderEmail;
use App\Member;
use App\NetworkFee;
use App\Order;
use App\OrderDetails;
use App\OrderPoint;
use App\Package;
use App\PairingInfo;
use App\Point;
use App\Product;
use App\Tokensale;
use App\Traits\TraitModel;
use Berkayk\OneSignal\OneSignalClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use OneSignal;
use Symfony\Component\HttpFoundation\Response;
use App\Events\OrderRecieved;

class OrdersApiController extends Controller
{
    use TraitModel;
    private $onesignal_client;

    public function __construct()
    {
        $this->onesignal_client = new OneSignalClient(env('ONESIGNAL_APP_ID_MEMBER'), env('ONESIGNAL_REST_API_KEY_MEMBER'), '');
    }

    public function transferStock(Request $request)
    {
        $data = json_encode($request->all());
        $package = json_decode($data, false);
        $cart_arr = $package->cart;
        $count_cart = count($cart_arr);

        /* set agent from & to */
        $agent = Customer::find($request->agent_from_id);
        $agent_id = $request->agent_from_id;
        $agent_to = Customer::find($request->agent_to_id);
        $agent_to_id = $request->agent_to_id;

        $warehouses_id = 1;
        //set order
        $last_code = $this->get_last_code('stock_trsf');
        $order_code = acc_code_generate($last_code, 8, 3);
        $register = date("Y-m-d");
        $memo = 'Transfer Stok Agen dari ' . $agent->code . "-" . $agent->name . ' ke ' . $agent_to->code . "-" . $agent_to->name;
        $data = array('memo' => $memo, 'total' => 0, 'type' => 'stock_trsf', 'status' => 'pending', 'ledgers_id' => 0, 'customers_id' => $agent_id, 'payment_type' => 'point', 'code' => $order_code, 'register' => $register);
        $order = Order::create($data);
        for ($i = 0; $i < $count_cart; $i++) {
            //set order products
            $order->products()->attach($cart_arr[$i]->id, ['quantity' => $cart_arr[$i]->qty, 'price' => $cart_arr[$i]->price]);
            //set order order details (inventory stock)

            $order->productdetails()->attach($cart_arr[$i]->id, ['quantity' => $cart_arr[$i]->qty, 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $agent_id]);
            $order->productdetails()->attach($cart_arr[$i]->id, ['quantity' => $cart_arr[$i]->qty, 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $agent_to_id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer Stok Berhasil!',
        ]);
    }

    public function pointsTotal(Request $request)
    {
        $total = 0;
        $status_fld = '';
        if (isset($request->status_fld)) {
            $status_fld = $request->status_fld;
        }
        $points = Point::FilterWithdraw($status_fld)->get();
        foreach ($points as $key => $point_row) {
            $total += $this->points_balance_selected($request->customer_id, $point_row->id);
        }

        return $total;
    }

    public function points(Request $request)
    {
        $status_fld = '';
        if (isset($request->status_fld)) {
            $status_fld = $request->status_fld;
        }
        if (isset($request->customer_id)) {
            $points = Point::FilterWithdraw($status_fld)->get();
            foreach ($points as $key => $point_row) {
                $points[$key]['balance'] = $this->points_balance_selected($request->customer_id, $point_row->id);
            }
        } else {
            $points = Point::FilterWithdraw($status_fld)->get();
        }

        return $points;
    }

    public function test($id)
    {
        $orderdetails = OrderDetails::select('products_id', 'quantity')->where('orders_id', $id)->where('type', 'D')->get();
        $order = Order::find($id);
        //get stock agent, loop package
        $stock_status = 'true';
        foreach ($orderdetails as $orderdetail) {
            $stock_debit = OrderDetails::where('owner', '=', $order->agents_id)
                ->where('type', '=', 'D')
                ->where('status', '=', 'onhand')
                ->where('products_id', $orderdetail->products_id)
                ->sum('quantity');
            $stock_credit = OrderDetails::where('owner', '=', $order->agents_id)
                ->where('type', '=', 'C')
                ->where('status', '=', 'onhand')
                ->where('products_id', $orderdetail->products_id)
                ->sum('quantity');
            $stock_balance = $stock_debit - $stock_credit;
            if ($stock_balance < $orderdetail->quantity) {
                $stock_status = 'false';
            }
        }
        return $stock_status;
        // //get relate point
        // //$return_out="";
        // $order_points_arr = OrderPoint::where('orders_id', $order->id)->get();
        // foreach ($order_points_arr as $order_points_id) {
        //     //push notif
        //     $user_os = Customer::find($order_points_id->customers_id);
        //     $id_onesignal = $user_os->id_onesignal;
        //     if (!empty($id_onesignal)) {
        //         //$return_out .="-".$id_onesignal;
        //         $memo = $order_points_id->memo;
        //         $register = date("Y-m-d");
        //         //store to logs_notif
        //         $data = ['register' => $register, 'customers_id' => $order_points_id->customers_id, 'memo' => $memo];
        //         $logs = LogNotif::create($data);
        //         //push notif
        //         if ($user_os->type == 'agent') {
        //             if (!empty($id_onesignal)) {
        //                 OneSignal::sendNotificationToUser(
        //                     $memo,
        //                     $id_onesignal,
        //                     $url = null,
        //                     $data = null,
        //                     $buttons = null,
        //                     $schedule = null
        //                 );}
        //         } else {
        //             if (!empty($id_onesignal)) {
        //                 $this->onesignal_client->sendNotificationToUser(
        //                     $memo,
        //                     $id_onesignal,
        //                     $url = null,
        //                     $data = null,
        //                     $buttons = null,
        //                     $schedule = null
        //                 );}
        //         }
        //     }
        // }
        //return $return_out;
    }

    public function orderCancel($id)
    {
        /*update order status */
        $order = Order::find($id);
        if ($order->status == 'pending') {
            $order->status = 'closed';
            $order->status_delivery = 'pending';
            $order->save();
            //if order type activation_member close member status
            if ($order->type == 'activation_member' && $order->activation_type_id_old == 0) {
                $member = Customer::find($order->customers_activation_id);
                $member->status = 'pending';
                $member->save();
            }
            //if upgrade
            if ($order->type == 'activation_member' && $order->activation_type_id_old > 0) {
                $member = Customer::find($order->customers_activation_id);
                $member->activation_type_id = $order->activation_type_id_old;
                $member->save();
            }
            //update pivot points
            $orderpoints = OrderPoint::where('orders_id', $id)->get();
            foreach ($orderpoints as $key => $orderpoint) {
                $orderpoint_upd = OrderPoint::find($orderpoint->id);
                $orderpoint_upd->status = 'onhold';
                $orderpoint_upd->save();
            }
            //update pivot BVPairingQueue
            $pairingqueues = BVPairingQueue::where('order_id', $id)->get();
            foreach ($pairingqueues as $key => $pairingqueue) {
                $pairingqueue_upd = BVPairingQueue::find($pairingqueue->id);
                $pairingqueue_upd->status = 'close';
                $pairingqueue_upd->save();
            }
            //update pivot products details
            $ids = $order->productdetails()->allRelatedIds();
            foreach ($ids as $products_id) {
                $order->productdetails()->updateExistingPivot($products_id, ['status' => 'onhold']);
            }
            //update ledger
            $ledger = Ledger::find($order->ledgers_id);
            $ledger->status = 'closed';
            $ledger->save();
            //push notif to member
            $user = Customer::find($order->customers_id);
            //onesignal
            $id_onesignal = $user->id_onesignal;
            $memo = 'Hallo ' . $user->name . ', Order ' . $order->code . ' telah dibatalkan.';
            $register = date("Y-m-d");
            //store to logs_notif
            $data = ['register' => $register, 'customers_id' => $order->customers_id, 'memo' => $memo];
            $logs = LogNotif::create($data);
            //push notif
            if (!empty($id_onesignal)) {
                $this->onesignal_client->sendNotificationToUser(
                    $memo,
                    $id_onesignal,
                    $url = null,
                    $data = null,
                    $buttons = null,
                    $schedule = null
                );}
            //push notif to agent
            $user_os = Customer::find($order->agents_id);
            $id_onesignal = $user_os->id_onesignal;
            $memo = 'Hallo ' . $user_os->name . ', Order ' . $order->code . ' telah dibatalkan.';
            $register = date("Y-m-d");
            //store to logs_notif
            $data = ['register' => $register, 'customers_id' => $order->agents_id, 'memo' => $memo];
            $logs = LogNotif::create($data);
            //push notif
            OneSignal::sendNotificationToUser(
                $memo,
                $id_onesignal,
                $url = null,
                $data = null,
                $buttons = null,
                $schedule = null
            );
            //response
            $message = 'Pesanan Sudah Dibatalkan.';
            $status = true;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        } else {
            $message = 'Pembatalan Gagal.';
            $status = false;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        }
    }

    public function orderAgentProcess($id)
    {
        /*update order status */
        $order = Order::find($id);
        $orderdetails = OrderDetails::select('products_id', 'quantity')->where('orders_id', $id)->where('type', 'D')->get();
        //get stock agent, loop package
        $stock_status = 'true';
        foreach ($orderdetails as $orderdetail) {
            $stock_debit = OrderDetails::where('owner', '=', $order->agents_id)
                ->where('type', '=', 'D')
                ->where('status', '=', 'onhand')
                ->where('products_id', $orderdetail->products_id)
                ->sum('quantity');
            $stock_credit = OrderDetails::where('owner', '=', $order->agents_id)
                ->where('type', '=', 'C')
                ->where('status', '=', 'onhand')
                ->where('products_id', $orderdetail->products_id)
                ->sum('quantity');
            $stock_balance = $stock_debit - $stock_credit;
            if ($stock_balance < $orderdetail->quantity) {
                $stock_status = 'false';
            }
        }
        if ($order->status == 'pending' && $stock_status == 'true') {
            $order->status = 'approved';
            $order->status_delivery = 'process';
            $order->save();
            //push notif
            $user = Customer::find($order->customers_id);
            $id_onesignal = $user->id_onesignal;
            $memo = 'Hallo ' . $user->name . ', Order ' . $order->code . ' sudah diproses.';
            $register = date("Y-m-d");
            //store to logs_notif
            $data = ['register' => $register, 'customers_id' => $order->customers_id, 'memo' => $memo];
            $logs = LogNotif::create($data);
            //push notif
            if (!empty($id_onesignal)) {
                $this->onesignal_client->sendNotificationToUser(
                    $memo,
                    $id_onesignal,
                    $url = null,
                    $data = null,
                    $buttons = null,
                    $schedule = null
                );}
            $message = 'Proses Order Berhasil.';
            $status = true;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        } else {
            $message = 'Proses Order Gagal, Stok Agen Tidak Mencukupi.';
            $status = false;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ], 404);
        }
    }

    public function deliveryAgentUpdate($id)
    {
        /*update order status */
        $order = Order::find($id);
        if ($order->status == 'approved' && $order->status_delivery == 'process') {
            $order->status_delivery = 'delivered';
            $order->save();
            //push notif
            $user = Customer::find($order->customers_id);
            $id_onesignal = $user->id_onesignal;
            $memo = 'Hallo ' . $user->name . ', Order ' . $order->code . ' sudah dikirimkan.';
            $register = date("Y-m-d");
            //store to logs_notif
            $data = ['register' => $register, 'customers_id' => $order->customers_id, 'memo' => $memo];
            $logs = LogNotif::create($data);
            //push notif
            if (!empty($id_onesignal)) {
                $this->onesignal_client->sendNotificationToUser(
                    $memo,
                    $id_onesignal,
                    $url = null,
                    $data = null,
                    $buttons = null,
                    $schedule = null
                );}
            $message = 'Update Delivery Status Berhasil.';
            $status = true;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        } else {
            $message = 'Update Delivery Status Gagal.';
            $status = false;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        }
    }

    public function deliveryMemberUpdate($id)
    {
        $orderRecieved = event(new OrderRecieved($id));
        if ($orderRecieved[0]->status == 1) {
            return response()->json([
                'status' => true,
                'message' => $orderRecieved[0]->message,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => $orderRecieved[0]->message,
            ], 401);
        }
    }

    public function history($id, Request $request)
    {
        if (isset($request->page)) {
            $orders = Order::with('customers')
                ->with('products')
                ->with('productdetails')
                ->with('agents')
                ->where('customers_id', '=', $id)
                ->where(function ($query) {
                    $query->where('type', 'agent_sale')
                        ->orWhere('type', 'activation_member')
                        ->orWhere('type', 'sale');
                })
                ->orderBy('id', 'DESC')
                ->paginate(10, ['*'], 'page', $request->page);
        } else {
            $orders = Order::with('customers')
                ->with('products')
                ->with('productdetails')
                ->with('agents')
                ->where('customers_id', '=', $id)
                ->where(function ($query) {
                    $query->where('type', 'agent_sale')
                        ->orWhere('type', 'activation_member')
                        ->orWhere('type', 'sale');
                })
                ->orderBy('id', 'DESC')
                ->get();
        }

        //Check if history found or not.
        if (is_null($orders)) {
            $message = 'History Order not found.';
            $status = false;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        } else {
            $message = 'History retrieved successfully.';
            $status = true;
            return response()->json([
                'status' => $status,
                'message' => $message,
                'data' => $orders,
            ]);
        }
    }

    public function historyAgent($id, Request $request)
    {
        // return $request;
        if (isset($request->page)) {
            $orders = Order::with('customers')
                ->with('products')
                ->with('productdetails')
                ->where('agents_id', $id)
                ->where(function ($query) {
                    $query->where('type', 'agent_sale')
                        ->orWhere('type', 'activation_member');
                })
                ->orderBy('id', 'DESC')
                ->paginate(10, ['*'], 'page', $request->page);
        } else {
            $orders = Order::with('customers')
                ->with('products')
                ->with('productdetails')
                ->where('agents_id', $id)
                ->where(function ($query) {
                    $query->where('type', 'agent_sale')
                        ->orWhere('type', 'activation_member');
                })
                ->orderBy('id', 'DESC')
                ->get();
        }

        //Check if history found or not.
        if (is_null($orders)) {
            $message = 'History Order not found.';
            $status = false;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        } else {
            $message = 'History retrieved successfully.';
            $status = true;
            return response()->json([
                'status' => $status,
                'message' => $message,
                'data' => $orders,
            ]);
        }
    }

    public function storeAgent(Request $request)
    {
        //get total
        $total = 0;
        $cogs_total = 0;
        $bv_total = 0;
        $data = json_encode($request->all());
        $package = json_decode($data, false);
        $cart_arr = $package->cart;
        $count_cart = count($cart_arr);
        $profit_total = 0;
        for ($i = 0; $i < $count_cart; $i++) {
            $total += $cart_arr[$i]->quantity * $cart_arr[$i]->price;
            $product = Product::find($cart_arr[$i]->products_id);
            $cogs_total += $cart_arr[$i]->quantity * $product->cogs;
            $bv_total += $cart_arr[$i]->quantity * $product->bv;
            $profit_total += $cart_arr[$i]->quantity * $product->profit;
        }

        /* set customer & point balance */
        $customer = Customer::find($request->customers_id);
        //get point customer
        $points_id = 1;
        $points_debit = OrderPoint::where('customers_id', '=', $request->customers_id)
            ->where('type', '=', 'D')
            ->where('points_id', '=', 1)
            ->where('status', '=', 'onhand')
            ->sum('amount');
        $points_credit = OrderPoint::where('customers_id', '=', $request->customers_id)
            ->where('type', '=', 'C')
            ->where('points_id', '=', 1)
            ->where('status', '=', 'onhand')
            ->sum('amount');
        $points_balance = $points_debit - $points_credit;

        //check if payment type is exist
        $payment_type = 'point';
        if (isset($request->payment_type)) {
            $payment_type = $request->payment_type;
        }

        //compare total to points_balance
        if ($points_balance >= $total || $payment_type == 'bank') {
            /* proceed ledger */
            $memo = 'Transaksi Marketplace Agen ' . $customer->code . "-" . $customer->name;
            $data = ['register' => $request->input('register'), 'title' => $memo, 'memo' => $memo, 'status' => 'pending'];
            $ledger = Ledger::create($data);
            $ledger_id = $ledger->id;
            //set ledger entry arr
            $acc_inv_stock = $this->account_lock_get('acc_inv_stock'); //'20'
            $acc_sale = $this->account_lock_get('acc_sale'); //'44'
            $acc_exp_cogs = $this->account_lock_get('acc_exp_cogs'); //'45'
            $acc_points = $this->account_lock_get('acc_points'); //utang poin '67'
            $total_pay = $total;
            $accounts = array($acc_inv_stock, $acc_exp_cogs, $acc_sale);
            $amounts = array($cogs_total, $cogs_total, $total);
            $types = array('C', 'D', 'C');
            //if agent get cashback
            $customer_row = Customer::select('*')
                ->Where('id', '=', $request->input('customers_id'))
                ->get();
            if ($customer_row[0]->type == 'agent') {
                if ($customer_row[0]->agent_type != 'reseller') {
                    //get cashback 01
                    $acc_disc = $this->account_lock_get('acc_disc'); //68
                    $acc_res_netfee = $this->account_lock_get('acc_res_netfee'); //70
                    $acc_res_cashback = $this->account_lock_get('acc_res_cashback');
                    $acc_res_ref = $this->account_lock_get('acc_res_ref');

                    //CBA 1
                    $networkfee_row = NetworkFee::select('*')
                        ->Where('code', '=', 'CBA01')
                        ->get();
                    //CBA 2
                    $networkfee_row2 = NetworkFee::select('*')
                        ->Where('code', '=', 'CBA02')
                        ->get();
                    //max referal
                    $ref_fee_row = NetworkFee::select('*')
                        ->Where('type', '=', 'activation')
                        ->Where('activation_type_id', '=', '4')
                        ->get();
                    //BVCV
                    $bvcv_row = NetworkFee::select('*')
                        ->Where('code', '=', 'BVCV')
                        ->get();
                    $cba1 = (($networkfee_row[0]->amount) / 100) * $total;

                    $cbmart = 0;
                    $incmart = 0;
                    $agent_row = Member::find($request->customers_id);
                    if ($agent_row->agent_type != 'non' && $agent_row->agent_type != 'main') {
                        //CB Mart
                        $cbmart_row = NetworkFee::select('*')
                            ->Where('code', '=', 'CBMART')
                            ->get();
                        $cba1 = (($networkfee_row[0]->amount - $cbmart_row[0]->amount) / 100) * $total;
                        if ($agent_row->ref_id > 0) {
                            $cbmart = (($cbmart_row[0]->amount) / 100) * $total;
                        } else {
                            $incmart = (($cbmart_row[0]->amount) / 100) * $total;
                        }
                    }

                    $cba2 = (($networkfee_row2[0]->amount) / 100) * $total;
                    $bvcv = (($bvcv_row[0]->amount) / 100) * $bv_total;
                    $bv_nett = $bv_total - $bvcv;
                    //$res_ref_amount = (($ref_fee_row[0]->sbv) / 100) * $bv_nett;
                    $res_netfee_amount = $bv_nett;
                    $round_profit = $total - $cogs_total - $bv_total;
                    $profit = $bvcv + $round_profit; // (set to ledger profit)
                    $amount_disc = $cba1 + $cba2 + $res_netfee_amount + $cbmart; // (potongan penjualan)
                    $amount_res_cashback = $amount_disc - $cba1 - $cbmart; //(reserve/cadangan)
                    $total_pay = $total - $cba1 - $cbmart;
                    //$acc_points = '67';
                    //push array jurnal
                    array_push($accounts, $acc_disc, $acc_res_netfee, $acc_points, $acc_res_cashback);
                    array_push($amounts, $amount_disc, $res_netfee_amount, $total_pay, $cba2);
                    array_push($types, "D", "C", "D", "C");
                } else {
                    $cbmart = 0;
                    //push array jurnal
                    array_push($accounts, $acc_points);
                    array_push($amounts, $total_pay);
                    array_push($types, "D");
                }
            }
            //ledger entries
            for ($account = 0; $account < count($accounts); $account++) {
                if ($accounts[$account] != '') {
                    $ledger->accounts()->attach($accounts[$account], ['entry_type' => $types[$account], 'amount' => $amounts[$account]]);
                }
            }

            /* set order, order products, order details (inventory stock), order points */
            //set def
            $ref_def_id = Customer::select('id')
                ->Where('def', '=', '1')
                ->get();
            $owner_def = $ref_def_id[0]->id;
            $customers_id = $request->customers_id;
            $warehouses_id = 1;
            //set order
            $last_code = $this->get_last_code('order');
            $order_code = acc_code_generate($last_code, 8, 3);
            $register = $request->register;
            $data = array('memo' => $memo, 'total' => $total, 'type' => 'sale', 'status' => 'pending', 'ledgers_id' => $ledger_id, 'customers_id' => $customers_id, 'payment_type' => $payment_type, 'code' => $order_code, 'register' => $register);
            $order = Order::create($data);
            for ($i = 0; $i < $count_cart; $i++) {
                //set order products
                $order->products()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'price' => $cart_arr[$i]->price]);
                //set order order details (inventory stock)
                //check if package
                $products_type = Product::select('type')
                    ->where('id', $cart_arr[$i]->products_id)
                    ->get();
                $products_type = json_decode($products_type, false);
                if ($products_type[0]->type == 'package') {
                    $package_items = Package::with('products')
                        ->where('id', $cart_arr[$i]->products_id)
                        ->get();
                    $package_items = json_decode($package_items, false);
                    $package_items = $package_items[0]->products;
                    //loop items
                    foreach ($package_items as $key => $value) {
                        $order->productdetails()->attach($value->id, ['quantity' => $cart_arr[$i]->quantity * $value->pivot->quantity, 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $owner_def]);
                        $order->productdetails()->attach($value->id, ['quantity' => $cart_arr[$i]->quantity * $value->pivot->quantity, 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                    }
                } else {
                    $order->productdetails()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $owner_def]);
                    $order->productdetails()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                }
            }
            //get point source
            $point_source = $customers_id;
            if (isset($request->payment_type) && $request->payment_type == 'bank') {
                $point_source = $owner_def;
            }

            //set trf points from customer to Usdha Bhakti
            // $order->points()->attach($points_id, ['amount' => $total_pay, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin dari ' . $memo, 'customers_id' => $owner_def]);
            $order->points()->attach($points_id, ['amount' => $total_pay, 'type' => 'C', 'status' => 'onhold', 'memo' => 'Pemotongan Poin dari ' . $memo, 'customers_id' => $point_source]);

            //set trf points cashback agent ubb mart
            if ($cbmart > 0) {
                //ubb mart fee
                $order->points()->attach($points_id, ['amount' => $cbmart, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Komisi Agen UBB Mart) dari ' . $memo, 'customers_id' => $agent_row->ref_id]);
                //trsf ubb mart fee
                $order->points()->attach($points_id, ['amount' => $cbmart, 'type' => 'C', 'status' => 'onhold', 'memo' => 'Pemotongan Poin (Agen UBB Mart) dari ' . $memo, 'customers_id' => $point_source]);
            }

            //send invoice email
            $customer = Customer::find($customers_id);
            Mail::to($customer->email)->send(new OrderEmail($order->id, $customers_id));

            return response()->json([
                'success' => true,
                'message' => 'Aktivasi Member Berhasil!',
                'email' => $customer->email,
                'order_id' => $order->id,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Saldo Poin Member Tidak Mencukupi.',
            ], 401);
        }
    }

    public function store(Request $request)
    {
        //get total
        $total = 0;
        $discount = 0;
        $cogs_total = 0;
        $bv_total = 0;
        $profit = 0;
        $data = json_encode($request->all());
        $package = json_decode($data, false);
        $cart_arr = $package->cart;
        $count_cart = count($cart_arr);
        for ($i = 0; $i < $count_cart; $i++) {
            $total += $cart_arr[$i]->quantity * $cart_arr[$i]->price;
            $product = Product::find($cart_arr[$i]->products_id);
            $cogs_total += $cart_arr[$i]->quantity * $product->cogs;
            $bv_total += $cart_arr[$i]->quantity * $product->bv;
            $discount += $cart_arr[$i]->quantity * (($product->discount / 100) * $cart_arr[$i]->price);
        }
        $profit = $total - $cogs_total;

        //set member & point balance
        $member = Customer::find($request->customers_id);

        //check up status
        // $status_list_upline=$this->status_list_upline($member->slot_x, $member->slot_y);
        // if($status_list_upline['status']==0){
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Register Gagal, Status Upline masih ada yang belum activ.',
        //     ], 401);
        // }

        //get point member
        $points_id = 1;
        $points_saving_id = 3;
        $points_fee_id = 4;
        $points_debit = OrderPoint::where('customers_id', '=', $request->customers_id)
            ->where('type', '=', 'D')
            ->where('points_id', '=', 1)
            ->where('status', '=', 'onhand')
            ->sum('amount');
        $points_credit = OrderPoint::where('customers_id', '=', $request->customers_id)
            ->where('type', '=', 'C')
            ->where('points_id', '=', 1)
            ->where('status', '=', 'onhand')
            ->sum('amount');
        $points_balance = $points_debit - $points_credit;

        //get stock agent, loop package
        $stock_status = 'true';
        if ($request->tokensale != '') {
            $cart_arr = $request->cart['item'];
            $count_cart = count($cart_arr);
            for ($i = 0; $i < $count_cart; $i++) {
                $stock_debit = OrderDetails::where('owner', '=', $request->input('agents_id'))
                    ->where('type', '=', 'D')
                    ->where('status', '=', 'onhand')
                    ->where('products_id', $cart_arr[$i]['id'])
                    ->sum('quantity');
                $stock_credit = OrderDetails::where('owner', '=', $request->input('agents_id'))
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

        //compare total to point belanja
        if (($points_balance >= $total || $request->tokensale != '') && $stock_status == 'true') {
            /* proceed ledger */
            $memo = 'Transaksi Marketplace Member ' . $member->code . "-" . $member->name;
            $data = ['register' => $request->input('register'), 'title' => $memo, 'memo' => $memo, 'status' => 'pending'];
            $ledger = Ledger::create($data);
            $ledger_id = $ledger->id;
            //set ledger entry arr
            $profit_inactive = 0;

            //CBA 1
            $networkfee1_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBA01')
                ->get();
            $cba1 = (($networkfee1_row[0]->amount) / 100) * $total;
            //chech if agent has referal
            $cbmart = 0;
            //CBA 2
            $networkfee2_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBA02')
                ->get();
            $cba2 = (($networkfee2_row[0]->amount) / 100) * $total;
            $agent_row = Member::find($request->agents_id);
            // if ($agent_row->ref_bin_id > 0) {
            //     //CB Mart
            //     $cbmart_row = NetworkFee::select('*')
            //         ->Where('code', '=', 'CBMART')
            //         ->get();
            //     $cbmart = (($cbmart_row[0]->amount) / 100) * $total;
            //     $cba2 = (($networkfee2_row[0]->amount - $cbmart_row[0]->amount) / 100) * $total;
            // }
            //set ref fee level
            $package_network_row = NetworkFee::select('*')
                ->Where('type', '=', 'ro')
                ->Where('activation_type_id', '=', $member->activation_type_id)
                ->get();
            //BVCV
            $bvcv_row = NetworkFee::select('*')
                ->Where('code', '=', 'BVCV')
                ->get();
            //BVPO
            $bvpo_row = NetworkFee::select('*')
                ->Where('code', '=', 'BVPO')
                ->get();
            $bvcv = (($bvcv_row[0]->amount) / 100) * $bv_total;
            $bv_nett = $bv_total - $bvcv;

            $sbv = (($package_network_row[0]->sbv) / 100) * $bv_nett;
            //check if package conventional or not
            $package_type = 0;
            for ($i = 0; $i < $count_cart; $i++) {
                $products_type = Product::select('type', 'package_type')
                    ->where('id', $cart_arr[$i]->products_id)
                    ->get();
                $products_type = json_decode($products_type, false);
                if ($products_type[0]->type == 'package' && $products_type[0]->package_type == 'conventional') {
                    $package_type = 1;
                }
                if ($products_type[0]->type == 'package' && $products_type[0]->package_type == 'promo') {
                    $package_type = 2;
                }
            }
            $ref_fee_lev = 0;
            if ($package_type == 1) {
                //conventional fee
                //CONV
                // $conv_row = NetworkFee::select('*')
                //     ->Where('type', '=', 'conventional')
                //     ->get();
                // $ref_fee_lev = (($conv_row[0]->sbv) / 100) * $bv_nett;
                $ref_fee_lev = $discount;
            } else if ($package_type == 2) {
                $ref_fee_lev = 0;
            } else {
                // //get LEV RO
                // $lev_fee = $sbv / $ref_fee_row[0]->deep_level;
                // $ref_arr = array();
                // $ref_arr = $this->get_ref_exc($member->id, $ref_arr, 1, 0, $ref_fee_row[0]->deep_level);
                // $ref_fee_lev = count($ref_arr) * $lev_fee;
            }

            $package_activation_type_id = $this->get_act_type_bv($bv_nett);
            if ($package_type == 0) {
                //package activation type
                $package_activation_row = Activation::select('type', 'bv_min', 'bv_max')
                    ->Where('id', '=', $package_activation_type_id)
                    ->get();
                //get BV (min platinum)
                $min_plat_row = Activation::select('bv_min', 'bv_max')
                    ->Where('id', '=', 4)
                    ->first();
                $min_plat = $min_plat_row->bv_min * $bvpo_row[0]->amount;
                //package referal fee
                //ref 1 package fee
                $sbv_percen = $package_network_row[0]->sbv;
                $rsbv_g1_percen = $package_network_row[0]->rsbv_g1;
                $ref1_fee_point_sale_def = ($rsbv_g1_percen / 100) * ($sbv_percen / 100) * $bv_nett;
                if (($bv_nett > $min_plat) && $package_activation_type_id < 4) {
                    $ref1_fee_point_sale_def = ($rsbv_g1_percen / 100) * ($sbv_percen / 100) * $min_plat;
                }
                // //ref 2 package fee
                // $sbv_percen = $package_network_row[0]->sbv;
                // $rsbv_g2_percen = $package_network_row[0]->rsbv_g2;
                // $ref2_fee_point_sale_def = ($rsbv_g2_percen / 100) * ($sbv_percen / 100) * $bv_nett;
                // if (($bv_nett > $min_plat) && $package_activation_type_id < 4) {
                //     $ref2_fee_point_sale_def = ($rsbv_g2_percen / 100) * ($sbv_percen / 100) * $min_plat;
                // }

                //ref 1
                $ref1_fee_point_sale = 0;
                $ref1_fee_point_upgrade = 0;
                $ref1_flush_out = 0;
                $ref1_row = Member::find($member->ref_bin_id);
                //ref 1 row
                if (!empty($ref1_row) && $ref1_row->ref_bin_id > 0) {
                    $ref1_fee_row = NetworkFee::select('*')
                        ->Where('type', '=', 'activation')
                        ->Where('activation_type_id', '=', $ref1_row->activation_type_id)
                        ->get();
                    $sbv1_percen = $ref1_fee_row[0]->sbv;
                    $rsbv_g1_percen = $ref1_fee_row[0]->rsbv_g1;
                    $ref1_fee_point_sale = ($rsbv_g1_percen / 100) * ($sbv1_percen / 100) * $bv_nett;
                    if (($bv_nett > $min_plat) && $ref1_row->activation_type_id < 4) {
                        //$ref1_fee_point_sale = ($rsbv_g1_percen / 100) * ($sbv1_percen / 100) * $min_plat;
                    }
                    if ($ref1_fee_point_sale_def > $ref1_fee_point_sale) {
                        //$ref1_flush_out = $ref1_fee_point_sale_def - $ref1_fee_point_sale;
                    }
                }

                //ref 2
                $ref2_fee_point_sale = 0;
                $ref2_fee_point_upgrade = 0;
                $member_get_flush_out = 0;
                // $ref2_row = Member::find($ref1_row->ref_bin_id);
                // //ref 2 row
                // if (!empty($ref2_row) && $ref2_row->ref_bin_id > 0) {
                //     $ref2_fee_row = NetworkFee::select('*')
                //         ->Where('type', '=', 'activation')
                //         ->Where('activation_type_id', '=', $ref2_row->activation_type_id)
                //         ->get();
                //     $sbv2_percen = $ref2_fee_row[0]->sbv;
                //     $rsbv_g2_percen = $ref2_fee_row[0]->rsbv_g2;
                //     $ref2_fee_point_sale = ($rsbv_g2_percen / 100) * ($sbv2_percen / 100) * $bv_nett;
                //     if (($bv_nett > $min_plat) && $ref2_row->activation_type_id < 4) {
                //         $ref2_fee_point_sale = ($rsbv_g2_percen / 100) * ($sbv2_percen / 100) * $min_plat;
                //     }
                //     $member_get_flush_out = $ref2_row->id;
                //     if ($ref1_row->activation_type_id >= $ref2_row->activation_type_id) {
                //         $member_get_flush_out = 0;
                //     }
                // }
                if ($member_get_flush_out == 0) {
                    $ref1_flush_out = 0;
                }
            }

            /*set order*/
            //set def
            $customers_id = $request->customers_id;
            $agents_id = $request->agents_id;
            $warehouses_id = 1;
            $com_row = Member::select('*')
                ->where('def', '=', '1')
                ->get();
            $com_id = $com_row[0]->id;

            $payment_type = 'point';
            $status_delivery = 'received';
            $status_order = 'pending';
            if ($request->tokensale != '') {
                $payment_type = 'token';
                $status_delivery = 'delivered';
                $status_order = 'approved';
            }

            //set order
            $last_code = $this->get_last_code('order-agent');
            $order_code = acc_code_generate($last_code, 8, 3);
            $register = $request->register;
            $bv_ro_amount = 0;
            if ($package_type == 0) {
                $bv_ro_amount = $bv_total;
            }
            $data = array('memo' => $memo, 'total' => $total, 'type' => 'agent_sale', 'status' => $status_order, 'ledgers_id' => $ledger_id, 'customers_id' => $customers_id, 'agents_id' => $agents_id, 'payment_type' => $payment_type, 'code' => $order_code, 'register' => $register, 'bv_ro_amount' => $bv_ro_amount, 'bv_total' => $bv_total, 'token_no' => $request->tokensale, 'status_delivery' => $status_delivery);
            $order = Order::create($data);
            for ($i = 0; $i < $count_cart; $i++) {
                //set order products
                $order->products()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'price' => $cart_arr[$i]->price]);
                //set order order details (inventory stock)
                //check if package
                $products_type = Product::select('type')
                    ->where('id', $cart_arr[$i]->products_id)
                    ->get();
                $products_type = json_decode($products_type, false);
                if ($products_type[0]->type == 'package') {
                    $package_items = Package::with('products')
                        ->where('id', $cart_arr[$i]->products_id)
                        ->get();
                    $package_items = json_decode($package_items, false);
                    $package_items = $package_items[0]->products;
                    //loop items
                    foreach ($package_items as $key => $value) {
                        $order->productdetails()->attach($value->id, ['quantity' => $cart_arr[$i]->quantity * $value->pivot->quantity, 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $agents_id]);
                        $order->productdetails()->attach($value->id, ['quantity' => $cart_arr[$i]->quantity * $value->pivot->quantity, 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                    }
                } else {
                    $order->productdetails()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $agents_id]);
                    $order->productdetails()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                }
            }

            $ref2_id = 0;
            // if (!empty($ref2_row) && $ref2_row->ref_bin_id > 0) {
            //     $ref2_id = $ref2_row->id;
            // }

            //insert pairing info
            $data = array('order_id' => $order->id, 'ref_id' => $member->ref_bin_id, 'bv_total' => $bv_total, 'bvcv_amount' => $bvcv_row[0]->amount, 'ref1_fee_point_sale' => $ref1_fee_point_sale, 'ref1_fee_point_upgrade' => $ref1_fee_point_upgrade, 'ref2_fee_point_sale' => $ref2_fee_point_sale, 'ref2_fee_point_upgrade' => $ref2_fee_point_upgrade, 'ref1_flush_out' => $ref1_flush_out, 'ledger_id' => $ledger_id, 'cba2' => $cba2, 'cbmart' => $cbmart, 'points_fee_id' => $points_fee_id, 'points_upg_id' => 2, 'ref2_id' => $ref2_id, 'memo' => $memo, 'member_get_flush_out' => $member_get_flush_out, 'package_type' => $package_type, 'ref_fee_lev' => $ref_fee_lev, 'customer_id' => $member->id);
            $pairinginfo = PairingInfo::create($data);

            //set trf points from member to Usadha Bhakti
            $order->points()->attach($points_id, ['amount' => $total, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Penambahan Poin dari (Pending Order) ' . $memo, 'customers_id' => $com_id]);
            if ($request->tokensale == '') {
                $order->points()->attach($points_id, ['amount' => $total, 'type' => 'C', 'status' => 'onhand', 'memo' => 'Pemotongan Poin dari ' . $memo, 'customers_id' => $customers_id]);
            }

            if ($package_type == 1) {
                //set trf points from usadha to ref1
                $order->points()->attach($points_fee_id, ['amount' => $cba2, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Conventional Refferal) dari ' . $memo, 'customers_id' => $member->ref_bin_id]);
                //set trf points cashback agent ubb mart
                if ($cbmart > 0) {
                    $order->points()->attach($points_id, ['amount' => $cbmart, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Komisi Agen UBB Mart) dari ' . $memo, 'customers_id' => $agent_row->ref_bin_id]);
                }
                //set trf points from usadha to member conv fee
                $order->points()->attach($points_saving_id, ['amount' => $ref_fee_lev, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin Tabungan (Conventional Refferal) dari ' . $memo, 'customers_id' => $member->id]);
            }if ($package_type == 2) {
                //set trf points from usadha to agent
                $order->points()->attach($points_id, ['amount' => $cba2, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Cashback Agen 2) dari ' . $memo, 'customers_id' => $agents_id]);
                //set trf points cashback agent ubb mart
                if ($cbmart > 0) {
                    $order->points()->attach($points_id, ['amount' => $cbmart, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Komisi Agen UBB Mart) dari ' . $memo, 'customers_id' => $agent_row->ref_bin_id]);
                }
            } else {
                //set trf points from usadha to agent
                $order->points()->attach($points_id, ['amount' => $cba2, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Cashback Agen 2) dari ' . $memo, 'customers_id' => $agents_id]);
                //set trf points cashback agent ubb mart
                if ($cbmart > 0) {
                    $order->points()->attach($points_id, ['amount' => $cbmart, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Komisi Agen UBB Mart) dari ' . $memo, 'customers_id' => $agent_row->ref_bin_id]);
                }
            }

            //set trf points from member to agent
            if ($request->tokensale == '') {
                $order->points()->attach($points_id, ['amount' => $total, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin dari (Penjualan Paket) ' . $memo, 'customers_id' => $agents_id]);
            }

            //if using token sale
            if ($request->tokensale != '') {
                $tokensales = Tokensale::where('code', $request->tokensale)
                    ->where('status', '=', 'active')
                    ->orderBy('id', 'DESC')
                    ->first();
                $tokensales->status = 'closed';
                $tokensales->save();
                $this->orderCompleted($order->id);
            }

            //push notif to agent
            $user_os = Customer::find($agents_id);
            $id_onesignal = $user_os->id_onesignal;
            $memo = 'Order Masuk dari ' . $memo;
            $register = date("Y-m-d");
            //store to logs_notif
            $data = ['register' => $register, 'customers_id' => $agents_id, 'memo' => $memo];
            $logs = LogNotif::create($data);
            //push notif
            OneSignal::sendNotificationToUser(
                $memo,
                $id_onesignal,
                $url = null,
                $data = null,
                $buttons = null,
                $schedule = null
            );

            //send invoice email
            //get agent email
            $agent = Customer::find($agents_id);
            Mail::to($agent->email)->send(new OrderEmail($order->id, $agents_id));

            return response()->json([
                'success' => true,
                'message' => 'Pembelian Member Berhasil!',
                'email' => $agent->email,
                'order_id' => $order->id,
                'ref_id' => $member->ref_bin_id,
                'package_type' => $package_type,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Saldo Poin Member Tidak Mencukupi atau Stok Agen tidak mencukupi.',
            ], 401);
        }
    }

    public function automaintain(Request $request)
    {
        //get total
        $total = 0;
        $discount = 0;
        $cogs_total = 0;
        $bv_total = 0;
        $profit = 0;
        $data = json_encode($request->all());
        $package = json_decode($data, false);
        $cart_arr = $package->cart;
        $count_cart = count($cart_arr);
        for ($i = 0; $i < $count_cart; $i++) {
            $total += $cart_arr[$i]->quantity * $cart_arr[$i]->price;
            $product = Product::find($cart_arr[$i]->products_id);
            $cogs_total += $cart_arr[$i]->quantity * $product->cogs;
            $bv_total += $cart_arr[$i]->quantity * $product->bv;
            $discount += $cart_arr[$i]->quantity * (($product->discount / 100) * $cart_arr[$i]->price);
        }
        $profit = $total - $cogs_total;

        //set member & point balance
        $member = Customer::find($request->customers_id);

        //get point member
        $points_id = 1;
        $points_saving_id = 3;
        $points_fee_id = 4;
        $points_debit = OrderPoint::where('customers_id', '=', $request->customers_id)
            ->where('type', '=', 'D')
            ->where('points_id', '=', 1)
            ->where('status', '=', 'onhand')
            ->sum('amount');
        $points_credit = OrderPoint::where('customers_id', '=', $request->customers_id)
            ->where('type', '=', 'C')
            ->where('points_id', '=', 1)
            ->where('status', '=', 'onhand')
            ->sum('amount');
        $points_balance = $points_debit - $points_credit;

        //compare total to point belanja
        if ($points_balance >= $total) {
            /* proceed ledger */
            $memo = 'Transaksi Auto Maintain Member ' . $member->code . "-" . $member->name;
            $data = ['register' => $request->input('register'), 'title' => $memo, 'memo' => $memo, 'status' => 'pending'];
            $ledger = Ledger::create($data);
            $ledger_id = $ledger->id;
            //set ledger entry arr
            $profit_inactive = 0;

            //CBA 1
            $networkfee1_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBA01')
                ->get();
            $cba1 = (($networkfee1_row[0]->amount) / 100) * $total;
            //chech if agent has referal
            $cbmart = 0;
            //CBA 2
            $networkfee2_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBA02')
                ->get();
            $cba2 = (($networkfee2_row[0]->amount) / 100) * $total;
            $agent_row = Member::find($request->agents_id);

            //set ref fee level
            $package_network_row = NetworkFee::select('*')
                ->Where('type', '=', 'ro')
                ->Where('activation_type_id', '=', $member->activation_type_id)
                ->get();
            //BVCV
            $bvcv_row = NetworkFee::select('*')
                ->Where('code', '=', 'BVCV')
                ->get();
            //BVPO
            $bvpo_row = NetworkFee::select('*')
                ->Where('code', '=', 'BVPO')
                ->get();
            $bvcv = (($bvcv_row[0]->amount) / 100) * $bv_total;
            $bv_nett = $bv_total - $bvcv;

            $sbv = (($package_network_row[0]->sbv) / 100) * $bv_nett;
            //check if package conventional or not
            $package_type = 0;
            for ($i = 0; $i < $count_cart; $i++) {
                $products_type = Product::select('type', 'package_type')
                    ->where('id', $cart_arr[$i]->products_id)
                    ->get();
                $products_type = json_decode($products_type, false);
                if ($products_type[0]->type == 'package' && $products_type[0]->package_type == 'conventional') {
                    $package_type = 1;
                }
                if ($products_type[0]->type == 'package' && $products_type[0]->package_type == 'promo') {
                    $package_type = 2;
                }
            }
            $ref_fee_lev = 0;
            if ($package_type == 1) {
                $ref_fee_lev = $discount;
            } else if ($package_type == 2) {
                $ref_fee_lev = 0;
            } else {
            }

            $package_activation_type_id = $this->get_act_type_bv($bv_nett);
            if ($package_type == 0) {
                //package activation type
                $package_activation_row = Activation::select('type', 'bv_min', 'bv_max')
                    ->Where('id', '=', $package_activation_type_id)
                    ->get();
                //get BV (min platinum)
                $min_plat_row = Activation::select('bv_min', 'bv_max')
                    ->Where('id', '=', 4)
                    ->first();
                $min_plat = $min_plat_row->bv_min * $bvpo_row[0]->amount;
                //package referal fee
                //ref 1 package fee
                $sbv_percen = $package_network_row[0]->sbv;
                $rsbv_g1_percen = $package_network_row[0]->rsbv_g1;
                $ref1_fee_point_sale_def = ($rsbv_g1_percen / 100) * ($sbv_percen / 100) * $bv_nett;
                if (($bv_nett > $min_plat) && $package_activation_type_id < 4) {
                    $ref1_fee_point_sale_def = ($rsbv_g1_percen / 100) * ($sbv_percen / 100) * $min_plat;
                }

                //ref 1
                $ref1_fee_point_sale = 0;
                $ref1_fee_point_upgrade = 0;
                $ref1_flush_out = 0;

                //ref 2
                $ref2_fee_point_sale = 0;
                $ref2_fee_point_upgrade = 0;
                $member_get_flush_out = 0;
                if ($member_get_flush_out == 0) {
                    $ref1_flush_out = 0;
                }
            }

            /*set order*/
            //set def
            $customers_id = $request->customers_id;
            $agents_id = $request->agents_id;
            $warehouses_id = 1;
            $com_row = Member::select('*')
                ->where('def', '=', '1')
                ->get();
            $com_id = $com_row[0]->id;
            //set order
            $last_code = $this->get_last_code('order-agent');
            $order_code = acc_code_generate($last_code, 8, 3);
            $register = $request->register;
            $bv_ro_amount = 0;
            if ($package_type == 0) {
                $bv_ro_amount = $bv_total;
            }
            $data = array('memo' => $memo, 'total' => $total, 'type' => 'agent_sale', 'status' => 'pending', 'ledgers_id' => $ledger_id, 'customers_id' => $customers_id, 'agents_id' => $agents_id, 'payment_type' => 'point', 'code' => $order_code, 'register' => $register, 'bv_ro_amount' => $bv_ro_amount, 'bv_total' => $bv_total, 'bv_automaintain_amount' => $bv_total);
            $order = Order::create($data);
            for ($i = 0; $i < $count_cart; $i++) {
                //set order products
                $order->products()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'price' => $cart_arr[$i]->price]);
                //set order order details (inventory stock)
                //check if package
                $products_type = Product::select('type')
                    ->where('id', $cart_arr[$i]->products_id)
                    ->get();
                $products_type = json_decode($products_type, false);
                if ($products_type[0]->type == 'package') {
                    $package_items = Package::with('products')
                        ->where('id', $cart_arr[$i]->products_id)
                        ->get();
                    $package_items = json_decode($package_items, false);
                    $package_items = $package_items[0]->products;
                    //loop items
                    foreach ($package_items as $key => $value) {
                        $order->productdetails()->attach($value->id, ['quantity' => $cart_arr[$i]->quantity * $value->pivot->quantity, 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $agents_id]);
                        $order->productdetails()->attach($value->id, ['quantity' => $cart_arr[$i]->quantity * $value->pivot->quantity, 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                    }
                } else {
                    $order->productdetails()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $agents_id]);
                    $order->productdetails()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                }
            }

            $ref2_id = 0;

            //insert pairing info
            $data = array('order_id' => $order->id, 'ref_id' => $member->ref_bin_id, 'bv_total' => $bv_total, 'bvcv_amount' => $bvcv_row[0]->amount, 'ref1_fee_point_sale' => $ref1_fee_point_sale, 'ref1_fee_point_upgrade' => $ref1_fee_point_upgrade, 'ref2_fee_point_sale' => $ref2_fee_point_sale, 'ref2_fee_point_upgrade' => $ref2_fee_point_upgrade, 'ref1_flush_out' => $ref1_flush_out, 'ledger_id' => $ledger_id, 'cba2' => $cba2, 'cbmart' => $cbmart, 'points_fee_id' => $points_fee_id, 'points_upg_id' => 2, 'ref2_id' => $ref2_id, 'memo' => $memo, 'member_get_flush_out' => $member_get_flush_out, 'package_type' => $package_type, 'ref_fee_lev' => $ref_fee_lev, 'customer_id' => $member->id);
            $pairinginfo = PairingInfo::create($data);

            //set trf points from member to Usadha Bhakti
            $order->points()->attach($points_id, ['amount' => $total, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Penambahan Poin dari (Pending Order) ' . $memo, 'customers_id' => $com_id]);
            $order->points()->attach($points_id, ['amount' => $total, 'type' => 'C', 'status' => 'onhand', 'memo' => 'Pemotongan Poin dari ' . $memo, 'customers_id' => $customers_id]);

            if ($package_type == 1) {
                //set trf points from usadha to ref1
                $order->points()->attach($points_fee_id, ['amount' => $cba2, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Conventional Refferal) dari ' . $memo, 'customers_id' => $member->ref_bin_id]);
                //set trf points cashback agent ubb mart
                if ($cbmart > 0) {
                    $order->points()->attach($points_id, ['amount' => $cbmart, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Komisi Agen UBB Mart) dari ' . $memo, 'customers_id' => $agent_row->ref_bin_id]);
                }
                //set trf points from usadha to member conv fee
                $order->points()->attach($points_saving_id, ['amount' => $ref_fee_lev, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin Tabungan (Conventional Refferal) dari ' . $memo, 'customers_id' => $member->id]);
            }if ($package_type == 2) {
                //set trf points from usadha to agent
                $order->points()->attach($points_id, ['amount' => $cba2, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Cashback Agen 2) dari ' . $memo, 'customers_id' => $agents_id]);
                //set trf points cashback agent ubb mart
                if ($cbmart > 0) {
                    $order->points()->attach($points_id, ['amount' => $cbmart, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Komisi Agen UBB Mart) dari ' . $memo, 'customers_id' => $agent_row->ref_bin_id]);
                }
            } else {
                //set trf points from usadha to agent
                $order->points()->attach($points_id, ['amount' => $cba2, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Cashback Agen 2) dari ' . $memo, 'customers_id' => $agents_id]);
                //set trf points cashback agent ubb mart
                if ($cbmart > 0) {
                    $order->points()->attach($points_id, ['amount' => $cbmart, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin (Komisi Agen UBB Mart) dari ' . $memo, 'customers_id' => $agent_row->ref_bin_id]);
                }
            }

            //set trf points from member to agent
            $order->points()->attach($points_id, ['amount' => $total, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin dari (Penjualan Paket) ' . $memo, 'customers_id' => $agents_id]);

            //push notif to agent
            $user_os = Customer::find($agents_id);
            $id_onesignal = $user_os->id_onesignal;
            $memo = 'Order Masuk dari ' . $memo;
            $register = date("Y-m-d");
            //store to logs_notif
            $data = ['register' => $register, 'customers_id' => $agents_id, 'memo' => $memo];
            $logs = LogNotif::create($data);
            //push notif
            OneSignal::sendNotificationToUser(
                $memo,
                $id_onesignal,
                $url = null,
                $data = null,
                $buttons = null,
                $schedule = null
            );

            //send invoice email
            //get agent email
            $agent = Customer::find($agents_id);
            Mail::to($agent->email)->send(new OrderEmail($order->id, $agents_id));

            return response()->json([
                'success' => true,
                'message' => 'Pembelian Member Berhasil!',
                'email' => $agent->email,
                'order_id' => $order->id,
                'ref_id' => $member->ref_bin_id,
                'package_type' => $package_type,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Saldo Poin Member Tidak Mencukupi.',
            ], 401);
        }
    }

    public function reseller(Request $request)
    {
        //get total
        $total = 0;
        $discount = 0;
        $cogs_total = 0;
        $bv_total = 0;
        $profit = 0;
        $data = json_encode($request->all());
        $package = json_decode($data, false);
        $cart_arr = $package->cart;
        $count_cart = count($cart_arr);
        for ($i = 0; $i < $count_cart; $i++) {
            $total += $cart_arr[$i]->quantity * $cart_arr[$i]->price;
            $product = Product::find($cart_arr[$i]->products_id);
            $cogs_total += $cart_arr[$i]->quantity * $product->cogs;
            $bv_total += $cart_arr[$i]->quantity * $product->bv;
            $discount += $cart_arr[$i]->quantity * (($product->discount / 100) * $cart_arr[$i]->price);
        }
        $profit = $total - $cogs_total;

        //set member & point balance
        $member = Customer::find($request->customers_id);

        //get point member
        $points_id = 1;
        $points_saving_id = 3;
        $points_fee_id = 4;
        $points_debit = OrderPoint::where('customers_id', '=', $request->customers_id)
            ->where('type', '=', 'D')
            ->where('points_id', '=', 1)
            ->where('status', '=', 'onhand')
            ->sum('amount');
        $points_credit = OrderPoint::where('customers_id', '=', $request->customers_id)
            ->where('type', '=', 'C')
            ->where('points_id', '=', 1)
            ->where('status', '=', 'onhand')
            ->sum('amount');
        $points_balance = $points_debit - $points_credit;

        //get stock agent, loop package
        $stock_status = 'true';
        if ($request->tokensale != '') {
            $cart_arr = $request->cart['item'];
            $count_cart = count($cart_arr);
            for ($i = 0; $i < $count_cart; $i++) {
                $stock_debit = OrderDetails::where('owner', '=', $request->input('agents_id'))
                    ->where('type', '=', 'D')
                    ->where('status', '=', 'onhand')
                    ->where('products_id', $cart_arr[$i]['id'])
                    ->sum('quantity');
                $stock_credit = OrderDetails::where('owner', '=', $request->input('agents_id'))
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

        //compare total to point belanja
        if (($points_balance >= $total || $request->tokensale != '') && $stock_status == 'true') {
            /* proceed ledger */
            $memo = 'Transaksi RO Reseller Member ' . $member->code . "-" . $member->name;
            $data = ['register' => $request->input('register'), 'title' => $memo, 'memo' => $memo, 'status' => 'pending'];
            $ledger = Ledger::create($data);
            $ledger_id = $ledger->id;
            //set ledger entry arr
            $profit_inactive = 0;

            /*set order*/
            //set def
            $customers_id = $request->customers_id;
            $agents_id = $request->agents_id;
            $warehouses_id = 1;
            $com_row = Member::select('*')
                ->where('def', '=', '1')
                ->get();
            $com_id = $com_row[0]->id;

            $payment_type = 'point';
            $status_delivery = 'received';
            $status_order = 'pending';
            if ($request->tokensale != '') {
                $payment_type = 'token';
                $status_delivery = 'delivered';
                $status_order = 'approved';
            }

            //set order
            $last_code = $this->get_last_code('order-agent');
            $order_code = acc_code_generate($last_code, 8, 3);
            $register = $request->register;
            $bv_ro_amount = 0;
            $package_type = 0;
            if ($package_type == 0) {
                $bv_ro_amount = $bv_total;
            }
            //BVPO
            $bvpo_row = NetworkFee::select('*')
                ->Where('code', '=', 'BVPO')
                ->get();
            $point_reseller_amount = $total / $bvpo_row[0]->amount;
            $data = array('memo' => $memo, 'total' => $total, 'type' => 'agent_sale', 'status' => $status_order, 'ledgers_id' => $ledger_id, 'customers_id' => $customers_id, 'agents_id' => $agents_id, 'payment_type' => $payment_type, 'code' => $order_code, 'register' => $register, 'bv_ro_amount' => $bv_ro_amount, 'bv_total' => $bv_total, 'bv_reseller_amount' => $point_reseller_amount, 'token_no' => $request->tokensale, 'status_delivery' => $status_delivery);
            $order = Order::create($data);
            for ($i = 0; $i < $count_cart; $i++) {
                //set order products
                $order->products()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'price' => $cart_arr[$i]->price]);
                //set order order details (inventory stock)
                //check if package
                $products_type = Product::select('type')
                    ->where('id', $cart_arr[$i]->products_id)
                    ->get();
                $products_type = json_decode($products_type, false);
                if ($products_type[0]->type == 'package') {
                    $package_items = Package::with('products')
                        ->where('id', $cart_arr[$i]->products_id)
                        ->get();
                    $package_items = json_decode($package_items, false);
                    $package_items = $package_items[0]->products;
                    //loop items
                    foreach ($package_items as $key => $value) {
                        $order->productdetails()->attach($value->id, ['quantity' => $cart_arr[$i]->quantity * $value->pivot->quantity, 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $agents_id]);
                        $order->productdetails()->attach($value->id, ['quantity' => $cart_arr[$i]->quantity * $value->pivot->quantity, 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                    }
                } else {
                    $order->productdetails()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'type' => 'C', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $agents_id]);
                    $order->productdetails()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'type' => 'D', 'status' => 'onhold', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                }
            }

            //set trf points from member to Usadha Bhakti
            $order->points()->attach($points_id, ['amount' => $total, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Penambahan Poin dari (Pending Order) ' . $memo, 'customers_id' => $com_id]);
            if ($request->tokensale == '') {
                $order->points()->attach($points_id, ['amount' => $total, 'type' => 'C', 'status' => 'onhand', 'memo' => 'Pemotongan Poin dari ' . $memo, 'customers_id' => $customers_id]);
            }

            //set trf points from member to agent
            if ($request->tokensale == '') {
                $order->points()->attach($points_id, ['amount' => $total, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin dari (Penjualan Paket) ' . $memo, 'customers_id' => $agents_id]);
            }

            //set RESELLER point
            $points_reseller_id = 5;
            $order->points()->attach($points_reseller_id, ['amount' => $point_reseller_amount, 'type' => 'D', 'status' => 'onhold', 'memo' => 'Penambahan Poin Belanja Reseller dari ' . $memo, 'customers_id' => $customers_id]);

            //if using token sale
            if ($request->tokensale != '') {
                $tokensales = Tokensale::where('code', $request->tokensale)
                    ->where('status', '=', 'active')
                    ->orderBy('id', 'DESC')
                    ->first();
                $tokensales->status = 'closed';
                $tokensales->save();
                $this->orderCompleted($order->id);
            }

            //push notif to agent
            $user_os = Customer::find($agents_id);
            $id_onesignal = $user_os->id_onesignal;
            $memo = 'Order Masuk dari ' . $memo;
            $register = date("Y-m-d");
            //store to logs_notif
            $data = ['register' => $register, 'customers_id' => $agents_id, 'memo' => $memo];
            $logs = LogNotif::create($data);
            //push notif
            OneSignal::sendNotificationToUser(
                $memo,
                $id_onesignal,
                $url = null,
                $data = null,
                $buttons = null,
                $schedule = null
            );

            //send invoice email
            //get agent email
            $agent = Customer::find($agents_id);
            Mail::to($agent->email)->send(new OrderEmail($order->id, $agents_id));

            return response()->json([
                'success' => true,
                'message' => 'Pembelian Member Berhasil!',
                'email' => $agent->email,
                'order_id' => $order->id,
                'ref_id' => $member->ref_bin_id,
                'package_type' => $package_type,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Saldo Poin Member Tidak Mencukupi atau Stok Agen tidak mencukupi.',
            ], 401);
        }
    }

}
