<div class="bg-white rounded-[32px] p-7 shadow-sm w-full max-w-3xl h-full mx-auto">

    {{-- HEADER (matches design) --}}
    <div class="flex items-center gap-3 mb-5 ml-2.5 leading-none">
        <h2 class="text-[16px] font-semibold text-secondary-dark">Calender</h2>

        <span class="text-primary">
            <svg class="size-5 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
            </svg>
        </span>
    </div>

    {{-- MONTH BAR --}}
    <div class="flex justify-between items-center px-3 mb-1">
        <p class="text-[13px] font-normal text-secondary-dark">
            {{ $current->format('F Y') }}
        </p>

        <div class="flex gap-1 text-[16px] font-light text-secondary-dark">
            <button wire:click="prev" >&lt;</button>
            <button wire:click="next" >&gt;</button>
        </div>
    </div>

    <div class="border-t w-[290px] ml-3 border-secondary-medium mb-2"></div>

    {{-- WEEKDAY LETTERS --}}
    <div class="grid grid-cols-7 text-center font-medium mb-2 text-[12px] text-secondary-medium tracking-wide">
        <span>Mo</span>
        <span>Tu</span>
        <span>We</span>
        <span>Th</span>
        <span>Fr</span>
        <span>Sa</span>
        <span>Su</span>
    </div>

    {{-- DAYS GRID (matches spacing + font in design) --}}
    <div class="grid grid-cols-7 text-center gap-y-1.5 text-[12px] text-gray-400">

        @foreach ($days as $day)
            @php
                if ($day) {
                    $isToday = $day->isToday();
                    $isRed = $day->day >= 23;
                }
            @endphp

            {{-- Empty slot --}}
            @if(!$day)
                <div></div>
            @else
                <div class="
                    @if($isToday) text-secondary-dark font-extrabold @endif
                    @if($isRed) text-primary font-extrabold @endif
                ">
                    {{ $day->day }}
                </div>
            @endif

        @endforeach

    </div>
</div>
