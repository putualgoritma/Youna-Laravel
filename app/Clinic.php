<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class Clinic extends Model
{
    use SoftDeletes;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
    protected $table = 'clinics';
   
    protected $fillable = [
        'code',
        'name',
        'phone',
        'whatsapp',
        'address',
        'description',
    ];

    public function images()
    {
        return $this->hasMany(ClinicImage::class, 'clinic_id')->select('id','clinic_id','name','path');
    }
}
