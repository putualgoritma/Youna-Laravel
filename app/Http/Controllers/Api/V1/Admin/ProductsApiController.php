<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Activation;
use App\CustomerApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ProductsApiController extends Controller
{
    public function activationTypeDetail(Request $request)
    {
        $activation = Activation::where('id', $request->id)->first();
        if (is_null($activation)) {
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
                'data' => $activation,
            ]);
        }
    }

    public function activationType(Request $request)
    {
        if ($request->min) {
            if ($request->min > 2) {
                $activation = Activation::where('id', '>', $request->min)->where('type', '=', 'business')->get();
            } else {
                $activation = Activation::where('id', '>', $request->min)
                    ->where(function ($query) {
                        $query->where('type', 'business')
                            ->orWhere('type', 'mercy');
                    })
                    ->get();
            }
        } else {
            $activation = Activation::where('id', '>', 1)
                ->where(function ($query) {
                    $query->where('type', 'business')
                        ->orWhere('type', 'mercy');
                })
                ->get();
        }
        if (is_null($activation)) {
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
                'data' => $activation,
            ]);
        }
    }

    public function stockMember($id)
    {
        $products = Product::selectRaw("products.*,(SUM(CASE WHEN product_order_details.type = 'D' AND product_order_details.status = 'onhand' AND product_order_details.owner = '" . $id . "' THEN product_order_details.quantity ELSE 0 END) - SUM(CASE WHEN product_order_details.type = 'C' AND product_order_details.status = 'onhand' AND product_order_details.owner = '" . $id . "' THEN product_order_details.quantity ELSE 0 END)) AS quantity_balance")
            ->leftjoin('product_order_details', 'product_order_details.products_id', '=', 'products.id')
            ->where('products.type', '=', 'single')
            ->where('products.status', '=', 'show')
            ->groupBy('products.id')
            ->get();

        //Check if history found or not.
        if (is_null($products)) {
            $message = 'Product not found.';
            $status = false;
            return response()->json([
                'status' => $status,
                'message' => $message,
            ]);
        } else {
            $message = 'Product retrieved successfully.';
            $status = true;
            return response()->json([
                'status' => $status,
                'message' => $message,
                'data' => $products,
            ]);
        }
    }

    public function indexAgent(Request $request)
    {
        $agent_type = 'non';
        if ($request->agent_id) {
            //get agent agent_type
            $agent = CustomerApi::find($request->agent_id);
            $agent_type = $agent->agent_type;
        }
        if ($agent_type == 'reseller') {
            $products = Product::where(function ($qry) {
                $qry->where('package_type', '=', 'resellernew')
                    ->orWhere('model', '=', 'reseller');
            })
                ->where('status', '=', 'show')
                ->get();
        } else {
            $products = Product::where('package_type', '!=', 'resellernew')
                ->where('model', '!=', 'reseller')
                ->where('status', '=', 'show')
                ->get();
        }

        return $products;
    }

    public function indexMemberAgent(Request $request)
    {
        $agent_type = 'non';
        if ($request->agent_id) {
            //get agent agent_type
            $agent = CustomerApi::find($request->agent_id);
            $agent_type = $agent->agent_type;
        }
        if ($agent_type == 'reseller') {
            try {
                if (isset($request->page)) {
                    $products = Product::select('*')->FilterInput()
                        ->where('type', '=', 'single')
                        ->where('status', '=', 'show')
                        ->where('model', '=', 'reseller')
                        ->paginate(10, ['*'], 'page', $request->page);
                } else {
                    $products = Product::select('*')->FilterInput()
                        ->where('type', '=', 'single')
                        ->where('status', '=', 'show')
                        ->where('model', '=', 'reseller')
                        ->get();
                }
            } catch (QueryException $exception) {
                return;
            }} else {
            try {
                if (isset($request->page)) {
                    $products = Product::select('*')->FilterInput()
                        ->where('type', '=', 'single')
                        ->where('status', '=', 'show')
                        ->where('model', '!=', 'reseller')
                        ->paginate(10, ['*'], 'page', $request->page);
                } else {
                    $products = Product::select('*')->FilterInput()
                        ->where('type', '=', 'single')
                        ->where('status', '=', 'show')
                        ->where('model', '!=', 'reseller')
                        ->get();
                }
            } catch (QueryException $exception) {
                return;
            }
        }

        return $products;
    }

    public function indexMemberPackage(Request $request)
    {
        try {
            if ($request->page) {
                $products = Product::select('*')->FilterInput()
                    ->where('type', '=', 'package')
                    ->where('package_type', '=', 'member')
                    ->where('activation_type_id', '=', $request->activation_type_id)
                    ->where('status', '=', 'show')
                    ->where('model', '=', 'network')
                    ->paginate(10, ['*'], 'page', $request->page);
            } else {
                $products = Product::select('*')->FilterInput()
                    ->where('type', '=', 'package')
                    ->where('package_type', '=', 'member')
                    ->where('activation_type_id', '=', $request->activation_type_id)
                    ->where('status', '=', 'show')
                    ->where('model', '=', 'network')
                    ->get();
            }
        } catch (QueryException $exception) {
            return;
        }

        return $products;
    }

    public function indexMember(Request $request)
    {
        try {
            if (isset($request->page)) {
                $products = Product::select('*')->FilterInput()
                    ->where('type', '=', 'single')
                    ->where('status', '=', 'show')
                    ->where('model', '=', 'network')
                    ->paginate(10, ['*'], 'page', $request->page);
            } else {
                $products = Product::where(function ($qry) {
                    $qry->where('package_type', '!=', 'agent')
                        ->orWhere('package_type', '=', 'none');
                })
                    ->where('status', '=', 'show')
                    ->where('model', '=', 'network')
                    ->get();
            }
        } catch (QueryException $exception) {
            return;
        }

        return $products;
    }

    public function indexReseller(Request $request)
    {
        try {
            if (isset($request->page)) {
                $products = Product::select('*')->FilterInput()
                    ->where('type', '=', 'single')
                    ->where('status', '=', 'show')
                    ->where('model', '=', 'reseller')
                    ->paginate(10, ['*'], 'page', $request->page);
            } else {
                $products = Product::where(function ($qry) {
                    $qry->where('package_type', '!=', 'agent')
                        ->orWhere('package_type', '=', 'none');
                })
                    ->where('status', '=', 'show')
                    ->where('model', '=', 'reseller')
                    ->get();
            }
        } catch (QueryException $exception) {
            return;
        }

        return $products;
    }

    public function indexMemberUpgrade($activation_type_id)
    {
        $products = Product::where(function ($qry) {
            $qry->where('package_type', '=', 'upgrade');
        })
            ->where('status', '=', 'show')
            ->where('activation_type_id', '=', $activation_type_id)
            ->get();

        return $products;
    }

    public function index()
    {
        $products = Product::where('status', '=', 'show')
            ->get();

        return $products;
    }

    public function store(StoreProductRequest $request)
    {
        return Product::create($request->all());
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        return $product->update($request->all());
    }

    public function show($id)
    {
        $product = Product::find($id);

        //Check if product found or not.
        if (is_null($product)) {
            $message = 'Product not found.';
            $status = false;
            $response = $this->response($status, $product, $message);
            return $response;
        }
        $message = 'Product retrieved successfully.';
        $status = true;

        //Call function for response data
        $response = $this->response($status, $product, $message);
        return $response;
    }

    public function destroy(Product $product)
    {
        return $product->delete();
    }

    /**
     * Response data
     *
     * @param $status
     * @param $product
     * @param $message
     * @return \Illuminate\Http\Response
     */
    public function response($status, $product, $message)
    {
        //Response data structure
        $return['success'] = $status;
        $return['data'] = $product;
        $return['message'] = $message;
        return $return;
    }
}
