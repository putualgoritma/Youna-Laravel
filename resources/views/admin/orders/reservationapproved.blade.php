@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
    Setujui {{ trans('global.order.title_singular') }}
    </div>

    <div class="card-body">
        <form action="{{ route("admin.orders.reservationApprovedProcess") }}" method="POST" enctype="multipart/form-data">
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

            <div>
                <input class="btn btn-danger" type="submit" value="Proses">
            </div>
        </form>


    </div>
</div>
@endsection
