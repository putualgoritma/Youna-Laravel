@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.create') }} {{ trans('global.agent.title_singular') }}
    </div>

    <div class="card-body">
        <form action="{{ route("admin.agents.store") }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group {{ $errors->has('register') ? 'has-error' : '' }}">
                <label for="register">{{ trans('global.production.fields.register') }}*</label>
                <input type="date" id="register" name="register" class="form-control" value="{{ old('register', isset($production) ? $production->register : date('Y-m-d')) }}" required>
                @if($errors->has('register'))
                    <em class="invalid-feedback">
                        {{ $errors->first('register') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.production.fields.register_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('code') ? 'has-error' : '' }}">
                <label for="code">{{ trans('global.agent.fields.code') }}*</label>
                <input type="text" id="code" name="code" class="form-control" value="{{ old('code', isset($agent) ? $agent->code : $code) }}">
                @if($errors->has('code'))
                    <em class="invalid-feedback">
                        {{ $errors->first('code') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.agent.fields.code_helper') }}
                </p>
            </div>

            <div class="form-group {{ $errors->has('agent_type') ? 'has-error' : '' }}">
                <label for="agent_type">{{ trans('global.agent.fields.type') }}*</label>
                <select name="agent_type" class="form-control">
                    <option value="non">Non Reseller</option>
                    <option value="reseller">Reseller</option>
                </select>
                @if($errors->has('agent_type'))
                    <em class="invalid-feedback">
                        {{ $errors->first('agent_type') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.agent.fields.type_helper') }}
                </p>
            </div>

            <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                <label for="name">{{ trans('global.agent.fields.name') }}*</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', isset($agent) ? $agent->name : '') }}" required>
                @if($errors->has('name'))
                    <em class="invalid-feedback">
                        {{ $errors->first('name') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.agent.fields.name_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('last_name') ? 'has-error' : '' }}">
                <label for="last_name">{{ trans('global.agent.fields.last_name') }}</label>
                <input type="text" id="last_name" name="last_name" class="form-control" value="{{ old('last_name', isset($agent) ? $agent->last_name : '') }}" step="0.01">
                @if($errors->has('last_name'))
                    <em class="invalid-feedback">
                        {{ $errors->first('last_name') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.agent.fields.last_name_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('address') ? 'has-error' : '' }}">
                <label for="address">{{ trans('global.agent.fields.address') }}*</label>
                <textarea id="address" name="address" class="form-control " required>{{ old('address', isset($agent) ? $agent->address : '') }}</textarea>
                @if($errors->has('address'))
                    <em class="invalid-feedback">
                        {{ $errors->first('address') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.agent.fields.address_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('phone') ? 'has-error' : '' }}">
                <label for="phone">{{ trans('global.agent.fields.phone') }}*</label>
                <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', isset($agent) ? $agent->phone : '') }}" step="0.01" required>
                @if($errors->has('phone'))
                    <em class="invalid-feedback">
                        {{ $errors->first('phone') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.agent.fields.phone_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                <label for="email">{{ trans('global.agent.fields.email') }}*</label>
                <input type="text" id="email" name="email" class="form-control" value="{{ old('email', isset($agent) ? $agent->email : '') }}" step="0.01" required>
                @if($errors->has('email'))
                    <em class="invalid-feedback">
                        {{ $errors->first('email') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.agent.fields.email_helper') }}
                </p>
            </div>
            <div>
                <input class="btn btn-danger" type="submit" value="{{ trans('global.save') }}">
                <input type="hidden" id="type" name="type" value='agent'>
            </div>
        </form>
    </div>
</div>

@endsection
