<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Tokensale;
use App\Traits\TraitModel;
use Berkayk\OneSignal\OneSignalClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokensalesApiController extends Controller
{
    use TraitModel;
    private $onesignal_client;

    public function __construct()
    {
        $this->onesignal_client = new OneSignalClient(env('ONESIGNAL_APP_ID_MEMBER'), env('ONESIGNAL_REST_API_KEY_MEMBER'), '');
    }

    public function validToken(Request $request)
    {
        $tokensales = Tokensale::with('products')
                ->with('agents')        
                ->where('code', $request->token)
                ->where('status', '=', 'active')
                ->where('type', $request->type)
                ->orderBy('id', 'DESC')
                ->first();

        //Check if token found or not.
        if (is_null($tokensales)) {
            $message = 'Tokensale not found.';
            $status = false;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        } else {
            $message = 'Tokensale retrieved successfully.';
            $status = true;
            return response()->json([
                'status' => $status,
                'message' => $message,
                'data' => $tokensales,
            ]);
        }
    }

    public function historyToken($id, Request $request)
    {
        // return $request;
        if (isset($request->page)) {
            $tokensales = Tokensale::with('customers')
                ->with('products')
                ->where('agent_id', $id)
                ->orderBy('id', 'DESC')
                ->paginate(10, ['*'], 'page', $request->page);
        } else {
            $tokensales = Tokensale::with('customers')
                ->with('products')
                ->where('agent_id', $id)
                ->orderBy('id', 'DESC')
                ->get();
        }

        //Check if history found or not.
        if (is_null($tokensales)) {
            $message = 'History Tokensale not found.';
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
                'data' => $tokensales,
            ]);
        }
    }

    public function generateToken(Request $request)
    {
        //get request data
        $data = json_encode($request->all());
        $package = json_decode($data, false);
        $cart_arr = $package->cart;
        $count_cart = count($cart_arr);
        //get total
        $total = 0;
        for ($i = 0; $i < $count_cart; $i++) {
            $total += $cart_arr[$i]->quantity * $cart_arr[$i]->price;
        }

        //generate token
        $code = $this->gen_token();
        $data = array('agent_id' => $package->agent_id, 'customer_id' => $package->customer_id, 'code' => $code, 'type' => $package->type, 'activation_type_id' => $package->activation_type_id, 'old_activation_type_id' => $package->old_activation_type_id, 'memo' => $package->memo, 'total' => $total);
        $tokensale = Tokensale::create($data);
        for ($i = 0; $i < $count_cart; $i++) {
            //insert into tokensale_product
            $tokensale->products()->attach($cart_arr[$i]->products_id, ['quantity' => $cart_arr[$i]->quantity, 'price' => $cart_arr[$i]->price]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Generate Token Berhasil!',
            'token' => $code,
        ]);

    }

}
