<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\City;
use App\Classes\ReservationClass;
use App\CustomerApi;
use App\Events\MemberActivated;
use App\Events\MemberDownline;
use App\Events\MemberUpgrade;
use App\Http\Controllers\Controller;
use App\Ledger;
use App\LogNotif;
use App\Mail\MemberEmail;
use App\Mail\ResetEmail;
use App\Member;
use App\NetworkFee;
use App\Order;
use App\OrderPoint;
use App\Package;
use App\Product;
use App\Province;
use App\Traits\TraitModel;
use Auth;
use Berkayk\OneSignal\OneSignalClient;
use Hashids;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Validator;

class CustomersApiController extends Controller
{

    use TraitModel;
    private $onesignal_client;

    public function __construct()
    {
        $this->onesignal_client = new OneSignalClient(env('ONESIGNAL_APP_ID_MEMBER'), env('ONESIGNAL_REST_API_KEY_MEMBER'), '');
    }

    public function reservationHistory(Request $request)
    {
        if (isset($request->page)) {
            $orders = Order::with('customers')
                ->with('availabilities')
                ->where('customers_id', '=', $request->customers_id)
                ->where('type', 'reservation')
                ->orderBy('id', 'DESC')
                ->paginate(10, ['*'], 'page', $request->page);
        } else {
            $orders = Order::with('customers')
                ->with('availabilities')
                ->where('customers_id', '=', $request->customers_id)
                ->where('type', 'reservation')
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

    public function reservation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'memo' => 'required',
            'customers_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 401);
        } else {
            try {
                $reservationClass = new ReservationClass($request);
                $reservationClass->main();
            } catch (QueryException $exception) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Kosong.',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reservasi Berhasil.',
            ]);
        }
    }

    public function testPairing(Request $request)
    {
        $testPairing = $this->test_pairing_bin($request->order_id, $request->customer_id, $request->bv_amount_inc, $request->points_fee_id);
    }

    public function getAutoMaintain(Request $request)
    {
        $auto_maintain_bv = $this->career_type($request->customer_id);

        return response()->json([
            'success' => true,
            'data' => $auto_maintain_bv,
        ]);
    }

    public function statusListUp(Request $request)
    {
        $status_list_upline = $this->status_list_upline($request->slot_x, $request->slot_y);

        return response()->json([
            'success' => true,
            'data' => $status_list_upline,
        ]);
    }

    public function pairing_info(Request $request)
    {
        //BVPO
        $bvpo_row = NetworkFee::select('*')
            ->Where('code', '=', 'BVPO')
            ->first();
        //
        $bv_queue = $this->get_bv_queue($request->id);
        $bv_pairing_r = ($bv_queue['r'] - $bv_queue['c']) / $bvpo_row->amount;
        $bv_pairing_l = ($bv_queue['l'] - $bv_queue['c']) / $bvpo_row->amount;
        $bv_queue_c = $bv_queue['c'] / $bvpo_row->amount;
        $bv_queue_c_count = $bv_queue['c_count'];
        $reg_today = date('Y-m-d');
        $get_bv_daily_queue = $this->get_bv_daily_queue($request->id, $reg_today) / $bvpo_row->amount;
        return response()->json([
            'success' => true,
            'bv_pairing_r' => $bv_pairing_r,
            'bv_pairing_l' => $bv_pairing_l,
            'bv_queue_c' => $bv_queue_c,
            'bv_queue_c_count' => $bv_queue_c_count,
            'get_bv_daily_queue' => $get_bv_daily_queue,
        ]);
    }

    public function net_info(Request $request)
    {
        $member = CustomerApi::find($request->id);
        $downline_ref = CustomerApi::select('id')
            ->where('ref_bin_id', $request->id)
            ->where('type', '=', 'member')
            ->where('status', '=', 'active')
            ->get();
        //get total level
        $get_level_total = $this->get_level_total($member->slot_x, $member->slot_y, 1, 0);
        //get left and right child
        $slot_selected_x = $member->slot_x + 1;
        $slot_selected_y_left = ($member->slot_y * 2) - 1;
        $slot_selected_y_right = ($member->slot_y * 2);
        $downline_left = CustomerApi::select('id')
            ->where('ref_bin_id', '>', 0)
            ->where('type', '=', 'member')
            ->where('slot_x', '=', $slot_selected_x)
            ->where('slot_y', $slot_selected_y_left)
            ->first();
        $downline_right = CustomerApi::select('id')
            ->where('ref_bin_id', '>', 0)
            ->where('type', '=', 'member')
            ->where('slot_x', '=', $slot_selected_x)
            ->where('slot_y', $slot_selected_y_right)
            ->first();

        $downline_left_total = 0;
        if ($downline_left) {
            $downline_left_total = $this->get_downline_total($slot_selected_x, $slot_selected_y_left, 1, 0) + 1;
        }
        $downline_right_total = 0;
        if ($downline_right) {
            $downline_right_total = $this->get_downline_total($slot_selected_x, $slot_selected_y_right, 1, 0) + 1;
        }

        return response()->json([
            'success' => true,
            'right_total' => $downline_right_total,
            'left_total' => $downline_left_total,
            'level_total' => $get_level_total,
            'ref_total' => count($downline_ref),
        ]);
    }

    public function groupLRAmount(Request $request)
    {
        $pairing_bin = $this->pairing_bin($request->order_id, $request->customer_id, $request->bv_amount_inc, $request->points_fee_id);

        return response()->json([
            'success' => true,
            'data' => $pairing_bin,
        ]);
    }

    public function slotListUp(Request $request)
    {
        $list_upline = $this->get_list_upline($request->slot_x, $request->slot_y);

        return response()->json([
            'success' => true,
            'data' => $list_upline,
        ]);
    }

    public function slotIfLR(Request $request)
    {
        $if_lr = $this->group_if_lr($request->group_slot_x, $request->group_slot_y, $request->slot_x, $request->slot_y);
        $status = 'l';
        if ($if_lr['y'] % 2 == 0) {
            $status = 'r';
        }
        return response()->json([
            'success' => true,
            'x' => $if_lr['x'],
            'y' => $if_lr['y'],
            'status' => $status,
        ]);
    }

    public function slotEmpty(Request $request)
    {
        //get x & y referal
        $user = CustomerApi::where('id', $request->id)->first();
        $slot_arr = array();
        $get_slot_empty = $this->get_slot_empty($user->slot_x, $user->slot_y, 1, $slot_arr);
        return response()->json([
            'success' => true,
            'parent' => $get_slot_empty,
        ]);
    }

    public function slotTree(Request $request)
    {
        $slot_prev_x = -1;
        $slot_prev_y = -1;
        $user = CustomerApi::where('id', $request->id)->first();

        if ($request->has('slot_x') && $request->has('slot_x') != null) {
            if ($request->status == '0') {
                $yu = 1;
                $slot_arr = array();
                if ($request->type_hu == 3) {
                    $get_slot_empty = $this->get_slot_empty_3hu($request->slot_x, $request->slot_y, 1, $slot_arr);
                } else {
                    $get_slot_empty = $this->get_slot_empty($request->slot_x, $request->slot_y, 1, $slot_arr);
                }
                $slot_init_x = $get_slot_empty['x'];
                $slot_init_y = $get_slot_empty['y'];
            } else {
                $yu = 2;
                $slot_init_x = $request->slot_x;
                $slot_init_y = $request->slot_y;
            }
            if ($slot_init_x > $user->slot_x) {
                $slot_prev_x = $slot_init_x - 1;
                $slot_prev_y = ceil($slot_init_y / 2);
                //$slot_prev_y = ceil($slot_prev_y / 2);
                //$slot_prev_y = ceil($slot_prev_y / 2);
            }
        } else {
            $yu = 3;
            $slot_init_x = $user->slot_x;
            $slot_init_y = $user->slot_y;
        }
        $slot_arr = array();
        $slot_arr[0][0]['x'] = $slot_init_x;
        $slot_arr[0][0]['y'] = $slot_init_y;
        $slot_customer = CustomerApi::select('id', 'activation_type_id', 'code', 'name', 'status', 'slot_x', 'slot_y')->where("slot_x", $slot_init_x)->where("slot_y", $slot_init_y)->with('activations')->first();
        if ($slot_customer) {
            $slot_arr[0][0]['data'] = $slot_customer;
            $top_id = $slot_customer->id;
        } else {
            $slot_arr[0][0]['data'] = '';
            $top_id = 0;
        }
        for ($i = 1; $i <= 3; $i++) {
            $slot_arr[$i][0]['x'] = $slot_arr[$i - 1][0]['x'] + 1;
            $slot_arr[$i][0]['y'] = ($slot_arr[$i - 1][0]['y'] * 2) - 1;
            $slot_customer = CustomerApi::select('id', 'activation_type_id', 'code', 'name', 'status', 'slot_x', 'slot_y')->where("slot_x", $slot_arr[$i][0]['x'])->where("slot_y", $slot_arr[$i][0]['y'])->with('activations')->first();
            if ($slot_customer) {
                $slot_arr[$i][0]['data'] = $slot_customer;
            } else {
                $slot_arr[$i][0]['data'] = '';
            }
            for ($j = 1; $j < pow(2, $i); $j++) {
                $slot_arr[$i][$j]['x'] = $slot_arr[$i][$j - 1]['x'];
                $slot_arr[$i][$j]['y'] = ($slot_arr[$i][$j - 1]['y']) + 1;
                $slot_customer = CustomerApi::select('id', 'activation_type_id', 'code', 'name', 'status', 'slot_x', 'slot_y')->where("slot_x", $slot_arr[$i][$j]['x'])->where("slot_y", $slot_arr[$i][$j]['y'])->with('activations')->first();
                if ($slot_customer) {
                    $slot_arr[$i][$j]['data'] = $slot_customer;
                } else {
                    $slot_arr[$i][$j]['data'] = '';
                }
            }
        }
        return response()->json([
            'success' => true,
            'slots' => $slot_arr,
            'prev_x' => $slot_prev_x,
            'prev_y' => $slot_prev_y,
            'status' => $yu,
        ]);
    }

    public function loginSwitch()
    {
        $user = CustomerApi::where('id', request('id'))
            ->where('type', 'member')
            ->where('status', '!=', 'close')
            ->with(['activations', 'refferal', 'provinces', 'city'])
            ->first();
        if (!empty($user)) {
            $user->ref_link = "https://admin.belogherbal.com/member?ref=" . Hashids::encode($user->id);
            return response()->json([
                'success' => true,
                'user' => $user,
            ]);
        } else {
            $message = 'Email & Password yang Anda masukkan salah. Salah memasukkan Email & Password lebih dari 3x maka Account akan otomatis di blokir.';
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 401);
        }
    }

    public function membersHu(Request $request)
    {
        try {
            if ($request->exc != '') {
                $members = CustomerApi::select('*')->where('owner_id', $request->owner_id)->where('ref_bin_id', '>', 0)->where('id', '!=', $request->exc)->get();
            } else {
                $members = CustomerApi::select('*')->where('owner_id', $request->owner_id)->where('ref_bin_id', '>', 0)->get();
            }
            $owner = CustomerApi::select('*')->where('id', $request->owner_id)->first();
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kosong.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $members,
            'owner' => $owner,
        ]);
    }

    public function logsUpdate($id)
    {
        $logs = LogNotif::find($id);

        $logs->status = 'read';
        $logs->save();
        return response()->json([
            'success' => true,
            'message' => 'Update Log Status is success.',
        ]);

    }

    public function logsUnread(Request $request)
    {
        $logs = LogNotif::where('customers_id', $request->customers_id)
            ->where('status', 'unread')
            ->get();
        return response()->json([
            'success' => true,
            'count' => $logs->count(),
        ]);
    }

    public function logs(Request $request)
    {
        $logs = LogNotif::where('customers_id', $request->customers_id)
            ->orderBy("id", "desc")
            ->get();
        if (!empty($logs)) {
            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Log is empty',
            ], 401);
        }
    }

    public function upImg($id, Request $request)
    {
        $member = Member::find($id);
        $img_path = "/images/users";
        if ($request->img != null) {
            $resource = $request->img;
            $name = strtolower($member->code);
            // $img_nama=$request->img->filename;
            // $filename_arr = explode(".", $filename);
            // $filename_count=count($filename_arr);
            // //return $img_nama;
            // $file_ext=$filename_arr[$filename_count-1];
            $file_ext = $request->img->extension();
            $name = str_replace(" ", "-", $name);
            $img_name = $img_path . "/" . $name . "-" . $member->id . "." . $file_ext;

            //unlink old
            $basepath = str_replace("laravel-youna", "public_html/youna.belogherbal.com", \base_path());
            $resource->move($basepath . $img_path, $img_name);
            $member->img = $img_name;
            $member->save();
            $member_data = CustomerApi::with('activations')->where('id', $id)->first();
            return response()->json([
                'success' => true,
                'message' => 'Update Image Profile is Success.',
                'data' => $member_data,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Image is null',
            ], 401);
        }
    }

    public function upImgRJS($id, Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Update Image Profile is Success.',
            'data' => $request,
        ]);
    }

    public function resetUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 401);
        }

        $user = Member::where('email', $request->input('email'))->first();

        if (empty($user)) {
            $message = 'Reset gagal, Email tidak dikenali.';
            $status = false;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        } else {
            $password = passw_gnr(7);
            $password_ency = bcrypt($password);
            $user->password = $password_ency;
            $user->save();
            foreach ($user as $key => $value) {
                $user->password_raw = $password;
            }
            Mail::to($request->input('email'))->send(new ResetEmail($user));
            $message = 'Reset berhasil, Password baru telah terkirim ke Email.';
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }
    }

    public function members(Request $request)
    {
        try {
            if (isset($request->page)) {
                $members = CustomerApi::select('*')->FilterInput()->where('status', '!=', 'closed')->where(function ($qry) {
                    $qry->where('ref_bin_id', '>', 0)
                        ->orWhere('type', 'agent');
                })
                    ->paginate(10, ['*'], 'page', $request->page);
            } else {
                $members = CustomerApi::select('*')->where('status', '!=', 'closed')->where('ref_bin_id', '>', 0)->where(function ($qry) {
                    $qry->where('ref_bin_id', '>', 0)
                        ->orWhere('type', 'agent');
                })
                    ->get();
            }
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kosong.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    public function agentlist(Request $request)
    {
        try {
            if (isset($request->page)) {
                $members = CustomerApi::select('*')->where('type', 'agent')->where('status', 'active')->FilterInput()
                    ->paginate(10, ['*'], 'page', $request->page);
            } else {
                $members = CustomerApi::select('*')
                    ->get();
            }
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kosong.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    public function downlineTree(Request $request)
    {
        try {
            $down_arr = array();
            $data = $this->downline_tree($request->ref_bin_id, $down_arr);
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kosong.',
            ], 401);
        }
    }

    public function membersPagination(Request $request)
    {
        try {
            if (isset($request->page)) {
                if (isset($request->filter) && $request->filter != '') {
                    $members = CustomerApi::select('*')
                        ->where('name', 'LIKE', '%' . $request->filter . '%')
                        ->paginate($request->per_page, ['*'], 'page', $request->page);
                } else {
                    $members = CustomerApi::select('*')
                        ->paginate($request->per_page, ['*'], 'page', $request->page);
                }
            } else {
                if (isset($request->filter) && $request->filter != '') {
                    $members = CustomerApi::select('*')
                        ->where('name', 'LIKE', '%' . $request->filter . '%')
                        ->get();
                } else {
                    $members = CustomerApi::select('*')
                        ->get();
                }
            }
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kosong.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $members,
            'filter' => $request->filter,
        ]);
    }

    public function downlineAgent($id)
    {
        $user = CustomerApi::where('ref_id', $id)
            ->where('status', 'active')
            ->with('activations')
            ->with('refferal')
            ->orderBy('activation_at', 'ASC')
            ->get();
        if (!empty($user)) {
            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data is empty.',
            ], 401);
        }
    }

    public function downline($id)
    {
        $user = CustomerApi::where('ref_bin_id', $id)
            ->where('status', 'active')
            ->with('activations')
            ->with('refferal')
            ->orderBy('activation_at', 'ASC')
            ->get();
        if (!empty($user)) {
            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data is empty.',
            ], 401);
        }
    }

    public function membershow(Request $request)
    {
        $user = CustomerApi::where('phone', $request->phone)->first();
        if (!empty($user)) {
            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Phone Number',
            ], 401);
        }
    }

    public function membershowid(Request $request)
    {
        $user = CustomerApi::where('id', $request->id)->first();
        if (!empty($user)) {
            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Member ID',
            ], 401);
        }
    }

    public function login()
    {

        $user = CustomerApi::where('email', request('email'))
            ->where('type', 'member')
            ->where('status', '!=', 'close')
            ->with(['activations', 'refferal', 'provinces', 'city'])
            ->first();
        if (!empty($user)) {
            if ((Hash::check(request('password'), $user->password)) && ($user->status_block == 0)) {
                Auth::login($user);
                if (request('id_onesignal') != null) {
                    $user->id_onesignal = request('id_onesignal');
                    $user->save();
                }
                $success['token'] = Auth::user()->createToken('authToken')->accessToken;
                //After successfull authentication, notice how I return json parameters
                $user->ref_link = "https://admin.belogherbal.com/member?ref=" . Hashids::encode($user->id);
                //get child
                $users_hu = CustomerApi::select('*')->where('owner_id', $user->id)->where('ref_bin_id', '>', 0)->get();
                $hu = count($users_hu);
                //get status upline
                $status_list_upline = $this->status_list_upline($user->slot_x, $user->slot_y);
                //return
                return response()->json([
                    'success' => true,
                    'token' => $success,
                    'user' => $user,
                    'hu' => $hu,
                    'status_up' => $status_list_upline,
                ]);
            } else {
                //if authentication is unsuccessfull, notice how I return json parameters
                $message = 'Email & Password yang Anda masukkan salah. Salah memasukkan Email & Password lebih dari 3x maka Account akan otomatis di blokir.';
                if ($user->status_block == 1) {
                    $message = 'Your Account is temporary blocked.';
                }
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 401);
            }} else {
            $message = 'Email & Password yang Anda masukkan salah. Salah memasukkan Email & Password lebih dari 3x maka Account akan otomatis di blokir.';
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 401);
        }
    }

    public function loginagent()
    {

        $user = CustomerApi::where('email', request('email'))
            ->where('type', 'agent')
            ->with(['provinces', 'city'])
            ->first();
        if ($user && ((Hash::check(request('password'), $user->password)) && ($user->status_block == 0))) {
            Auth::login($user);
            if (request('id_onesignal') != null) {
                $user->id_onesignal = request('id_onesignal');
                $user->save();
            }
            $success['token'] = Auth::user()->createToken('authToken')->accessToken;
            //After successfull authentication, notice how I return json parameters
            return response()->json([
                'success' => true,
                'token' => $success,
                'user' => $user,
            ]);
        } else {
            //if authentication is unsuccessfull, notice how I return json parameters
            $message = 'Email & Password yang Anda masukkan salah. Salah memasukkan Email & Password lebih dari 3x maka Account akan otomatis di blokir.';
            if ($user && ($user->status_block == 1)) {
                $message = 'Your Account is temporary blocked.';
            }
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 401);
        }
    }

    public function userBlock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 401);
        }

        $user = Member::where('email', $request->input('email'))->first();
        if (empty($user)) {
            $message = 'Update Gagal.';
            $status = false;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        } else {
            $user->status_block = '1';
            $user->save();
            //response
            $message = 'Update Berhasil.';
            $status = true;
            return response()->json([
                'status' => $status,
                'message' => $message,
                'data' => $user,
            ]);
        }
    }

    /**
     * Register api.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateprofile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            //'phone' => 'required|unique:customers|regex:/(0)[0-9]{10}/',
            'phone' => 'required',
            //'email' => 'required|email|unique:customers',
            'email' => 'required|email',
            'password' => 'required',
            'address' => 'required',
            'lat' => 'required',
            'lng' => 'required',
            'province_id' => 'required',
            'city_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 401);
        }
        $input = $request->all();
        // $member = CustomerApi::with('activations')->where('id', $input['id'])->first();
        $member = CustomerApi::with(['activations', 'provinces', 'city'])->where('id', $input['id'])->first();
        $password_raw = $input['password'];
        $input['password'] = bcrypt($input['password']);
        $member->password = $input['password'];
        $member->name = $input['name'];
        $member->phone = $input['phone'];
        $member->email = $input['email'];
        $member->address = $input['address'];
        $member->lat = $input['lat'];
        $member->lng = $input['lng'];
        $member->province_id = $input['province_id'];
        $member->city_id = $input['city_id'];
        try {
            $member->save();
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate Email or Phone Number.',
            ], 401);
        }

        foreach ($member as $key => $value) {
            $member->password_raw = $password_raw;
        }
        $member->ref_link = "https://admin.belogherbal.com/member?ref=" . Hashids::encode($member->id);
        Mail::to($request->input('email'))->send(new MemberEmail($member));
        return response()->json([
            'success' => true,
            'data' => $member,
        ]);
    }

    /**
     * Register api.
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            //'phone' => 'required|unique:customers|regex:/(0)[0-9]{10}/',
            'phone' => 'required',
            //'email' => 'required|email|unique:customers',
            'email' => 'required|email',
            'password' => 'required',
            'register' => 'required',
            'address' => 'required',
            'province_id' => 'required',
            'city_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 401);
        }
        $input = $request->all();
        $last_code = $this->mbr_get_last_code();
        $code = acc_code_generate($last_code, 8, 3);
        $password_raw = $input['password'];
        $input['password'] = bcrypt($input['password']);
        $input['code'] = $code;
        $input['type'] = 'member';
        $input['status'] = 'pending';
        if (!isset($input['customers_id'])) {
            $ref_def_id = Member::select('id')
                ->Where('def', '=', '1')
                ->get();
            $referals_id = $ref_def_id[0]->id;
            $parent_id = $this->set_parent($referals_id);
            $input['parent_id'] = $parent_id;
            $input['ref_id'] = $referals_id;
            $input['ref_bin_id'] = $referals_id;
        }

        //check ref_id
        $ref_row = Member::find($input['ref_bin_id']);
        if ($ref_row->status != 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Register Gagal, Status Referal belum Activasi.',
            ], 401);
        }

        try {
            $user = CustomerApi::create($input);
            $success['token'] = $user->createToken('appToken')->accessToken;
            foreach ($user as $key => $value) {
                $user->password_raw = $password_raw;
            }
            Mail::to($request->input('email'))->send(new MemberEmail($user));
            return response()->json([
                'success' => true,
                'token' => $success,
                'user' => $user,
            ]);
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate Email or Phone Number.',
            ], 401);
        }
    }

    public function registerDownlineCustPackage(Request $request)
    {
        if (env('MEMBER_ACTIV') == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Register Gagal, Status Activasi Dinonaktifkan Untuk Beberapa Waktu.',
            ], 401);
        } else {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'phone' => 'required|unique:customers',
                //'phone' => 'required',
                'email' => 'required|email|unique:customers',
                //'email' => 'required|email',
                'password' => 'required',
                'register' => 'required',
                'address' => 'required',
                'ref_id' => 'required',
                'agents_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors(),
                ], 401);
            }

            //check ref_id
            $ref_row = Member::find($request->ref_id);
            if ($ref_row->status != 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Register Gagal, Status Referal belum Activasi.',
                ], 401);
            }

            $memberDownline = event(new MemberDownline($request));
            if ($memberDownline[0]->status == 1) {
                return response()->json([
                    'success' => true,
                    'message' => $memberDownline[0]->message,
                    'data' => $memberDownline[0]->data,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $memberDownline[0]->message,
                    'data' => $memberDownline[0]->data,
                ], 401);
            }
        }
    }

    /**
     * Register api.
     *
     * @return \Illuminate\Http\Response
     */
    public function registerAgent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            //'phone' => 'required|unique:customers|regex:/(0)[0-9]{10}/',
            'phone' => 'required',
            //'email' => 'required|email|unique:customers',
            'email' => 'required|email',
            'password' => 'required',
            'register' => 'required',
            'address' => 'required',
            'province_id' => 'required',
            'city_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 401);
        }
        $input = $request->all();
        $last_code = $this->get_last_code('agent');
        $code = acc_code_generate($last_code, 8, 3);
        $password_raw = $input['password'];
        $input['password'] = bcrypt($input['password']);
        $input['code'] = $code;
        $input['type'] = 'agent';
        $input['status'] = 'pending';
        $input['parent_id'] = 0;
        $input['ref_id'] = 0;

        try {
            $user = CustomerApi::create($input);
            $member = $user;
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate Email or Phone Number.',
            ], 401);
        }

        $success['token'] = $user->createToken('appToken')->accessToken;
        foreach ($user as $key => $value) {
            $user->password_raw = $password_raw;
        }
        Mail::to($request->input('email'))->send(new MemberEmail($user));
        return response()->json([
            'success' => true,
            'token' => $success,
            'user' => $user,
        ]);
    }

    public function upgradeCustPackage(Request $request)
    {
        if (env('MEMBER_ACTIV') == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Register Gagal, Status Activasi Dinonaktifkan Untuk Beberapa Waktu.',
            ], 401);
        } else {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'agents_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors(),
                ], 401);
            } else {
                $memberUpgrade = event(new MemberUpgrade($request));
                if ($memberUpgrade[0]->status == 1) {
                    return response()->json([
                        'success' => true,
                        'message' => $memberUpgrade[0]->message,
                        'data' => $memberUpgrade[0]->data,
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $memberUpgrade[0]->message,
                    ], 401);
                }
            }
        }
    }

    public function activateCustPackage(Request $request)
    {
        if (env('MEMBER_ACTIV') == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Register Gagal, Status Activasi Dinonaktifkan Untuk Beberapa Waktu.',
            ], 401);
        } else {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'agents_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors(),
                ], 401);
            } else {
                $memberActivated = event(new MemberActivated($request));
                if ($memberActivated[0]->status == 1) {
                    return response()->json([
                        'success' => true,
                        'message' => $memberActivated[0]->message,
                        'data' => $memberActivated[0]->data,
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $memberActivated[0]->message,
                    ], 401);
                }
            }
        }
    }

    public function activateAgent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'package_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 401);
        } else {
            //set member
            $member = Member::find($request->input('id'));
            //get point member
            $points_id = 1;
            $points_fee_id = 4;
            $points_debit = OrderPoint::where('customers_id', '=', $request->input('id'))
                ->where('type', '=', 'D')
                ->where('points_id', '=', 1)
                ->where('status', '=', 'onhand')
                ->sum('amount');
            $points_credit = OrderPoint::where('customers_id', '=', $request->input('id'))
                ->where('type', '=', 'C')
                ->where('points_id', '=', 1)
                ->where('status', '=', 'onhand')
                ->sum('amount');
            $points_balance = $points_debit - $points_credit;

            //get package price & cogs
            $package = Product::select('price', 'cogs', 'bv')
                ->where('id', '=', $request->input('package_id'))
                ->get();
            $package = json_decode($package, false);
            $cogs_total = $package[0]->cogs;
            $bv_total = $package[0]->bv;
            $total = $package[0]->price;
            $profit = $total - $cogs_total;

            //compare total to point belanja
            if ($points_balance >= $total && $member->status == 'pending') {
                //init
                $register = date("Y-m-d");
                $memo = 'Aktivasi Agen ' . $member->code . "-" . $member->name;
                /* proceed ledger */
                $data = ['register' => $register, 'title' => $memo, 'memo' => $memo];
                $ledger = Ledger::create($data);
                $ledger_id = $ledger->id;
                //set ledger entry arr
                $acc_inv_stock = '20';
                $acc_sale = '44';
                $acc_exp_cogs = '45';
                $acc_points = '67'; //utang poin
                $total_pay = $total;
                $accounts = array($acc_inv_stock, $acc_exp_cogs, $acc_sale);
                $amounts = array($cogs_total, $cogs_total, $total);
                $types = array('C', 'D', 'C');
                //if agent get cashback
                $customer_row = CustomerApi::select('*')
                    ->Where('id', '=', $request->input('id'))
                    ->get();
                if ($customer_row[0]->type == 'agent') {
                    //get cashback 01
                    $acc_disc = 68;
                    $acc_res_cashback = 70;
                    //CBA 1
                    $networkfee_row = NetworkFee::select('*')
                        ->Where('code', '=', 'CBA01')
                        ->get();
                    //BVCV
                    $bvcv_row = NetworkFee::select('*')
                        ->Where('code', '=', 'BVCV')
                        ->get();
                    $cba1 = (($networkfee_row[0]->amount) / 100) * $total;
                    $bvcv = (($bvcv_row[0]->amount) / 100) * $bv_total;
                    $bv_nett = $bv_total - $bvcv;
                    $round_profit = $total - $cogs_total - $bv_total;
                    $profit = $bvcv + $round_profit; // (set to ledger profit)
                    $amount_disc = $bv_nett; // (potongan penjualan)
                    $amount_res_cashback = $amount_disc - $cba1; //(reserve/cadangan)
                    $total_pay = $total - $cba1;
                    //$acc_points = '67';
                    //push array jurnal
                    array_push($accounts, $acc_disc, $acc_res_cashback, $acc_points);
                    array_push($amounts, $amount_disc, $amount_res_cashback, $total_pay);
                    array_push($types, "D", "C", "D");
                }
                //ledger entries
                for ($account = 0; $account < count($accounts); $account++) {
                    if ($accounts[$account] != '') {
                        $ledger->accounts()->attach($accounts[$account], ['entry_type' => $types[$account], 'amount' => $amounts[$account]]);
                    }
                }

                /*update member */
                $member->status = 'active';
                $member->save();
                /* set order, order products, order details (inventory stock), order points */
                //set def
                $ref_def_id = CustomerApi::select('id')
                    ->Where('def', '=', '1')
                    ->get();
                $owner_def = $ref_def_id[0]->id;
                $customers_id = $request->input('id');
                $warehouses_id = 1;
                //set order
                $last_code = $this->get_last_code('order');
                $order_code = acc_code_generate($last_code, 8, 3);
                $data = array('memo' => $memo, 'total' => $total, 'type' => 'sale', 'status' => 'approved', 'ledgers_id' => $ledger_id, 'customers_id' => $customers_id, 'payment_type' => 'point', 'code' => $order_code, 'register' => $register);
                $order = Order::create($data);
                //set order products
                $order->products()->attach($request->input('package_id'), ['quantity' => 1, 'price' => $total]);
                //set order order details (inventory stock)
                $package_items = Package::with('products')
                    ->where('id', $request->input('package_id'))
                    ->get();
                $package_items = json_decode($package_items, false);
                $package_items = $package_items[0]->products;
                //loop items
                foreach ($package_items as $key => $value) {
                    $order->productdetails()->attach($value->id, ['quantity' => $value->pivot->quantity, 'type' => 'D', 'status' => 'onhand', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                    $order->productdetails()->attach($value->id, ['quantity' => $value->pivot->quantity, 'type' => 'C', 'status' => 'onhand', 'warehouses_id' => $warehouses_id, 'owner' => $owner_def]);
                }
                //set trf points from customer to Usdha Bhakti
                // $order->points()->attach($points_id, ['amount' => $total_pay, 'type' => 'D', 'status' => 'onhand', 'memo' => 'Penambahan Poin dari ' . $memo, 'customers_id' => $owner_def]);
                $order->points()->attach($points_id, ['amount' => $total_pay, 'type' => 'C', 'status' => 'onhand', 'memo' => 'Pemotongan Poin dari ' . $memo, 'customers_id' => $customers_id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Aktivasi Agen Berhasil!',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Poin atau Stok Barang Tidak Mencukupi! atau Member sudah aktif! Poin Balance: ' . $points_balance . " Total package: " . $total . " Agen Satus: " . $member->status,
                ], 401);
            }
        }

    }

    public function logout(Request $res)
    {
        if (Auth::user()) {
            $user = Auth::user()->token();
            $user->revoke();

            return response()->json([
                'success' => true,
                'message' => 'Logout successfully',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unable to Logout',
            ]);
        }
    }

    public function resellerAgents()
    {
        $agents = CustomerApi::select('*')
            ->where('type', 'agent')
            ->where('status', 'active')
            ->where('agent_type', 'reseller')
            ->get();

        return $agents;
    }

    public function agents()
    {
        $agents = CustomerApi::select('*')
            ->where('type', 'agent')
            ->where('status', 'active')
            ->where('agent_type', '!=', 'reseller')
            ->get();

        return $agents;
    }

    public function agentsOpen()
    {
        $agents = CustomerApi::select('*')
            ->where('type', 'agent')
            ->where('status', 'active')
            ->get();

        // return $agents;

        if (is_null($agents)) {
            $message = 'Data not found.';
            $status = false;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        } else {
            $message = 'Data retrieved successfully.';
            $status = true;
            return response()->json([
                'status' => $status,
                'message' => $message,
                'data' => $agents,
            ]);
        }
    }

    public function agentshow($id)
    {
        $agent = CustomerApi::find($id);

        //Check if agent found or not.
        if (is_null($agent)) {
            $message = 'Product not found.';
            $status = false;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        }
        $message = 'Product retrieved successfully.';
        $status = true;
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $agent,
        ]);
    }
    public function location()
    {
        try {
            $province = Province::all();
            $city = City::all();

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'province' => $province,
                'city' => $city,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 404,
                'message' => 'failed',
                'data' => $th,
            ]);
        }
    }

}
