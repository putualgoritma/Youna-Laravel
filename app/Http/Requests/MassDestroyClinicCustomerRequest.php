<?php

namespace App\Http\Requests;

use App\ClinicCustomer;
use Gate;
use Illuminate\Foundation\Http\FormRequest;

class MassDestroyClinicCustomerRequest extends FormRequest
{
    public function authorize()
    {
        return abort_if(Gate::denies('clinic_customer_delete'), 403, '403 Forbidden') ?? true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:clinic_customer,id',
        ];
    }
}
