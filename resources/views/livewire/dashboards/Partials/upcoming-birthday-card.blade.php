<div
    class="relative w-full max-w-sm h-[340px] bg-[var(--color-neutral-50)] rounded-3xl shadow-lg ring-1 ring-[var(--color-primary-400)] overflow-hidden">

    <div class="flex items-center gap-2 px-5 pt-4 pb-2">
        <div
            class="flex items-center justify-center h-8 w-8 rounded-lg bg-[var(--color-primary-500)] text-white text-lg shadow-sm">
            📅
        </div>
        <h2 class="text-lg font-extrabold text-[var(--color-primary)] tracking-tight">
            Upcoming Birthdays
        </h2>
    </div>

    <hr class="border-[var(--color-primary-200)] mx-5">

    <div class="relative p-5 space-y-4 overflow-y-auto scroll-smooth max-h-[270px]
                scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-primary-400)]">
        @forelse($upcomingBirthdays as $emp)
            <div
                class="flex items-start gap-4 p-4 bg-white rounded-2xl border border-[var(--color-primary-100)] hover:border-[var(--color-primary-300)] hover:shadow transition">
                <div class="relative">
                    <img
                        src="{{ $emp?->getFirstMediaUrl('avatar') ?: 'https://ui-avatars.com/api/?name=' . urlencode($emp?->name_en ?? $emp?->name_ar ?? 'Employee') }}"
                        alt="{{ $emp->name_en ?? $emp->name_ar }}"
                        class="h-12 w-12 rounded-full object-cover ring-2 ring-[var(--color-primary-500)]"
                    >
                </div>

                <div class="flex-1">
                    <p class="text-sm font-bold text-[var(--color-neutral-800)] leading-tight">
                        {{ $emp->name_en ?? $emp->name_ar }}
                    </p>

                    @if($emp->positionTitle || $emp->departmentName)
                        <p class="text-xs text-[var(--color-neutral-500)] mt-0.5">
                            {{ $emp->positionTitle ?? '' }}
                            @if($emp->departmentName)
                                <span class="text-[var(--color-neutral-400)]">· {{ $emp->departmentName }}</span>
                            @endif
                        </p>
                    @endif

                    <div class="flex flex-col items-end mt-1">
                        <p class="text-sm text-[var(--color-primary-600)] font-semibold flex items-center gap-1 self-start">
                            🎉 <span>{{ $emp->upcomingDob }}</span>
                        </p>
                        <p class="text-[11px] font-medium text-[var(--color-secondary-700)] italic mt-0.5">
                            {{ $emp->daysLeftText }}
                        </p>
                    </div>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center text-[var(--color-neutral-500)] text-sm py-10">
                <div class="text-4xl mb-2">🎈</div>
                <p class="font-medium">No upcoming birthdays this week</p>
            </div>
        @endforelse
    </div>
</div>
