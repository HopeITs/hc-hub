<div class="w-full h-full bg-white rounded-3xl shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] ring-1 ring-gray-200 overflow-hidden relative">
    <div class="js-today-events ">
        <div class="flex items-center justify-between px-7 pt-6">
            <div class="flex items-center gap-2">
                <span class="text-[13px] font-medium text-[#58595B]">@lang('general.today-events')</span>
                <span class="text-[13px] font-light text-secondary-light">{{ now()->format('n/j/Y') }}</span>
                <x-icons.event class="ml-1 mb-0.5 text-secondary-medium" />
            </div>
        </div>

        <div class="swiper">
            <div class="swiper-wrapper">

                @forelse($events as $event)
                    <div class="swiper-slide">
                        <div class="relative h-[240px] flex flex-col items-center justify-center">
                            @if($event['type'] === 'birthday')
                                <img src="/assets/GIF/01-Confetti.gif"
                                    class="absolute inset-0 w-full h-full pr-2 pb-10 object-cover pointer-events-none opacity-100 z-0">
                            @endif

                            <div class="flex flex-col h-full justify-center z-30 bg-white items-center ">
                                {{-- Avatar --}}
                                <img src="{{ $event['photo'] }}" alt="{{ $event['name'] }}"
                                    class="relative z-20 bg-white border border-primary w-18 h-18 rounded-full object-cover">

                                {{-- Info --}}
                                <div class="mt-2 bg-white z-20 text-center">
                                    <p class="font-semibold text-[15px] mt-2 text-[#58595B]">{{ $event['name'] }}</p>

                                    @php
                                        $details = trim(
                                            $event['position'] . ($event['department'] ? ' • ' . $event['department'] : ''),
                                        );
                                    @endphp

                                    @if ($details)
                                        <p class="text-[12px] tracking-wider font-light text-[#58595B]">{{ $details }}</p>
                                    @endif

                                    <p class="text-[12px] text-gray-400 mt-1">{{ $event['message'] }}</p>
                                </div>
                            </div>

                            {{-- Watermark --}}
                            <img src="{{ $event['type'] === 'birthday' ? '/assets/icons/birthday-cake.svg' : '/assets/icons/event.svg' }}"
                                class="pointer-events-none absolute {{ $event['type'] === 'birthday' ? 'right-0 -bottom-2' : 'right-1 -bottom-3' }} w-20 h-20 opacity-10 select-none"
                                alt="{{ ucfirst($event['type']) }} watermark">
                        </div>
                    </div>
                @empty
                    <div class="swiper-slide">
                        <div
                            class="relative h-[240px] flex flex-col items-center justify-center text-center text-sm text-gray-400 px-6">
                            <x-icons.event class="w-10 h-10 text-gray-300 mb-3" />
                            <p class="text-[14px] font-medium text-gray-500">@lang('general.no-events-today')</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="swiper-pagination !bottom-5"></div>
        </div>
    </div>

    {{-- Swiper Assets --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            new Swiper(".js-today-events .swiper", {
                slidesPerView: 1,
                loop: true,
                autoplay: {
                    delay: 4500,
                    disableOnInteraction: false
                },
                pagination: {
                    el: ".js-today-events .swiper-pagination",
                    clickable: true
                },
            });
        });
    </script>

    <style>
        .js-today-events .swiper-pagination-bullet {
            width: 6px;
            height: 6px;
            background: #d1d5db;
            opacity: 1;
        }

        .js-today-events .swiper-pagination-bullet-active {
            background: #6b7280;
        }
    </style>
</div>
