<?php

namespace App\Classes;

use App\ActivationType;
use App\CustomerApi;
use App\OrderPoint;
use App\Traits\TraitModel;

class MemberClass
{
    use TraitModel;
    public $member;

    public function __construct($memberID = 0)
    {
        if ($memberID > 0) {
            $this->member = CustomerApi::select('customers.*', 'activation_type.type as activation_type_name')
                ->where('customers.id', $memberID)
                ->leftjoin('activation_type', 'activation_type.id', '=', 'customers.activation_type_id')
                ->with(['activations', 'refferal', 'provinces', 'city'])
                ->first();
        }
    }

    public function pointBalance()
    {
        $member_id = $this->member->id;
        $points_debit = OrderPoint::where('customers_id', '=', $member_id)
            ->where('type', '=', 'D')
            ->where('status', '=', 'onhand')
            ->where('points_id', '=', 1)
            ->sum('amount');
        $points_credit = OrderPoint::where('customers_id', '=', $member_id)
            ->where('type', '=', 'C')
            ->where('status', '=', 'onhand')
            ->where('points_id', '=', 1)
            ->sum('amount');
        $points_balance = $points_debit - $points_credit;
        return $points_balance;
    }

    public function memberDuplicateStatus($request)
    {
        $member = CustomerApi::select('id')
            ->where('email', '=', $request->email)
            ->where('phone', '=', $request->phone)
            ->where('status', 'active')
            ->get();
        if (count($member) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function memberCreate($request)
    {
        $input = array();
        //check duplicate email or phone
        if ($this->memberDuplicateStatus($request)) {
            $response = array();
            $response['message'] = "Gagal, Duplicate Email or Phone Number.";
            $response['status'] = false;
            return (object) $response;
        } else {
            $input = ['register' => $request->register,
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'ref_id' => $request->ref_id,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
                'deleted_at' => $request->deleted_at,
                'status_block' => $request->status_block,
                'slot_x' => $request->slot_x,
                'slot_y' => $request->slot_y,
                'owner_id' => $request->owner_id];
            $last_code = $this->mbr_get_last_code();
            $code = acc_code_generate($last_code, 8, 3);
            $password_raw = $request['password'];
            $input['password'] = bcrypt($request['password']);
            $input['code'] = $code;
            $input['type'] = 'member';
            $input['status'] = 'pending';
            $parent_id = $this->set_parent($request['ref_id']);
            $input['parent_id'] = $parent_id;
            $input['ref_bin_id'] = $request['ref_id'];
            $input['lat'] = 0;
            $input['lng'] = 0;
            $input['province_id'] = 0;
            $input['city_id'] = 0;
            $input['activation_type_id'] = $request['activationtype'];

            $user = CustomerApi::create($input);
            $member = $user;
            //check if 3 HU
            if ($request['type_hu'] == 3) {
                //get slot HU
                $slot_left_x = $input['slot_x'] + 1;
                $slot_right_x = $input['slot_x'] + 1;
                $slot_left_y = ($input['slot_y'] * 2) - 1;
                $slot_right_y = $input['slot_y'] * 2;
                //register L HU
                $last_code = $this->mbr_get_last_code();
                $code = acc_code_generate($last_code, 8, 3);
                $data = ['register' => $input['register'], 'name' => $input['name'] . "-002", 'activation_type_id' => 2, 'type' => 'member', 'status' => 'pending', 'code' => $code, 'parent_id' => $member->id, 'ref_id' => $input['ref_id'], 'owner_id' => $member->id, 'ref_bin_id' => $input['ref_id'], 'slot_x' => $slot_left_x, 'slot_y' => $slot_left_y];
                $hu_l = CustomerApi::create($data);
                //register R HU
                $last_code = $this->mbr_get_last_code();
                $code = acc_code_generate($last_code, 8, 3);
                $data = ['register' => $input['register'], 'name' => $input['name'] . "-003", 'activation_type_id' => 2, 'type' => 'member', 'status' => 'pending', 'code' => $code, 'parent_id' => $member->id, 'ref_id' => $input['ref_id'], 'owner_id' => $member->id, 'ref_bin_id' => $input['ref_id'], 'slot_x' => $slot_right_x, 'slot_y' => $slot_right_y];
                $hu_r = CustomerApi::create($data);
            }
            $response = array();
            $response['data'] = $member;
            $response['message'] = "Aktivasi Member Berhasil!";
            $response['status'] = true;
            return (object) $response;
        }
    }

    public function memberHU($type = '')
    {
        if ($type != '') {
            $customer_hu = CustomerApi::select('customers.id')
                ->leftjoin('activation_type', 'activation_type.id', '=', 'customers.activation_type_id')
                ->where('customers.owner_id', $this->member->id)
                ->where('customers.ref_bin_id', '>', 0)
                ->where('customers.status', 'active')
                ->where('activation_type.type', '=', $type)
                ->get();
        } else {
            $customer_hu = CustomerApi::select('id')
                ->where('owner_id', $this->member->id)
                ->where('ref_bin_id', '>', 0)
                ->where('status', 'active')
                ->get();
        }
        $hu = count($customer_hu);
        return $hu;
    }

    public function memberLegBalance($customer_id = 0, $slot_x = 0, $slot_y = 0)
    {
        //init status
        $status = false;
        //get upline slot
        if ($customer_id > 0) {
            $upline = CustomerApi::select('customers.*')
                ->join('activation_type', 'activation_type.id', '=', 'customers.activation_type_id')
                ->where('customers.id', $customer_id)
                ->where('customers.status', 'active')
                ->where('activation_type.type', '=', 'business')
                ->first();
        } else {
            $upline = CustomerApi::select('customers.*')
                ->join('activation_type', 'activation_type.id', '=', 'customers.activation_type_id')
                ->where('customers.slot_x', $slot_x)
                ->where('customers.slot_y', $slot_y)
                ->where('customers.status', 'active')
                ->where('activation_type.type', '=', 'business')
                ->first();
        }
        //check downline if exist
        if ($upline) {
            $true_count = 0;
            for ($i = 0; $i < 2; $i++) {
                $slot_x = $upline->slot_x + 1;
                $slot_y = ($upline->slot_y * 2) - (1 - $i);
                $downline = CustomerApi::select('customers.id')
                    ->join('activation_type', 'activation_type.id', '=', 'customers.activation_type_id')
                    ->where('customers.slot_x', $slot_x)
                    ->where('customers.slot_y', $slot_y)
                    ->where('customers.status', 'active')
                    ->where('activation_type.type', '=', 'business')
                    ->first();
                if ($downline) {
                    $true_count++;
                }
            }
            if ($true_count == 2) {
                $status = true;
            }
        }
        //return response
        $orderResp['status'] = $status;
        $orderResp['data'] = $upline;
        return (object) $orderResp;
    }

    public function activationType($activationTypeID)
    {
        $activationType = ActivationType::select('type')
            ->where('id', '=', $activationTypeID)
            ->first();
        return $activationType->type;
    }
}
