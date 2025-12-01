@props([
    'type' => 'day',
    'leave_type' => '',
    'start' => '',
    'end' => '',
    'duration' => '',
    'reason' => '',
    'date' => ''

])
<div class='flex flex-col justify-start items-start space-y-2'>
    <span><b>{{trans_choice('general.leave-type',1) }}:</b> {{$leave_type}}</span>
    <span> <b>@lang('general.type'):</b> {{trans_choice('general.'.$type,1)}}</span>
    @if($type === 'day')
        <span> <b>@lang('general.leave-date'):</b> {{$start  .' → '. $end}} </span>
        <span> <b>@lang('general.duration'):</b> {{$duration}}  </span>
    @else
        <span><b>@lang('general.leave-date'):</b> {{$date}}</span>
        <span><b>@lang('general.time'):</b>{{$start  .' → '. $end}}</span>
        <span><b>@lang('general.duration'):</b> {{$duration}} </span>
    @endif
    <span> <b>@lang('general.reason'):</b> {{$reason}}</span>
</div>
