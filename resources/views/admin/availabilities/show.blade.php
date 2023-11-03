@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.show') }} {{ trans('global.availability.title') }}
    </div>

    <div class="card-body">
        <table class="table table-bordered table-striped">
            <tbody>
            <tr>
                    <th>
                    {{ trans('global.availability.fields.clinic_id') }}
                    </th>
                    <td>
                        {{ $availability->clinicCustomers->clinics->code." - ".$availability->clinicCustomers->clinics->name }}
                    </td>
                </tr>    
            <tr>
                    <th>
                    {{ trans('global.clinic.fields.name') }}
                    </th>
                    <td>
                    {{ $availability->clinicCustomers->customers->code." - ".$availability->clinicCustomers->customers->name }}
                    </td>
                </tr>
                <tr>
                    <th>
                    {{ trans('global.availability.fields.day_id') }}
                    </th>
                    <td>
                        {{ $availability->days->name }}
                    </td>
                </tr>
                <tr>
                    <th>
                    {{ trans('global.availability.fields.start') }}
                    </th>
                    <td>
                        {{ $availability->start }}
                    </td>
                </tr>
                <tr>
                    <th>
                    {{ trans('global.availability.fields.end') }}
                    </th>
                    <td>
                        {{ $availability->end }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection