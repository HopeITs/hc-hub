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
<div class="flex items-end space-x-6 bg-white rounded-2xl py-4 px-6 w-fit">
    @if($isAnnual)
        <div class="flex items-end -mb-3.5">
            <span class="text-[125px] font-extrabold text-secondary-medium leading-none">{{$remainingDays}}</span>
            <span class="text-3xl text-secondary-medium font-semibold mb-4">/{{$totalDays}}</span>
        </div>

        <div class="border-l border-secondary-medium h-26"></div>
    @endif
    <div class="flex flex-col justify-between min-w-full">
        <h4 class="text-secondary-medium font-bold text-md">
            {{$title}}
        </h4>
        <div class="border-b border-3 border-primary w-6"></div>
        <ul class="mt-2 space-y-0.2 text-secondary-light text-xs leading-relaxed">
            <li class="text-secondary-light">
                <span>USED:</span>
                <span>{!! $used !!}</span>
                {{--                <span ><strong class="text-secondary-dark">12</strong> days & <strong class="text-secondary-dark">4</strong> hours</span>--}}
            </li>
            <li>
                <span>REMAINING:</span>
                <span>{!! $remaining !!}</span>
                {{--                <strong class="text-secondary-dark">12</strong> days & <strong class="text-secondary-dark">4</strong> hours--}}
            </li>
            <li>
                <span> PENDING:</span>
                <span>{!! $pending !!}</span>
                {{--                <strong class="text-secondary-dark">1</strong> day--}}
            </li>
            <li>
                <span> TOTAL:</span>
                <span>{!! $total !!}</span>
                {{--                <strong class="text-secondary-dark">25</strong> days--}}
            </li>
        </ul>
    </div>
</div>
