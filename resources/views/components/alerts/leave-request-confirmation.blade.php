@props([
    'type' => 'day',
    'leave_type' => '',
    'start' => '',
    'end' => '',
    'duration' => '',
    'reason' => '',
    'date' => ''

])
<div class='flex flex-col justify-start items-start'>
    <span><b>Leave:</b> {{$leave_type}}</span>
    <span> <b>Type:</b> {{$type}}</span>
    @if($type === 'day')
        <span> <b>Dates:</b> {{$start  .' → '. $end}} </span>
        <span> <b>Duration:</b> {{$duration}} day(s) </span>
    @else
        <span><b>Date:</b> {{$date}}</span>
        <span><b>Time:</b>{{$start  .' → '. $end}}</span>
        <span><b>Duration:</b> {{$duration}} hour(s)</span>
    @endif
    <span> <b>Reason:</b> {{$reason}}</span>
</div>
