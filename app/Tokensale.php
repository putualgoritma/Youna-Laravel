<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tokensale extends Model
{
    public $table = 'tokensales';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'created_at',
        'updated_at',
        'deleted_at',
        'agent_id',
        'customer_id',
        'code',
        'type',
        'activation_type_id',
        'old_activation_type_id',
        'memo',
        'total',
    ];

    public function customers()
    {
        return $this->belongsTo(Customer::class, 'customer_id')->select('id', 'code', 'name');
    }

    public function agents()
    {
        return $this->belongsTo(Customer::class, 'agent_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'tokensale_product', 'tokensale_id', 'product_id')
            ->withPivot([
                'quantity',
                'price',
            ])
            ->select(['products.id', 'products.price', 'products.name']);
    }
}
