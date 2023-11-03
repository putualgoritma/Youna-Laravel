@extends('layouts.admin')
@section('content')
@can('clinic_create')
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route("admin.clinic-customer.create",['clinic_id'=>$clinic->id]) }}">
                {{ trans('global.add') }} {{ trans('global.clinic_customer.title_singular') }}
            </a>
        </div>
    </div>
@endcan
<div class="card">
    <div class="card-header">
    {{ $clinic->code }} - {{ $clinic->name }} {{ trans('global.list') }}
    </div>

    <div class="card-body">
    <div class="form-group">
    <form action="" id="filtersForm">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <select id="status" name="status" class="form-control">
                                <option value="show">Show</option>
                                <option value="hidden">Hidden</option>
                                </select>
                            </div>
                        </div>
                        <span class="input-group-btn">
                        <input type="submit" class="btn btn-primary" value="Filter">
                        <input type="hidden" name='clinic_id' id='clinic_id' value="{{ $clinic->id }}">
                    </span>
                    </div>                    
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover datatable ajaxTable datatable-clinics">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('global.clinic.fields.code') }}
                        </th>
                        <th>
                            {{ trans('global.clinic.fields.name') }}
                        </th>
                        <th>
                            {{ trans('global.clinic_customer.fields.expert_code') }}
                        </th>
                        <th>
                            {{ trans('global.clinic_customer.fields.expert_name') }}
                        </th>
                        <th>
                            {{ trans('global.customer.fields.address') }}
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@section('scripts')
@parent
<script>
    $(function () {
    let searchParams = new URLSearchParams(window.location.search)
    //status
    let status = searchParams.get('status')
    if (status) {
        $("#status").val(status);
    }
    //clinic id
    let clinic_id = searchParams.get('clinic_id')
    if (clinic_id) {
        $("#clinic_id").val(clinic_id);
    }

    let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('clinic_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.clinicCustomer.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
          return $(entry).data('entry-id')
      });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }})
          .done(function () { location.reload() })
      }
    }
  }
  dtButtons.push(deleteButton)
@endcan

  $.extend(true, $.fn.dataTable.defaults, {
    clinic: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  $('.datatable-clinics:not(.ajaxTable)').DataTable({ buttons: dtButtons })

  let dtOverrideGlobals = {
    buttons: dtButtons,
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [],
    ajax: {
      url: "{{ route('admin.clinic-customer.index') }}",
      dataType: "json",
      headers: {'x-csrf-token': _token},
      method: 'GET',
      data: {
        'status':  $("#status").val(),
        'clinic_id':  $("#clinic_id").val(),
      }
    },
    columns: [
        { data: 'placeholder', name: 'placeholder' },
        { data: 'clinic_code', name: 'clinic_code' },
        { data: 'clinic_name', name: 'clinic_name' },
        { data: 'customer_code', name: 'customer_code' },
        { data: 'customer_name', name: 'customer_name' },
        { data: 'customer_address', name: 'customer_address'  },
        { data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    pageLength: 100,
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
  };

  $('.datatable-clinics').DataTable(dtOverrideGlobals);
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
        $($.fn.dataTable.tables(true)).DataTable()
            .columns.adjust();
    });

})

</script>
@endsection
@endsection
