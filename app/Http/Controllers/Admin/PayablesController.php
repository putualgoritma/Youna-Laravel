<?php

namespace App\Http\Controllers\Admin;

use App\Account;
use App\Payreceivable;
use App\PayreceivableTrs;
use App\Customer;
use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyPayreceivableRequest;
use App\Http\Requests\StorePayreceivableRequest;
use App\Ledger;
use App\Traits\TraitModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use DB;

class PayablesController extends Controller
{
    use TraitModel;

    public function index(Request $request)
    {
        abort_unless(\Gate::allows('payable_access'), 403);

        if ($request->ajax()) {
            $query = Payreceivable::selectRaw("payreceivables.*,(SUM(CASE WHEN payreceivables_trs.type = 'D' AND payreceivables_trs.status = 'approved' THEN payreceivables_trs.amount ELSE 0 END) - SUM(CASE WHEN payreceivables_trs.type = 'C' AND payreceivables_trs.status = 'approved' THEN payreceivables_trs.amount ELSE 0 END)) AS amount_balance")
                ->leftJoin('payreceivables_trs', 'payreceivables_trs.payreceivable_id', '=', 'payreceivables.id')
                ->where('payreceivables.type', '=', 'payable')
                ->with('customers')
                ->groupBy('payreceivables.id')
                ->FilterInput()
                ->get();
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'payable_show';
                $editGate = 'payable_edit';
                $deleteGate = 'payable_delete';
                $crudRoutePart = 'payables';

                return view('partials.datatablesPayables', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('register', function ($row) {
                return $row->register ? $row->register : "";
            });

            $table->editColumn('code', function ($row) {
                return $row->code ? $row->code : "";
            });

            $table->editColumn('name', function ($row) {
                return $row->customers->name ? $row->customers->name : "";
            });

            $table->editColumn('memo', function ($row) {
                return $row->memo ? $row->memo : "";
            });

            $table->editColumn('amount', function ($row) {
                return $row->amount ? number_format($row->amount, 2) : "";
            });

            $table->editColumn('amount_balance', function ($row) {
                return $row->amount_balance ? number_format($row->amount_balance, 2) : "";
            });

            $table->rawColumns(['actions', 'placeholder']);

            $table->addIndexColumn();
            return $table->make(true);
        }
        //def view
        $payables = Payreceivable::selectRaw("payreceivables.*,(SUM(CASE WHEN payreceivables_trs.type = 'D' AND payreceivables_trs.status = 'approved' THEN payreceivables_trs.amount ELSE 0 END) - SUM(CASE WHEN payreceivables_trs.type = 'C' AND payreceivables_trs.status = 'approved' THEN payreceivables_trs.amount ELSE 0 END)) AS amount_balance")
            ->leftJoin('payreceivables_trs', 'payreceivables_trs.payreceivable_id', '=', 'payreceivables.id')
            ->where('payreceivables.type', '=', 'payable')
            ->with('customers')
            ->groupBy('payreceivables.id')
            ->get();

        $customers = Customer::select('*')
            ->where('def', '=', '0')
            ->where(function ($query) {
                $query->where('type', 'general')
                    ->orWhere('type', 'capitalist');
            })
            ->get();

        return view('admin.payables.index', compact('payables', 'customers'));
    }

    public function create()
    {
        $last_code = $this->get_last_code('payable');
        $code = acc_code_generate($last_code, 8, 3);
        $accounts = Account::select('*')
            ->where('accounts_group_id', 10)
            ->get();
        $accounts_pay = Account::select('*')
        ->where(function ($query) {
            $query->where('accounts_group_id', 1)
                ->orWhere('accounts_group_id', 7);
        })
            ->get();
        $customers = Customer::select('*')
            ->where('def', '=', '0')
            ->where(function ($query) {
                $query->where('type', 'general')
                    ->orWhere('type', 'capitalist');
            })
            ->get();
        return view('admin.payables.create', compact('code', 'accounts', 'customers', 'accounts_pay'));
    }

    public function store(StorePayreceivableRequest $request)
    {
        abort_unless(\Gate::allows('payable_create'), 403);

        $memo = "Registrasi Utang";
        $data = ['register' => $request->input('register'), 'title' => $memo, 'memo' => $memo, 'status' => 'approved'];
        $ledger = Ledger::create($data);
        $ledger_id = $ledger->id;
        $accounts = array($request->input('account_id'), $request->input('account_pay'));
        $amounts = array($request->input('amount'), $request->input('amount'));
        $types = array('C', 'D');
        //ledger entries
        for ($account = 0; $account < count($accounts); $account++) {
            if ($accounts[$account] != '') {
                $ledger->accounts()->attach($accounts[$account], ['entry_type' => $types[$account], 'amount' => $amounts[$account]]);
            }
        }

        $data = array_merge($request->all(), ['label' => $memo, 'type' => 'payable', 'status' => 'approved']);
        $payreceivable = Payreceivable::create($data);

        //get code trs
        $last_code = $this->get_last_code('payable_trs');
        $code = acc_code_generate($last_code, 8, 3);

        $payreceivable->ledgers()->attach($ledger_id, ['type' => 'D', 'status' => 'approved', 'code' => $code, 'label' => $memo, 'memo' => $request->input('memo'), 'register' => $request->input('register'), 'amount' => $request->input('amount'), 'account_id' => $request->input('account_pay')]);

        return redirect()->route('admin.payables.index');
    }

    public function edit(Payreceivable $payreceivable)
    {
        abort_unless(\Gate::allows('payable_edit'), 403);

        // $payreceivable->load('accounts');

        // return view('admin.payables.edit', compact('payreceivable'));
        return redirect()->route('admin.payables.index');
    }

    public function update(StorePayreceivableRequest $request, Payreceivable $payreceivable)
    {
        abort_unless(\Gate::allows('payable_edit'), 403);

        // $data = $request->all();

        // $payreceivable->update($data);

        return redirect()->route('admin.payables.index');

    }

    public function show(Payreceivable $payreceivable)
    {
        abort_unless(\Gate::allows('payable_show'), 403);

        // return view('admin.payables.show', compact('payreceivable'));
        return redirect()->route('admin.payables.index');
    }

    public function destroy($id)
    {
        abort_unless(\Gate::allows('payable_delete'), 403);

        $payreceivable = Payreceivable::find($id);

        //if count pay trs type C
        $payreceivabletrs_c = PayreceivableTrs::where('payreceivable_id',$payreceivable->id)
            ->where('type','C')    
            ->get();
        
        if (count($payreceivabletrs_c)==0) {
            $payreceivabletrs_d = PayreceivableTrs::where('payreceivable_id',$payreceivable->id)
            ->where('type','D')    
            ->first();       
            if($payreceivabletrs_d->delete()){
            $ledger = Ledger::find($payreceivabletrs_d->ledger_id);
            $ledger->accounts()->detach();
            $ledger->delete();
            $payreceivable->delete();
            }
        }

        return back();
    }

    public function massDestroy(MassDestroyPayreceivableRequest $request)
    {
        //Payreceivable::whereIn('id', request('ids'))->delete();

        return response(null, 204);
    }
}
