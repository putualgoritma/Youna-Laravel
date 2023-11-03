<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\ClinicCustomer;
use App\Customer;
use App\Http\Controllers\Controller;
use App\Traits\TraitModel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ClinicCustomerApiController extends Controller
{
    use TraitModel;

    public function index(Request $request)
    {
        try {
            $clinicCustomer = ClinicCustomer::FilterClinic()->with('clinics')->with('customers')->FilterClinic()->get();
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kosong.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $clinicCustomer,
        ]);
    }

    public function create(Request $request)
    {
        //get expert
        $experts = Customer::where('type', 'expert')
            ->get();
        //get code
        $last_code = $this->get_last_code('clinic_customer');
        $code = acc_code_generate($last_code, 8, 3);
        //clinic id
        $clinic_id = $request->clinic_id;
    }

    public function store(Request $request)
    {
        //check if exist
        $clinicCustomerExist = ClinicCustomer::where('clinic_id', $request->clinic_id)->where('customer_id', $request->customer_id)->first();

        if (!$clinicCustomerExist) {
            $data = array_merge($request->all());
            $clinicCustomer = ClinicCustomer::create($data);
        }
    }

    public function edit(ClinicCustomer $clinicCustomer)
    {
        //get expert
        $experts = Customer::where('type', 'expert')->get();
    }

    public function update(Request $request, ClinicCustomer $clinicCustomer)
    {
        //check if exist
        $clinicCustomerExist = ClinicCustomer::where('clinic_id', $request->clinic_id)->where('customer_id', $request->customer_id)->where('id', '!=', $request->id)->first();

        if (!$clinicCustomerExist) {
            //update clinics
            $data = $request->all();
            $clinicCustomer->update($data);
        }

    }

    public function show(ClinicCustomer $clinicCustomer)
    {
        
    }

    public function destroy(ClinicCustomer $clinicCustomer)
    {
        $clinicCustomer->delete();
    }
}
