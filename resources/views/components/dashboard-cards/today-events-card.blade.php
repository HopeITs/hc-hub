<div
    class="relative w-full h-[300px] max-w-xs bg-[var(--color-neutral-50)] rounded-2xl shadow-lg ring-1 ring-[var(--color-primary-500)] overflow-hidden">
    <div class="absolute inset-0 bg-[url('/images/confetti.svg')] bg-cover bg-center opacity-10 pointer-events-none">
    </div>

    <div
        class="relative px-4 py-5 border-b border-[var(--color-primary)] flex items-center justify-between bg-[var(--color-primary)]">
        <span class="text-xl animate-bounce">📅</span>
        <h2 class="text-lg font-bold text-[var(--color-white)] tracking-tight">Today’s Events</h2>
        <span class="text-[10px] text-[var(--color-neutral-100)] font-medium">{{ now()->format('d M Y') }}</span>
    </div>

    <div
        class="relative p-4 space-y-3 overflow-y-auto bg-[var(--color-neutral-50)] scroll-smooth max-h-[220px] scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-primary-400)]">
        @forelse($todayEvents as $event)
            @if ($event->event_type === 'birthday')
                <div
                    class="flex items-center space-x-3 p-3 bg-white rounded-xl shadow-md hover:shadow-lg transition transform hover:-translate-y-1 border border-[var(--color-primary-100)]">
                    <div class="relative">
                        <img src="{{ $event->getFirstMediaUrl('avatar') ?: 'https://ui-avatars.com/api/?name=' . urlencode($event->name_en ?? ($event->name_ar ?? 'Employee')) }}"
                            alt="{{ $event->name_en ?? $event->name_ar }}"
                            class="h-12 w-12 rounded-full object-cover ring-2 ring-[var(--color-primary-500)] shadow-sm">
                    </div>

                    <div class="flex-1">
                        <p class="text-sm font-bold text-[var(--color-neutral-800)] leading-tight">
                            {{ $event->name_en ?? $event->name_ar }}
                        </p>
                        @if ($event->positionTitle || $event->departmentName)
                            <p class="text-[11px] text-[var(--color-neutral-500)]">
                                {{ $event->positionTitle ?? '' }}
                                @if ($event->departmentName)
                                    <span class="text-[var(--color-neutral-400)]"> · {{ $event->departmentName }}</span>
                                @endif
                            </p>
                        @endif
                        <p class="text-sm text-[var(--color-primary-600)] font-semibold mt-0.5">
                            {{ $event->event_title }} 🎉
                        </p>
                    </div>
                </div>
            @elseif ($event->event_type === 'news')
                <div
                    class="flex items-center space-x-3 p-3 bg-white rounded-xl shadow-md hover:shadow-lg transition transform hover:-translate-y-1 border border-[var(--color-primary-100)]">
                    <div class="relative">
                        <img src="{{ $event->image_url }}" alt="{{ $event->event_title }}"
                            class="h-12 w-12 rounded-full object-cover ring-2 ring-[var(--color-primary-500)] shadow-sm">
                    </div>

                    <div class="flex-1">
                        <p class="text-sm font-bold text-[var(--color-neutral-800)] leading-tight">
                            {{ $event->event_title }}
                        </p>
                        <p class="text-[11px] text-[var(--color-neutral-500)] mt-0.5 line-clamp-2">
                            {{ $event->event_description }}
                        </p>
                        <p class="text-[10px] text-[var(--color-primary-600)] font-semibold mt-0.5">
                            News Post
                        </p>
                    </div>
                </div>
            @endif
        @empty
            <div class="flex flex-col items-center justify-center text-[var(--color-neutral-500)] text-sm py-8">
                <div class="text-3xl mb-1">🥳</div>
                <p class="font-medium">No events today</p>
            </div>
        @endforelse
    </div>

    <div class="absolute bottom-0 left-0 w-full bg-[var(--color-primary-500)] text-center py-1.5">
        <p class="text-[10px] font-semibold text-white tracking-wide uppercase">Celebrate your team and stay informed!
        </p>
    </div>
</div>
