@props([
    'name' => '',
    'operation' => '',
    'amount' => '',
    'add_badge' => false,
    'notes' => null,
])
<div class='flex flex-col justify-start items-start space-y-2'>
    <span><b>{{trans_choice('general.employee',1)}}:</b> {{$name}}</span>
    <span> <b>@lang('general.operation'):</b> {{$operation}}</span>
    <span> <b>@lang('general.amount'):</b> {{$amount }}</span>
    <span> <b>@lang('general.add-as-badge'):</b> {{$add_badge }}</span>
    @isset($notes)
        <span> <b>@lang('general.notes'):</b> {{$notes}}</span>
    @endif

</div>
