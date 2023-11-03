@extends('layouts.admin')
@section('content')
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
<div class="card">
    <div class="card-header">
    <div><strong>{{ trans('global.pairingpending.title') }} {{ trans('global.list') }}</strong></div>
    <div></div>
    </div>

    <div class="card-body">
    <div class="form-group">
        <div class="col-md-6">
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
                            BV Tunggu Kiri
                        </th>
                        <th>
                            BV Tunggu Kanan
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>  
                <tfoot align="left">
		            <tr><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th></tr>
	            </tfoot>              
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

    // alert(  $("#from").val())
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.members.pairingPending') }}",
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
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [],
    ajax: {
      url: "{{ route('admin.members.pairingPending') }}",
      data: {
        
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
        { data: 'lsaldo', name: 'lsaldo' },
        { data: 'rsaldo', name: 'rsaldo' },
        { data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    pageLength: 100,
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
 
            // converting to interger to find total
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
 
            // computing column Total of the complete result 
            var Total = api
                .column( 8 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            var Total2 = api
                .column( 9 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
				
	    // Update footer by showing the total with the reference of the column index 
	    $( api.column( 7 ).footer() ).html('Total');
        $( api.column( 8 ).footer() ).html(Total.toLocaleString("en-GB"));
        $( api.column( 9 ).footer() ).html(Total2.toLocaleString("en-GB"));
        },
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