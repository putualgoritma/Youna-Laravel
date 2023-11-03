<?php

namespace App\Http\Requests;

use App\Clinic;
use Illuminate\Foundation\Http\FormRequest;

class StoreClinicRequest extends FormRequest
{
    public function authorize()
    {
        return \Gate::allows('clinic_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
            ],
        ];
    }
}
