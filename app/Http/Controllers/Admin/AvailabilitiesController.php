<?php

namespace App\Http\Controllers\Admin;

use App\ClinicCustomer;
use App\Availability;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\TraitModel;
use App\Customer;
use App\Day;

class AvailabilitiesController extends Controller
{
    use TraitModel; 
    
    public function index(Request $request)
    {
        abort_unless(\Gate::allows('availability_access'), 403);

        // ajax
        if ($request->ajax()) {

            $query = Availability::with('days')->with('clinicCustomers')->FilterClinicCustomer()->get();

            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'availability_show';
                $editGate = 'availability_edit';
                $deleteGate = 'availability_delete';
                $crudRoutePart = 'availabilities';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('clinic', function ($row) {
                return $row->clinicCustomers->clinics ? $row->clinicCustomers->clinics->code." - ".$row->clinicCustomers->clinics->name : "";
            });

            $table->editColumn('expert', function ($row) {
                return $row->clinicCustomers->customers ? $row->clinicCustomers->customers->code." - ".$row->clinicCustomers->customers->name : "";
            });

            $table->editColumn('day_id', function ($row) {
                return $row->days->name ? $row->days->name : "";
            });

            $table->editColumn('start', function ($row) {
                return $row->start ? $row->start : "";
            });

            $table->editColumn('end', function ($row) {
                return $row->end ? $row->end : "";
            });

            $table->rawColumns(['actions', 'placeholder', 'clinic']);

            // $table->addIndexColumn();
            return $table->make(true);
        }
        //def view
        $clinic_customer = ClinicCustomer::where('id', $request->clinic_customer_id)->with('clinics')->with('customers')->first();
        
        return view('admin.availabilities.index', compact('clinic_customer'));
    }

    public function create(Request $request)
    {
        abort_unless(\Gate::allows('availability_create'), 403);

        //days
        $days = Day::get();
        
        //clinic customer id
        $clinic_customer_id = $request->clinic_customer_id;

        return view('admin.availabilities.create', compact('days', 'clinic_customer_id'));
    }

    public function store(Request $request)
    {
        abort_unless(\Gate::allows('availability_create'), 403);

        //check if exist
        
        $start = $request->start . ":00";
        $end = $request->end . ":00";
        $data = ['clinic_customer_id' => $request->clinic_customer_id, 'day_id' => $request->day_id, 'start' => $request->start, 'end' => $request->end];
        $availability = Availability::create($data);

        return redirect()->route('admin.availabilities.index', ['clinic_customer_id'=>$request->clinic_customer_id]);
    }

    public function edit(Availability $availability)
    {
        abort_unless(\Gate::allows('availability_edit'), 403);
        //days
        $days = Day::get();

        return view('admin.availabilities.edit', compact('availability','days'));
    }

    public function update(Request $request, Availability $availability)
    {
        abort_unless(\Gate::allows('availability_edit'), 403);

        //check if exist
        $start = $request->start . ":00";
        $end = $request->end . ":00";
        $data = ['clinic_customer_id' => $request->clinic_customer_id, 'day_id' => $request->day_id, 'start' => $request->start, 'end' => $request->end];
        $availability->update($data);

        return redirect()->route('admin.availabilities.index', ['clinic_customer_id'=>$request->clinic_customer_id]);

    }

    public function show(Availability $availability)
    {
        abort_unless(\Gate::allows('availability_show'), 403);
        //return $availability;

        return view('admin.availabilities.show', compact('availability'));
    }

    public function destroy(Availability $availability)
    {
        abort_unless(\Gate::allows('availability_delete'), 403);
        
        $availability->delete();

        return back();
    }

    public function massDestroy(Request $request)
    {
        //Clinic::whereIn('id', request('ids'))->delete();

        return response(null, 204);
    }
}
