@props([
    'remainingDays' => 0 ,
    'totalDays' => 12 ,
    'used' => '12 days & 4 hours',
    'remaining' => '4 hours',
    'pending' => '',
    'total' => '',
    'title' => 'Leave',
    'isAnnual' => false

])
<div {{$attributes->merge([
    'class' => 'flex items-end space-x-8 bg-white rounded-surface py-4 px-6 w-full'
]) }}>
    @if($isAnnual)
        <div class="flex items-end -mb-3">
            <span class="text-[125px] font-extrabold text-secondary-medium leading-none">{{$remainingDays}}</span>
            <span class="text-h2 text-secondary-medium font-semibold mb-4">/{{$totalDays}}</span>
        </div>

        <div class="border-l border-secondary-medium h-26"></div>
    @endif
    <div class="flex flex-col justify-between min-w-full">
        <h4 class="text-secondary-medium font-bold text-md">
            {{$title}}
        </h4>
        <div class="border-b border-3 border-primary w-6"></div>
        <ul class="mt-3 space-y-1 text-secondary-light text-small leading-relaxed">
            <li class="text-secondary-light text-start">
                <span>@lang('general.used-leave', ['attr' => $used ]) </span>

            </li>
            <li>
                <span>@lang('general.remaining-leave', ['attr' => $remaining ]) </span>
            </li>
            <li>
                <span>@lang('general.pending-leave', ['attr' => $pending ]) </span>

            </li>
            <li>
                <span>@lang('general.total-leave', ['attr' => $total ]) </span>
            </li>
        </ul>
    </div>
</div>
