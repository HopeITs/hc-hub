<div class="w-full bg-white rounded-2xl shadow border border-[var(--color-neutral-200)] overflow-hidden">
    <!-- Header -->
    <div class="flex justify-between items-center px-6 py-4 bg-[var(--color-primary)]">
        <h3 class="text-lg font-semibold text-white">
            {{ __('Upcoming Holidays') }}
        </h3>
        <span class="text-sm bg-white/20 px-3 py-1 rounded-full text-white font-medium">
            3 Upcoming
        </span>
    </div>

    <!-- Content -->
    <div class="max-h-[18rem] overflow-y-auto divide-y divide-[var(--color-neutral-200)] scrollbar-thin scrollbar-thumb-[var(--color-neutral-300)] scrollbar-track-[var(--color-neutral-100)]">
        <!-- Holiday 1 -->
        <div class="flex justify-between items-start px-6 py-4 hover:bg-[var(--color-neutral-50)] transition">
            <div>
                <p class="text-sm font-semibold text-[var(--color-neutral-900)]">Eid al-Fitr</p>
                <p class="text-xs text-[var(--color-neutral-600)] mt-1">Starts: 2025-03-30</p>
                <p class="text-xs text-[var(--color-neutral-600)]">Ends: 2025-04-01</p>
            </div>
            <span class="text-xs font-medium text-[var(--color-primary-600)] bg-[var(--color-primary-100)] px-2 py-0.5 rounded-full">
                National
            </span>
        </div>

        <!-- Holiday 2 -->
        <div class="flex justify-between items-start px-6 py-4 hover:bg-[var(--color-neutral-50)] transition">
            <div>
                <p class="text-sm font-semibold text-[var(--color-neutral-900)]">Independence Day</p>
                <p class="text-xs text-[var(--color-neutral-600)] mt-1">Date: 2025-05-25</p>
            </div>
            <span class="text-xs font-medium text-[var(--color-primary-600)] bg-[var(--color-primary-100)] px-2 py-0.5 rounded-full">
                Public
            </span>
        </div>

        <!-- Holiday 3 -->
        <div class="flex justify-between items-start px-6 py-4 hover:bg-[var(--color-neutral-50)] transition">
            <div>
                <p class="text-sm font-semibold text-[var(--color-neutral-900)]">Christmas</p>
                <p class="text-xs text-[var(--color-neutral-600)] mt-1">Date: 2025-12-25</p>
            </div>
            <span class="text-xs font-medium text-[var(--color-primary-600)] bg-[var(--color-primary-100)] px-2 py-0.5 rounded-full">
                Christian
            </span>
        </div>
    </div>
</div>
