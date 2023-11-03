<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Expert;
use App\Http\Controllers\Controller;
use App\Traits\TraitModel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\QueryException;

class ExpertsApiController extends Controller
{
    use TraitModel;

    public function index(Request $request)
    {
        try {
            $experts = Expert::where('customers.type', '=', 'expert')->FilterInput()->get();
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kosong.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $experts,
        ]);
    }

    public function create()
    {
        $last_code = $this->get_last_code('expert');
        $code = acc_code_generate($last_code, 8, 3);
    }

    public function store(StoreCustomerRequest $request)
    {
        $password_def = bcrypt('2579');
        $data = array_merge($request->all(), ['status' => 'active', 'password' => $password_def]);
        $customer = Expert::create($data);
    }

    public function edit(Expert $expert)
    {

    }

    public function update(UpdateCustomerRequest $request, Expert $expert)
    {
        $expert->update($request->all());
    }

    public function show(Expert $expert)
    {

    }

    public function destroy(Expert $expert)
    {
        //check if related
        $expert->delete();
    }
}
