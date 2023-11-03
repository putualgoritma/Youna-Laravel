<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClinicCustomer extends Model
{
    public $timestamps = false;
    
    protected $table = 'clinic_customer';

    protected $fillable = [
        'code',
        'clinic_id',
        'customer_id',
    ];

    public function clinics()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id')->select('id', 'code', 'name', 'address', 'description');
    }

    public function customers()
    {
        return $this->belongsTo(Customer::class, 'customer_id')->select('id', 'code', 'name', 'address');
    }

    public function scopeFilterClinic($query)
    {
        if(!empty(request()->input('clinic_id'))){
            $clinic_id = request()->input('clinic_id'); 
            return $query->where('clinic_id', $clinic_id);
        }else{
            return ;
        }
    }

}
