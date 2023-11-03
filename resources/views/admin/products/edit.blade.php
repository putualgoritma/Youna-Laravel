@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.edit') }} {{ trans('global.product.title_singular') }}
    </div>

    <div class="card-body">
        <form action="{{ route("admin.products.update", [$product->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            {{ csrf_field() }}
            @method('PUT')
            <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                <label for="name">{{ trans('global.product.fields.name') }}*</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', isset($product) ? $product->name : '') }}">
                @if($errors->has('name'))
                    <em class="invalid-feedback">
                        {{ $errors->first('name') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.product.fields.name_helper') }}
                </p>
            </div>

            <div class="form-group {{ $errors->has('img') ? 'has-error' : '' }}">
                <label for="img">{{ trans('global.product.fields.img') }}*</label>
                <input type="file" id="img" name="img" class="form-control" value="{{ old('img', isset($product) ? $product->img : '') }}">
                @if($errors->has('img'))
                    <em class="invalid-feedback">
                        {{ $errors->first('img') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.product.fields.img_helper') }}
                </p>
                <p>
                    <img src="{{ old('img', isset($product) ? $product->img : '') }}" alt="{{ old('name', isset($product) ? $product->name : '') }}" width="300">
                </p>
            </div>

            <div class="form-group {{ $errors->has('description') ? 'has-error' : '' }}">
                <label for="description">{{ trans('global.product.fields.description') }}</label>
                <textarea id="description" name="description" class="form-control ">{{ old('description', isset($product) ? $product->description : '') }}</textarea>
                @if($errors->has('description'))
                    <em class="invalid-feedback">
                        {{ $errors->first('description') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.product.fields.description_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('price') ? 'has-error' : '' }}">
                <label for="price">{{ trans('global.product.fields.price') }}</label>
                <input type="number" id="price" name="price" class="form-control" value="{{ old('price', isset($product) ? $product->price : '') }}" step="0.01">
                @if($errors->has('price'))
                    <em class="invalid-feedback">
                        {{ $errors->first('price') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.product.fields.price_helper') }}
                </p>
            </div>
            

            <div class="form-group {{ $errors->has('status') ? 'has-error' : '' }}">
                <label for="status">{{ trans('global.package.fields.status') }}*</label>
                <select name="status" class="form-control">
                    <option value="show"{{ $product->status == 'show' ? 'selected="selected"' : '' }}>Show</option>
                    <option value="hidden"{{ $product->status == 'hidden' ? 'selected="selected"' : '' }}>Hide</option>                    
                </select>
                @if($errors->has('status'))
                    <em class="invalid-feedback">
                        {{ $errors->first('status') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.package.fields.status_helper') }}
                </p>
            </div> 

            <div>
                <input class="btn btn-danger" type="submit" value="{{ trans('global.save') }}">
                <input type="hidden" name="model" value="network">
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
    <script>
      $(document).ready(function(){
        let row_number = {{ count(old('accounts', $product->accounts->count() ? $product->accounts : [''])) }};
        $("#add_row").click(function(e){
          e.preventDefault();
          let new_row_number = row_number - 1;
          $('#account' + row_number).html($('#account' + new_row_number).html()).find('td:first-child');
          $('#accounts_table').append('<tr id="account' + (row_number + 1) + '"></tr>');
          row_number++;
        });

        $("#delete_row").click(function(e){
          e.preventDefault();
          if(row_number > 1){
            $("#account" + (row_number - 1)).html('');
            row_number--;
          }
        });
      });
    </script>
@endsection