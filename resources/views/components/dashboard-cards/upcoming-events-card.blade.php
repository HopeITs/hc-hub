@props(['upcomingEvents' => collect()])

<div class="w-full h-full bg-white rounded-surface shadow-e1 ring-1 ring-[var(--color-neutral-200)] overflow-hidden flex flex-col">
    <div class="flex items-center justify-between px-6 pt-4 pb-4 flex-shrink-0">
        <div class="flex items-center gap-2">
            <h3 class="text-body font-semibold text-[#58595B]">
                @lang('general.upcoming-events')
            </h3>
            <span class="text-secondary-light text-body font-medium">
                {{ $upcomingEvents->count() }}
            </span>
            <x-icons.event class="ms-1 mb-1 text-secondary-medium"/>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-6 pb-4 space-y-4 scrollbar-thin scrollbar-thumb-[var(--color-secondary-medium)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-secondary-medium)]">
        @forelse ($upcomingEvents as $event)
            <div class="pb-3 border-b border-b-secondary-soft">
                <div class="flex justify-between items-center">
                    <p class="text-body font-bold text-[#58595B]">{{ $event->event_title }}</p>
                    <span class="text-caption h-4 w-15 font-medium tracking-wide mt-4 text-center text-secondary-light px-1 rounded-full">
                        {{ \Carbon\Carbon::parse($event->event_timestamp)->format('j/n/Y') }}
                    </span>
                </div>
                <p class="text-caption font-light -mt-1 text-secondary-medium line-clamp-2">
                    {{ \Illuminate\Support\Str::limit(strip_tags($event->event_description ?? ''), 120) }}
                </p>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center  text-body font-light text-secondary-light h-full">
                <span>@lang('general.no-available-data')</span>
            </div>
        @endforelse
    </div>
</div>
