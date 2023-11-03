@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.edit') }} {{ trans('global.availability.title_singular') }}
    </div>

    <div class="card-body">
        <form action="{{ route("admin.availabilities.update", [$availability->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            {{ csrf_field() }}
            @method('PUT')
            <div class="form-group {{ $errors->has('day_id') ? 'has-error' : '' }}">
                <label for="day_id">{{ trans('global.availability.fields.day_id') }}*</label>
                <select name="day_id" class="form-control">
                @foreach ($days as $day)
                    <option value="{{ $day->id }}">{{ $day->name }}</option>                        
                    @endforeach
                    </select>
                @if($errors->has('day_id'))
                    <em class="invalid-feedback">
                        {{ $errors->first('day_id') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.availability.fields.day_id_helper') }}
                </p>
            </div>

            <div class="form-group {{ $errors->has('start') ? 'has-error' : '' }}">
                <label for="start">{{ trans('global.availability.fields.start') }}*</label>
                <input type="time" id="start" name="start" class="form-control" value="{{ old('start', isset($availability) ? $availability->start : '') }}" required>
                @if($errors->has('start'))
                    <em class="invalid-feedback">
                        {{ $errors->first('start') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.availability.fields.start_helper') }}
                </p>
            </div>

            <div class="form-group {{ $errors->has('end') ? 'has-error' : '' }}">
                <label for="end">{{ trans('global.availability.fields.end') }}*</label>
                <input type="time" id="end" name="end" class="form-control" value="{{ old('end', isset($availability) ? $availability->end : '') }}" required>
                @if($errors->has('end'))
                    <em class="invalid-feedback">
                        {{ $errors->first('end') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.availability.fields.end_helper') }}
                </p>
            </div>            

            <div>
                <input class="btn btn-danger" type="submit" value="{{ trans('global.save') }}">
                <input type="hidden" name="clinic_customer_id" value="{{ $availability->clinic_customer_id }}">
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
    
@endsection