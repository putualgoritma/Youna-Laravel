<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'code',
        'name',
    ];    

}
