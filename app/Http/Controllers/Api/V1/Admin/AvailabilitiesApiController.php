<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Availability;
use App\Day;
use App\Http\Controllers\Controller;
use App\Traits\TraitModel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class AvailabilitiesApiController extends Controller
{
    use TraitModel;

    public function index(Request $request)
    {
        try {
            //def view
            $availabilities = Availability::with('days')->with('clinicCustomers')->FilterClinicCustomer()->FilterDateDay()->get();
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kosong.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $availabilities,
        ]);
    }

    public function create(Request $request)
    {
        //days
        $days = Day::get();

        //clinic customer id
        $clinic_customer_id = $request->clinic_customer_id;
    }

    public function store(Request $request)
    {
        //check if exist
        $start = $request->start . ":00";
        $end = $request->end . ":00";
        $data = ['clinic_customer_id' => $request->clinic_customer_id, 'day_id' => $request->day_id, 'start' => $request->start, 'end' => $request->end];
        $availability = Availability::create($data);
    }

    public function edit(Availability $availability)
    {
        //days
        $days = Day::get();
    }

    public function update(Request $request, Availability $availability)
    {
        //check if exist
        $start = $request->start . ":00";
        $end = $request->end . ":00";
        $data = ['clinic_customer_id' => $request->clinic_customer_id, 'day_id' => $request->day_id, 'start' => $request->start, 'end' => $request->end];
        $availability->update($data);
    }


    public function updateStatus(Request $request)
    {
        //check if exist
        $start = $request->start . ":00";
        $end = $request->end . ":00";
        $data = ['clinic_customer_id' => $request->clinic_customer_id, 'day_id' => $request->day_id, 'start' => $request->start, 'end' => $request->end];
        $availability->update($data);
    }

    public function show(Availability $availability)
    {
    }

    public function destroy(Availability $availability)
    {
        $availability->delete();
    }
}
