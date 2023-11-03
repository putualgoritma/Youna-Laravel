@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.create') }} {{ trans('global.clinic.title_singular') }}
    </div>

    <div class="card-body">
        <form action="{{ route("admin.clinics.store") }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group {{ $errors->has('code') ? 'has-error' : '' }}">
                <label for="code">{{ trans('global.clinic.fields.code') }}*</label>
                <input type="text" id="code" name="code" class="form-control" value="{{ old('code', isset($clinic) ? $clinic->code : $code) }}">
                @if($errors->has('code'))
                    <em class="invalid-feedback">
                        {{ $errors->first('code') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.clinic.fields.code_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                <label for="name">{{ trans('global.clinic.fields.name') }}*</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', isset($clinic) ? $clinic->name : '') }}">
                @if($errors->has('name'))
                    <em class="invalid-feedback">
                        {{ $errors->first('name') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.clinic.fields.name_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('phone') ? 'has-error' : '' }}">
                <label for="phone">{{ trans('global.clinic.fields.phone') }}*</label>
                <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', isset($clinic) ? $clinic->phone : '') }}">
                @if($errors->has('phone'))
                    <em class="invalid-feedback">
                        {{ $errors->first('phone') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.clinic.fields.phone_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('whatsapp') ? 'has-error' : '' }}">
                <label for="whatsapp">{{ trans('global.clinic.fields.whatsapp') }}*</label>
                <input type="text" id="whatsapp" name="whatsapp" class="form-control" value="{{ old('whatsapp', isset($clinic) ? $clinic->whatsapp : '') }}">
                @if($errors->has('whatsapp'))
                    <em class="invalid-feedback">
                        {{ $errors->first('whatsapp') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.clinic.fields.whatsapp_helper') }}
                </p>
            </div>

            <div class="form-group {{ $errors->has('img') ? 'has-error' : '' }}">
                <label for="img">{{ trans('global.product.fields.img') }}*</label>
                <input type="file" id="img" name="img" class="form-control" value="{{ old('img', isset($clinic) ? $clinic->img : '') }}">
                @if($errors->has('img'))
                    <em class="invalid-feedback">
                        {{ $errors->first('img') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.product.fields.img_helper') }}
                </p>
                <p>
                    <img src="{{ old('img', isset($clinic) ? $clinic->img : '') }}" alt="{{ old('name', isset($clinic) ? $clinic->name : '') }}" width="300">
                </p>
            </div>

            <div class="form-group {{ $errors->has('address') ? 'has-error' : '' }}">
                <label for="address">{{ trans('global.clinic.fields.address') }}</label>
                <textarea id="address" name="address" class="form-control ">{{ old('address', isset($clinic) ? $clinic->address : '') }}</textarea>
                @if($errors->has('address'))
                    <em class="invalid-feedback">
                        {{ $errors->first('address') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.clinic.fields.address_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('description') ? 'has-error' : '' }}">
                <label for="description">{{ trans('global.clinic.fields.description') }}</label>
                <textarea id="description" name="description" class="form-control ">{{ old('description', isset($clinic) ? $clinic->description : '') }}</textarea>
                @if($errors->has('description'))
                    <em class="invalid-feedback">
                        {{ $errors->first('description') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.clinic.fields.description_helper') }}
                </p>
            </div>
            
            <div>
                <input class="btn btn-danger" type="submit" value="{{ trans('global.save') }}">
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')

@endsection