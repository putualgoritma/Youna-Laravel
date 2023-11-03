@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.show') }} {{ trans('global.clinic_customer.title') }}
    </div>

    <div class="card-body">
        <table class="table table-bordered table-striped">
            <tbody>
            <tr>
                    <th>
                    {{ trans('global.clinic.fields.code') }}
                    </th>
                    <td>
                        {{ $clinicCustomer->code }}
                    </td>
                </tr>    
            <tr>
                    <th>
                    {{ trans('global.clinic.fields.name') }}
                    </th>
                    <td>
                        {{ $clinicCustomer->clinics->code }} - {{ $clinicCustomer->clinics->name }}
                    </td>
                </tr>
                <tr>
                    <th>
                    {{ trans('global.customer.fields.name') }}
                    </th>
                    <td>
                        {{ $clinicCustomer->customer->code }} - {{ $clinicCustomer->customer->name }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection