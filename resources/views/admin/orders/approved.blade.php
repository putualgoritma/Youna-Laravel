@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
    Setujui {{ trans('global.order.title_singular') }}
    </div>

    <div class="card-body">
        <form action="{{ route("admin.orders.approvedprocess") }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')   
                     
            <div class="form-group {{ $errors->has('status') ? 'has-error' : '' }}">
            <div class="checkbox">
            <label>Setujui Order?</label>
            <input type="checkbox" data-toggle="toggle" name="status" id="status" data-on="Ya" data-off="Tidak">    
            </div>
                @if($errors->has('status'))
                    <em class="invalid-feedback">
                        {{ $errors->first('status') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.topup.fields.status_helper') }}
                </p>
                <input type="hidden" id="id" name="id" value="{{ $order->id }}">
            </div>

            @if($order->type === 'sale' && $order->payment_type === 'bank')
            <div class="form-group {{ $errors->has('accounts_id') ? 'has-error' : '' }}">
                <label for="accounts_id">{{ trans('global.order.fields.accounts_id') }}*</label>
                <select name="accounts_id" class="form-control" required>
                    <option value="">-- choose account --</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}"{{ old('code') == $account->id ? ' selected' : '' }}>
                        {{ $account->code }}-{{ $account->name }} {{ $account->last_name }}
                        </option>
                    @endforeach
                </select>
                @if($errors->has('accounts_id'))
                    <em class="invalid-feedback">
                        {{ $errors->first('accounts_id') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.order.fields.accounts_id_helper') }}
                </p>
            </div>
            @endif

            <div>
                <input class="btn btn-danger" type="submit" value="Proses">
            </div>
        </form>


    </div>
</div>
@endsection
