@can($viewGate)
@if($row->r_balance >0 || $row->l_balance > 0)
    <a class="btn btn-xs btn-primary" href="{{ route('admin.members.pairingConvert', ['member_id'=>$row->id,'l_balance'=>$row->l_balance,'r_balance'=>$row->r_balance]) }}">
        Konversi
    </a>
@endif
@endcan
