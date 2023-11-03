<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Topup extends Model
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
        'status',
        'ledgers_id',
        'customers_id',
        'payment_type',
        'acc_pay',
    ];

    public function points()
    {
        return $this->belongsToMany(Point::class, 'order_points', 'orders_id', 'points_id')->withPivot([
            'amount',
            'type',
            'status',
            'customers_id',
        ]);
    }

    public function customers()
    {
        return $this->belongsTo(Customer::class, 'customers_id');
    }
}
