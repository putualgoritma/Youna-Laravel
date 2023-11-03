<?php

namespace App\Http\Controllers\Admin;

use App\Clinic;
use App\ClinicCustomer;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\TraitModel;
use App\Customer;

class ClinicCustomerController extends Controller
{
    use TraitModel; 
    
    public function index(Request $request)
    {
        abort_unless(\Gate::allows('clinic_customer_access'), 403);

        // ajax
        if ($request->ajax()) {

            $query = ClinicCustomer::FilterClinic()->with('clinics')->with('customers')->FilterClinic()->get();

            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'clinic_customer_show';
                $editGate = 'clinic_customer_edit';
                $deleteGate = 'clinic_customer_delete';
                $crudRoutePart = 'clinic-customer';

                return view('partials.datatablesClinicCustomer', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('clinic_code', function ($row) {
                return $row->code ? $row->code : "";
            });

            $table->editColumn('clinic_name', function ($row) {
                return $row->clinics->name ? $row->clinics->code." - ".$row->clinics->name : "";
            });

            $table->editColumn('customer_code', function ($row) {
                return $row->customers->code ? $row->customers->code : "";
            });

            $table->editColumn('customer_name', function ($row) {
                return $row->customers->name ? $row->customers->name : "";
            });

            $table->editColumn('customer_address', function ($row) {
                return $row->customers->address ? $row->customers->address : "";
            });

            $table->rawColumns(['actions', 'placeholder', 'clinic']);

            // $table->addIndexColumn();
            return $table->make(true);
        }
        //def view
        $clinics = ClinicCustomer::FilterClinic()->get();
        $clinic = Clinic::find($request->clinic_id);

        return view('admin.clinic-customer.index', compact('clinics','clinic'));
    }

    public function create(Request $request)
    {
        abort_unless(\Gate::allows('clinic_customer_create'), 403);

        //get expert
        $experts = Customer::where('type', 'expert')
        ->get();
        //get code
        $last_code = $this->get_last_code('clinic_customer');
        $code = acc_code_generate($last_code, 8, 3);
        //clinic id
        $clinic_id = $request->clinic_id;

        return view('admin.clinic-customer.create', compact('code','experts', 'clinic_id'));
    }

    public function store(Request $request)
    {
        abort_unless(\Gate::allows('clinic_customer_create'), 403);

        //check if exist
        $clinicCustomerExist = ClinicCustomer::where('clinic_id', $request->clinic_id)->where('customer_id', $request->customer_id)->first();
        
        if(!$clinicCustomerExist){
        $data = array_merge($request->all());
        $clinicCustomer = ClinicCustomer::create($data);
        }

        return redirect()->route('admin.clinic-customer.index', ['clinic_id'=>$request->clinic_id]);
    }

    public function edit(ClinicCustomer $clinicCustomer)
    {
        abort_unless(\Gate::allows('clinic_customer_edit'), 403);
        //get expert
        $experts = Customer::where('type', 'expert')->get();

        return view('admin.clinic-customer.edit', compact('clinicCustomer','experts'));
    }

    public function update(Request $request, ClinicCustomer $clinicCustomer)
    {
        abort_unless(\Gate::allows('clinic_customer_edit'), 403);

        //check if exist
        $clinicCustomerExist = ClinicCustomer::where('clinic_id', $request->clinic_id)->where('customer_id', $request->customer_id)->where('id', '!=', $request->id)->first();
        
        if(!$clinicCustomerExist){
        //update clinics
        $data = $request->all();
        $clinicCustomer->update($data);  
        }      

        return redirect()->route('admin.clinic-customer.index', ['clinic_id'=>$request->clinic_id]);

    }

    public function show(ClinicCustomer $clinicCustomer)
    {
        abort_unless(\Gate::allows('clinic_customer_show'), 403);
        //return $clinicCustomer;

        return view('admin.clinic-customer.show', compact('clinicCustomer'));
    }

    public function destroy(ClinicCustomer $clinicCustomer)
    {
        abort_unless(\Gate::allows('clinic_customer_delete'), 403);
        
        $clinicCustomer->delete();

        return back();
    }

    public function massDestroy(Request $request)
    {
        //Clinic::whereIn('id', request('ids'))->delete();

        return response(null, 204);
    }
}
