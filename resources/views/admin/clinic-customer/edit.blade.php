@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.edit') }} {{ trans('global.clinic_customer.title_singular') }}
    </div>

    <div class="card-body">
        <form action="{{ route("admin.clinic-customer.update", [$clinicCustomer->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            {{ csrf_field() }}
            @method('PUT')
            <div class="form-group {{ $errors->has('code') ? 'has-error' : '' }}">
                <label for="code">{{ trans('global.clinic_customer.fields.code') }}*</label>
                <input type="text" id="code" name="code" class="form-control" value="{{ old('code', isset($clinicCustomer) ? $clinicCustomer->code : $code) }}">
                @if($errors->has('code'))
                    <em class="invalid-feedback">
                        {{ $errors->first('code') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.clinic_customer.fields.code_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('customer_id') ? 'has-error' : '' }}">
                <label for="customer_id">{{ trans('global.expert.fields.name') }}*</label>
            <select name="customer_id" class="form-control" required>
                    <option value="">-- Pilih Expert --</option>
                    @foreach ($experts as $expert)
                        <option value="{{ $expert->id }}"{{ $clinicCustomer->customer_id == $expert->id ? ' selected' : '' }}>
                        {{ $expert->code }}-{{ $expert->name }} {{ $expert->last_name }}
                        </option>
                    @endforeach
                </select>
                @if($errors->has('customer_id'))
                    <em class="invalid-feedback">
                        {{ $errors->first('customer_id') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.expert.fields.name_helper') }}
                </p>
            </div>            

            <div>
                <input class="btn btn-danger" type="submit" value="{{ trans('global.save') }}">
                <input type="hidden" name="clinic_id" value="{{ $clinicCustomer->clinic_id }}">
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
    
@endsection