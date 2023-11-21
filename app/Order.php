<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $table = 'orders';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'created_at',
        'updated_at',
        'deleted_at',
        'code',
        'memo',
        'register',
        'total',
        'type',
        'variant',
        'status',
        'ledgers_id',
        'customers_id',
        'payment_type',
        'agents_id',
        'bv_activation_amount',
        'customers_activation_id',
        'activation_type_id_old',
        'bv_ro_amount',
        'activation_type_id',
        'bv_total',
        'bv_automaintain_amount',
        'bv_reseller_amount',
        'token_no',
        'status_delivery',
        'firebase_threads_id',
    ];

    public function customers()
    {
        return $this->belongsTo(Customer::class, 'customers_id')->select('id', 'code', 'name');
    }

    public function agents()
    {
        return $this->belongsTo(Customer::class, 'agents_id');
    }

    public function availabilities()
    {
        return $this->belongsToMany(Availability::class, 'order_availability', 'order_id', 'availability_id')->with('days')->with('clinicCustomers')
            ->withPivot([
                'date',
                'qr_code',
            ])
            ->select(['availabilities.*']);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_product', 'order_id', 'product_id')
            ->withPivot([
                'quantity',
                'price',
                'cogs',
            ])
            ->select(['products.id', 'products.price', 'products.name']);
    }

    public function productdetails()
    {
        return $this->belongsToMany(Product::class, 'product_order_details', 'orders_id', 'products_id')->withPivot([
            'quantity',
            'type',
            'status',
            'warehouses_id',
            'owner',
        ]);
    }

    public function points()
    {
        return $this->belongsToMany(Point::class, 'order_points', 'orders_id', 'points_id')->withPivot([
            'amount',
            'type',
            'status',
            'customers_id',
        ]);
    }

    public function scopeFilterExpertDateDay($query)
    {
        if (!empty(request()->input('date'))) {
            $day_num = date("w", strtotime(request()->input('date'))) + 1;
            return $query->where('availabilities.day_id', $day_num);
        } else {
            return;
        }
    }

    public function scopeFilterExpert($query)
    {
        if (!empty(request()->input('expert_id'))) {
            return $query->where('clinic_customer.customer_id', request()->input('expert_id'));
        } else {
            return;
        }
    }

    public function scopeFilterInput($query)
    {
        if (!empty(request()->input('type'))) {
            $type = request()->input('type');
            if ($type == 'agent_sale') {
                return $query->where('type', $type)->where('bv_ro_amount', '<=', 0)->where('bv_automaintain_amount', '<=', 0)->where('bv_reseller_amount', '<=', 0);
            } else if ($type == 'ro') {
                return $query->where('type', 'agent_sale')->where('bv_ro_amount', '>', 0);
            } else if ($type == 'am') {
                return $query->where('type', 'agent_sale')->where('bv_automaintain_amount', '>', 0);
            } else if ($type == 'rr') {
                return $query->where('type', 'agent_sale')->where('bv_reseller_amount', '>', 0);
            } else {
                return $query->where('type', $type);
            }
        } else {
            return;
        }
    }

    public function scopeFilterStatus($query)
    {
        if (!empty(request()->input('status'))) {
            $status = request()->input('status');
            return $query->where('status', $status);
        } else {
            return;
        }
    }

    public function scopeFilterCustomer($query)
    {
        if (!empty(request()->input('customer'))) {
            $customer = request()->input('customer');
            return $query->where('customers_id', $customer);
        } else {
            return;
        }
    }

    public function scopeFilterRangeDate($query, $from, $to)
    {
        if (!empty($from) && !empty($to)) {
            $from = request()->input('from');
            $to = request()->input('to');
            // $from = '2021-09-01';
            // $to = '2021-09-20';
            return $query->whereBetween('register', [$from, $to]);
            // return $query->where('froms_id', $from);
            // dd(request()->input('from'));

        } else {
            $from = date('Y-m-01');
            $to = date('Y-m-d');
            return $query->whereBetween('register', [$from, $to]);
        }
    }

    public function scopeFilterProductJoin($query)
    {
        if (!empty(request()->input('product'))) {
            $product = request()->input('product');
            return $query->where('product_order_details.products_id', $product);
        } else {
            return;
        }
    }

    public function scopeFilterPackageJoin($query)
    {
        if (!empty(request()->input('product'))) {
            $product = request()->input('product');
            return $query->where('order_product.product_id', $product);
        } else {
            return;
        }
    }

    public function accounts()
    {
        return $this->belongsTo(Account::class, 'acc_pay')->select('id', 'code', 'name');
    }

    public function scopeFilterOrderType($query, $type)
    {
        if (!empty($type)) {
            if ($type == 'ro') {
                return $query->where('orders.bv_ro_amount', '>', 0);
            } else {
                return $query->where('orders.bv_automaintain_amount', '>', 0);
            }
        } else {
            return;
        }
    }
}
