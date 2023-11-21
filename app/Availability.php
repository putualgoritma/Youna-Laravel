<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Availability extends Model
{
    use SoftDeletes;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $table = 'availabilities';

    protected $fillable = [
        'clinic_customer_id',
        'day_id',
        'start',
        'end',

    ];

    public function days()
    {
        return $this->belongsTo(Day::class, 'day_id')->select('id', 'code', 'name');
    }

    public function clinicCustomers()
    {
        return $this->belongsTo(ClinicCustomer::class, 'clinic_customer_id')->with('customers')->with('clinics');
    }

    public function scopeFilterDateDay($query)
    {
        if (!empty(request()->input('date'))) {
            $day_num = date("w", strtotime(request()->input('date'))) + 1;
            return $query->where('day_id', $day_num);
        } else {
            return;
        }
    }

    public function scopeFilterClinicCustomer($query)
    {
        if (!empty(request()->input('clinic_customer_id'))) {
            $clinic_customer_id = request()->input('clinic_customer_id');
            return $query->where('clinic_customer_id', $clinic_customer_id);
        } else {
            return;
        }
    }

}
