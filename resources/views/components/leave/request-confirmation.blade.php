{{-- Summary shown in the save-confirmation dialog of a leave request. --}}
@props([
    'type' => 'day',
    'leaveType' => '',
    'start' => '',
    'end' => '',
    'duration' => '',
    'reason' => '',
    'date' => '',
])
<div class="flex flex-col items-start justify-start space-y-2 text-start">
    <span><b>{{ trans_choice('general.leave-type', 1) }}:</b> {{ $leaveType }}</span>
    <span><b>@lang('general.type'):</b> {{ __('leaves.unit.'.($type === 'hour' ? 'hour' : 'day')) }}</span>
    @if($type === 'hour')
        <span><b>@lang('general.leave-date'):</b> <bdi>{{ $date }}</bdi></span>
        <span><b>@lang('general.time'):</b> <bdi>{{ $start }}</bdi> {{ __('leaves.range-separator') }} <bdi>{{ $end }}</bdi></span>
    @else
        <span><b>@lang('general.during-period'):</b> <bdi>{{ $start }}</bdi> {{ __('leaves.range-separator') }} <bdi>{{ $end }}</bdi></span>
    @endif
    <span><b>@lang('general.duration'):</b> {{ $duration }}</span>
    <span><b>@lang('general.reason'):</b> {{ $reason }}</span>
</div>
