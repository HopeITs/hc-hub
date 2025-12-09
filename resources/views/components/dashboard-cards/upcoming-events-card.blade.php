@props(['upcomingEvents' => collect()])

<div class="w-full h-full bg-white rounded-3xl shadow-sm ring-1 ring-[var(--color-neutral-200)] overflow-hidden flex flex-col">
    <div class="flex items-center justify-between px-6 pt-5 pb-4 flex-shrink-0">
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-semibold text-[#58595B]">
                @lang('general.upcoming-events')
            </h3>
            <span class="text-secondary-light text-sm font-medium">
                {{ $upcomingEvents->count() }}
            </span>
            <x-icons.event class="ml-1 mb-0.5 text-secondary-medium"/>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-6 pb-4 space-y-4 scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-primary-400)]">
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
            <div class="flex flex-col items-center justify-center text-[var(--color-neutral-500)] text-sm py-16">
                <div class="text-4xl mb-2">📅</div>
                <p class="font-medium">@lang('general.no-events-today')</p>
            </div>
        @endforelse
    </div>
</div>
