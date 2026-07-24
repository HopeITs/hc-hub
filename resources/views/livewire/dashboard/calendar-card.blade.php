<div class="bg-white rounded-surface py-4 px-6 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] w-full max-w-3xl h-full mx-auto">

    <div class="flex items-center gap-2 leading-none">
        <h2 class="text-body font-semibold text-secondary-dark">@lang('general.calendar')</h2>

        <span class="text-primary">
            <svg class="size-5 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
            </svg>
        </span>
    </div>

    <div class="flex justify-between items-center px-3 my-1">
        <p class="text-body font-normal text-secondary-dark">
            {{ $current->format('F Y') }}
        </p>

        <div class="flex gap-1 text-body font-light text-secondary-dark">
            <button wire:click="prev" >&lt;</button>
            <button wire:click="next" >&gt;</button>
        </div>
    </div>

    <div class="border-t w-full  border-secondary-medium mb-2"></div>

    <div class="grid grid-cols-7 text-center font-medium mb-1 text-small text-secondary-dark tracking-wide">
        <span>{{substr(__('general.days-of-week.1'), 0,2)}} </span>
        <span>Tu</span>
        <span>We</span>
        <span>Th</span>
        <span>Fr</span>
        <span>Sa</span>
        <span>Su</span>
    </div>

    <div class="grid grid-cols-7 text-center font-light gap-y-2 text-caption">

        @foreach ($calendar as $day)
            @php
                $wrapperClasses = "size-5 flex items-center justify-center transition";

                if ($day['circleFill']) {
                    $wrapperClasses .= " rounded-full {$day['circleFill']}";
                }

                if (count($day['tooltip']) > 0) {
                    $wrapperClasses .= " cursor-pointer";
                }

                $numberClasses = "{$day['numberColor']} {$day['fontWeight']}";
            @endphp

            <div class="relative group flex justify-center">

                <div class="{{ $wrapperClasses }}">
                    <div class="flex flex-col items-center leading-none">

                <span class="{{ $numberClasses }}">
                    {{ $day['day'] }}
                </span>

                        @if($day['underline'])
                            <div class="w-full border-b {{ $day['underlineColor'] }} mt-[-1px]"></div>
                        @endif

                    </div>
                </div>

                @if(count($day['tooltip']) > 0)
                    <div class="absolute bottom-7 left-1/2 -translate-x-1/2 bg-white text-secondary-dark text-caption px-2 py-1 rounded shadow hidden group-hover:block whitespace-nowrap z-20">
                        @foreach($day['tooltip'] as $t)
                            <div>{{ $t }}</div>
                        @endforeach
                    </div>
                @endif

            </div>
        @endforeach

    </div>

</div>
