@extends('layouts.admin')
@section('content')
@can('member_create')
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route("admin.members.create") }}">
                {{ trans('global.add') }} {{ trans('global.member.title_singular') }}
            </a>
        </div>
    </div>
@endcan
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
<div class="card">
    <div class="card-header">
    <div><strong>{{ trans('global.member.title_singular') }} {{ trans('global.list') }}</strong></div>
    </div>

    <div class="card-body">
    <div class="form-group">
        <div class="col-md-6">
            <form action="" id="filtersForm">
                <div class="form-group">
                    <div class="input-group">
                    <select name="status-filter" id="status-filter" class="form-control">
                        <option value="">== Semua Status ==</option>
                        <option value="pending">Pending</option>
                        <option value="active">Aktif</option>
                        <option value="closed">Close</option>
                    </select>
                </div>
               </div>

                <div class="form-group">
                    {{-- <label>Dari Tanggal</label> --}}
                    <div class="input-group date">
                        <div class="input-group-addon">
                            <span class="glyphicon glyphicon-th"></span>
                        </div>
                        <input id="from" placeholder="masukkan tanggal Awal" type="date" class="form-control datepicker" name="from" value = "">
                    </div>
                </div>
                <div class="form-group">
                    {{-- <label>Sampai Tanggal</label> --}}
                    <div class="input-group date">
                        <div class="input-group-addon">
                            <span class="glyphicon glyphicon-th"></span>
                        </div>
                        <input id="to" placeholder="masukkan tanggal Akhir" type="date" class="form-control datepicker" name="to" value = "{{date('Y-m-d')}}">
                    </div>
                </div>
                <span class="input-group-btn">
                    <input type="submit" class="btn btn-primary" value="Filter">
                </span> 
            </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-members">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            No.
                        </th>
                        <th>
                            {{ trans('global.member.fields.code') }}
                        </th> 
                        <th>
                            {{ trans('global.member.fields.register') }}
                        </th>                        
                        <th>
                            {{ trans('global.member.fields.name') }}
                        </th>
                        <th>
                            {{ trans('global.member.fields.email') }}
                        </th>
                        <th>
                            {{ trans('global.member.fields.phone') }}
                        </th>
                        <th>
                            {{ trans('global.member.fields.status') }}
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
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'


    let statusFilter = searchParams.get('status-filter')
    if (statusFilter) {
        $("#status-filter").val(statusFilter);
    }

      // date from unutk start tanggal 
    let from = searchParams.get('from')
    if (from) {
        $("#from").val(from);
    }

    // date to untuk batas tanggal 
    let to = searchParams.get('to')
    if (to) {
        $("#to").val(to);
    }

    // alert(  $("#from").val())
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.members.massDestroy') }}",
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
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('member_delete')
  dtButtons.push(deleteButton)
@endcan

  $('.datatable:not(.ajaxTable)').DataTable({ buttons: dtButtons })

  let dtOverrideGlobals = {
    buttons: dtButtons,
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [],
    ajax: {
      url: "{{ route('admin.members.index') }}",
      data: {
        // 'status': searchParams.get('status-filter'),
        'status':  $("#status-filter").val(),
        'from' :   $("#from").val(),
        'to' :  $("#to").val(),
      }
    },
    columns: [
        { data: 'placeholder', name: 'placeholder' },
        { data: 'DT_RowIndex', name: 'no' },
        { data: 'code', name: 'code' },
        { data: 'register', name: 'register' },
        { data: 'name', name: 'name' },
        { data: 'email', name: 'email' },
        { data: 'phone', name: 'phone' },
        { data: 'status', name: 'status' },
        { data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    pageLength: 100,
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],    
  };

  $('.datatable-members').DataTable(dtOverrideGlobals);
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
        $($.fn.dataTable.tables(true)).DataTable()
            .columns.adjust();
    });

});

</script>
@endsection
@endsection