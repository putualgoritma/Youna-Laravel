<?php

namespace App\Http\Requests;

use App\ClinicCustomer;
use Illuminate\Foundation\Http\FormRequest;

class StoreClinicCustomerRequest extends FormRequest
{
    public function authorize()
    {
        return \Gate::allows('clinic_customer_create');
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
