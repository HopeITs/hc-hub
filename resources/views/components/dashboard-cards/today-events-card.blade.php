@php
    use App\Models\Employee;
    use App\Models\News;
    use Carbon\Carbon;

    $today = Carbon::today();
    $events = collect();

    $birthdays = Employee::whereNotNull('actual_dob')
        ->get()
        ->filter(fn($e) => optional($e->actual_dob)->format('m-d') === $today->format('m-d'))
        ->map(function ($employee) {
            $contract = $employee->contracts()
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->with('position')
                ->latest()
                ->first();

            $position = $contract?->position?->name ?? '';

            $photo = $employee->getFirstMediaUrl('avatar') ?: 'https://ui-avatars.com/api/?name=' . urlencode($employee->name) . '&color=be1f24&background=FFF';

            return [
                'type' => 'birthday',
                'name' => $employee->name,
                'position' => $position,
                'message' => 'Happy Birthday! 🎉',
                'photo' => $photo,
            ];
        });

    $newsEvents = News::whereDate('event_date', $today)
        ->latest()
        ->get()
        ->map(function ($news) {
            $photo = $news->photo ? asset('storage/' . $news->photo) : '/assets/icons/event.svg';
            return [
                'type' => 'event',
                'name' => $news->title,
                'position' => $news->newsable_name ?? '',
                'message' => strip_tags($news->description ?? 'Event Today'),
                'photo' => $photo,
            ];
        });

    $events = $birthdays->concat($newsEvents);
@endphp

<div class="js-today-events w-full h-full max-w-md bg-white rounded-3xl shadow-sm ring-1 ring-gray-200 overflow-hidden relative">
    <div class="flex items-center justify-between px-7 pt-6">
        <div class="flex items-center gap-2">
            <span class="text-[13px] font-medium text-[#58595B]">Today's Events</span>
            <span class="text-[13px] font-light text-secondary-light">{{ now()->format('n/j/Y') }}</span>
            <img src="/assets/icons/birthday-cake.svg" alt="Birthday watermark icon" class="w-5 h-5 select-none">
        </div>
    </div>

    <div class="swiper">
        <div class="swiper-wrapper">
            @forelse($events as $event)
                <div class="swiper-slide">
                    <div class="relative h-[240px] flex flex-col items-center justify-center">
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center"></div>

                        <span class="confetti-anchor relative inline-flex">
                            <img
                                src="{{ $event['photo'] }}"
                                alt="{{ $event['name'] }}"
                                class="relative z-10 w-18 h-18 rounded-full object-cover">
                        </span>

                        <div class="mt-2 z-10 text-center">
                            <p class="font-semibold text-[14px] text-[#58595B]">{{ $event['name'] }}</p>
                            @if(!empty($event['position']))
                                <p class="text-[9.5px] tracking-wider font-light text-[#58595B]">{{ $event['position'] }}</p>
                            @endif
                            <p class="text-[9px] text-gray-400 mt-1">{{ $event['message'] }}</p>
                        </div>

                        <div class="pointer-events-none absolute {{ $event['type'] === 'birthday' ? 'right-0 -bottom-2' : 'right-1 -bottom-3' }} opacity-10">
                            <img
                                src="{{ $event['type'] === 'birthday' ? '/assets/icons/birthday-cake.svg' : '/assets/icons/event.svg' }}"
                                alt="{{ ucfirst($event['type']) }} watermark icon"
                                class="w-20 h-20 select-none">
                        </div>
                    </div>
                </div>
            @empty
                <div class="swiper-slide">
                    <div class="h-[240px] flex items-center justify-center text-center text-sm text-gray-400">
                        No events today
                    </div>
                </div>
            @endforelse
        </div>

        <div class="swiper-pagination !bottom-5"></div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        new Swiper(".js-today-events .swiper", {
            slidesPerView: 1,
            loop: true,
            autoplay: { delay: 4500, disableOnInteraction: false },
            pagination: { el: ".js-today-events .swiper-pagination", clickable: true },
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
