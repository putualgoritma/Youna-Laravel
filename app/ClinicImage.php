<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class ClinicImage extends Model
{
    use SoftDeletes;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
    protected $table = 'clinic_image';
   
    protected $fillable = [
        'clinic_id',
        'name',
        'path',
    ];
}
