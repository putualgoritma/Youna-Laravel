@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.show') }} {{ trans('global.clinic.title') }}
    </div>

    <div class="card-body">
        <table class="table table-bordered table-striped">
            <tbody>
            <tr>
                    <th>
                        {{ trans('global.clinic.fields.code') }}
                    </th>
                    <td>
                        {{ $clinic->code }}
                    </td>
                </tr>    
            <tr>
                    <th>
                        {{ trans('global.clinic.fields.name') }}
                    </th>
                    <td>
                        {{ $clinic->name }}
                    </td>
                </tr>
                <tr>
                    <th>
                        {{ trans('global.clinic.fields.address') }}
                    </th>
                    <td>
                        {!! $clinic->address !!}
                    </td>
                </tr>
                <tr>
                    <th>
                        {{ trans('global.clinic.fields.description') }}
                    </th>
                    <td>
                        {!! $clinic->description !!}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection