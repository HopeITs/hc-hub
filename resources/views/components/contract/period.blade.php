@props(['contract'])

<div class="text-sm grid grid-cols-2 gap-3">
    <div>
        <div class="text-xs uppercase opacity-70">{{ __('general.from') }}</div>
        <div class="font-medium">{{ optional($contract->start_date)->format('Y-m-d') }}</div>
    </div>
    <div>
        <div class="text-xs uppercase opacity-70">{{ __('general.to') }}</div>
        <div class="font-medium">{{ optional($contract->end_date)->format('Y-m-d') }}</div>
    </div>
    @if($contract->terminated_at)
        <div class="col-span-2">
            <div class="text-xs uppercase opacity-70">{{ __('general.terminated_at') }}</div>
            <div class="font-medium">{{ optional($contract->terminated_at)->format('Y-m-d') }}</div>
            @if($contract->reason_of_termination)
                <div class="mt-1">{{ $contract->reason_of_termination }}</div>
            @endif
        </div>
    @endif
</div>
