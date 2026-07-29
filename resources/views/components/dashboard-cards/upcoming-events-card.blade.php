@props(['upcomingEvents' => collect()])

<div class="w-full h-full bg-white rounded-3xl shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] ring-1 ring-[var(--color-neutral-200)] overflow-hidden flex flex-col">
    <x-dashboard-cards.card-header :title="__('general.upcoming-events')" :count="$upcomingEvents->count()">
        <x-slot:icon>
            <x-icons.event class="size-full"/>
        </x-slot:icon>
    </x-dashboard-cards.card-header>

    <div class="flex-1 overflow-y-auto px-6 pb-4 space-y-4 scrollbar-thin scrollbar-thumb-[var(--color-secondary-medium)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-secondary-medium)]">
        @forelse ($upcomingEvents as $event)
            <div class="pb-3 border-b border-b-secondary-soft">
                <div class="flex justify-between items-center">
                    <p class="text-[13px] font-bold text-[#58595B]">{{ $event->event_title }}</p>
                    <span class="text-[10px] h-4 w-15 font-medium tracking-wide mt-4 text-center text-secondary-light px-1 rounded-full">
                        {{ \Carbon\Carbon::parse($event->event_timestamp)->format('j/n/Y') }}
                    </span>
                </div>
                <p class="text-[10px] font-light -mt-1 text-gray-500 line-clamp-2">
                    {{ \Illuminate\Support\Str::limit(strip_tags($event->event_description ?? ''), 120) }}
                </p>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center  text-sm font-light text-secondary-light h-full">
                <span>@lang('general.no-available-data')</span>
            </div>
        @endforelse
    </div>
</div>
