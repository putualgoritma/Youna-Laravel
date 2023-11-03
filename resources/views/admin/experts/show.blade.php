@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.show') }} {{ trans('global.expert.title') }}
    </div>

    <div class="card-body">
        <table class="table table-bordered table-striped">
            <tbody>
                <tr>
                    <th>
                        {{ trans('global.expert.fields.name') }}
                    </th>
                    <td>
                        {{ $expert->name }}
                    </td>
                </tr>
                <tr>
                    <th>
                        {{ trans('global.expert.fields.address') }}
                    </th>
                    <td>
                        {!! $expert->address !!}
                    </td>
                </tr>
                <tr>
                    <th>
                        {{ trans('global.expert.fields.phone') }}
                    </th>
                    <td>
                        {{ $expert->phone }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection