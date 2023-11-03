@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.pairingpending.title_create') }}
    </div>

    @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card-body">
        <form action="{{ route("admin.members.pairingConvertProcess") }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">{{ trans('global.member.fields.name') }}</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ $member->name }}" step="0.01" readonly>
            </div>
            <div class="form-group">
                <label for="address">{{ trans('global.member.fields.address') }}</label>
                <input type="text" id="address" name="address" class="form-control" value="{{ $member->address }}" step="0.01" readonly>
            </div>            
            <div class="form-group {{ $errors->has('l_balance') ? 'has-error' : '' }}">
                <label for="l_balance">{{ trans('global.pairingpending.fields.l_balance') }}</label>
                <input type="number" id="l_balance" name="l_balance" class="form-control" value="{{ old('l_balance', isset($member) ? $member->l_balance : '') }}" step="0.01" readonly>
                @if($errors->has('l_balance'))
                    <em class="invalid-feedback">
                        {{ $errors->first('l_balance') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.pairingpending.fields.l_balance_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('r_balance') ? 'has-error' : '' }}">
                <label for="r_balance">{{ trans('global.pairingpending.fields.r_balance') }}</label>
                <input type="number" id="r_balance" name="r_balance" class="form-control" value="{{ old('r_balance', isset($member) ? $member->r_balance : '') }}" step="0.01" readonly>
                @if($errors->has('r_balance'))
                    <em class="invalid-feedback">
                        {{ $errors->first('r_balance') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.pairingpending.fields.r_balance_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('balance') ? 'has-error' : '' }}">
                <label for="balance">{{ trans('global.pairingpending.fields.balance') }}</label>
                <input type="number" id="balance" name="balance" class="form-control" value="{{ old('balance', isset($member) ? $member->balance : '') }}" step="0.01">
                @if($errors->has('balance'))
                    <em class="invalid-feedback">
                        {{ $errors->first('balance') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.pairingpending.fields.balance_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('percent') ? 'has-error' : '' }}">
                <label for="percent">{{ trans('global.pairingpending.fields.percent') }} (%)</label>
                <input type="number" id="percent" name="percent" class="form-control" value="8.5" step="0.01">
                @if($errors->has('percent'))
                    <em class="invalid-feedback">
                        {{ $errors->first('percent') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.pairingpending.fields.percent_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('point') ? 'has-error' : '' }}">
                <label for="point">{{ trans('global.pairingpending.fields.point') }}</label>
                <select name="point" class="form-control">
                <option value="">-- pilih poin --</option>
                @foreach ($points as $point)
                <option value="{{ $point->id }}"{{ 6 == $point->id ? ' selected' : '' }}>
                {{ $point->name }}
                </option>
                @endforeach
                </select>
            </div>

            <div>
            <input type="hidden" name="member_id" value="{{$member->id}}">    
            <input class="btn btn-danger" type="submit" value="{{ trans('global.save') }}">
            </div>
        </form>
    </div>
</div>

@endsection
