<?php

namespace App\Http\Controllers\Admin;

use App\Expert;
use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyCustomerRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Traits\TraitModel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class ExpertsController extends Controller
{
    use TraitModel;    

    public function index(Request $request)
    {
        abort_unless(\Gate::allows('expert_access'), 403);

        if ($request->ajax()) {
            $query = Expert::where('customers.type', '=', 'expert')->FilterInput()->get();
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'expert_show';
                $editGate = 'expert_edit';
                $deleteGate = 'expert_delete';
                $crudRoutePart = 'experts';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('code', function ($row) {
                return $row->code ? $row->code : "";
            });

            $table->editColumn('register', function ($row) {
                return $row->register ? $row->register : "";
            });

            $table->editColumn('name', function ($row) {
                return $row->name ? $row->name : "";
            });

            $table->editColumn('address', function ($row) {
                return $row->address ? $row->address : "";
            });

            $table->editColumn('phone', function ($row) {
                return $row->phone ? $row->phone : "";
            });

            $table->editColumn('status', function ($row) {
                return $row->status ? $row->status : "";
            });

            $table->rawColumns(['actions', 'placeholder']);

            $table->addIndexColumn();

            return $table->make(true);
        }

        // $customers = Expert::where('type', '!=', 'member')
        // ->where('def', '=', '0')
        // ->get();

        $customers = Expert::where('customers.type', '=', 'expert')->get();

        return view('admin.experts.index', compact('customers'));
    }

    public function create()
    {
        abort_unless(\Gate::allows('expert_create'), 403);

        $last_code = $this->get_last_code('expert');
        $code = acc_code_generate($last_code, 8, 3);

        return view('admin.experts.create', compact('code'));
    }

    public function store(StoreCustomerRequest $request)
    {
        abort_unless(\Gate::allows('expert_create'), 403);

        $password_def = bcrypt('2579');
        $data = array_merge($request->all(), ['status' => 'active', 'password' => $password_def]);
        $customer = Expert::create($data);

        return redirect()->route('admin.experts.index');
    }

    public function edit(Expert $expert)
    {
        abort_unless(\Gate::allows('expert_edit'), 403);

        return view('admin.experts.edit', compact('expert'));
    }

    public function update(UpdateCustomerRequest $request, Expert $expert)
    {
        abort_unless(\Gate::allows('expert_edit'), 403);

        $expert->update($request->all());

        return redirect()->route('admin.experts.index');
    }

    public function show(Expert $expert)
    {
        abort_unless(\Gate::allows('expert_show'), 403);

        return view('admin.experts.show', compact('expert'));
    }

    public function destroy(Expert $expert)
    {
        abort_unless(\Gate::allows('expert_delete'), 403);

        //check if related        
        $expert->delete();

        return back();
    }

    public function massDestroy(MassDestroyCustomerRequest $request)
    {
        // Expert::whereIn('id', request('ids'))->delete();

        // return response(null, 204);
        return back()->withError('Gagal Delete, Member Active!');
    }
}
