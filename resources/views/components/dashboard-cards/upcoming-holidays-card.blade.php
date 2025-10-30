<div class="w-full rounded-2xl border border-[var(--color-neutral-200)] bg-white shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 bg-primary">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-9 h-9 bg-white/20 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-white tracking-wide">Upcoming Holidays</h3>
        </div>
        <span class="text-xs font-semibold text-[var(--color-primary-700)] bg-white px-3 py-1 rounded-full shadow-sm">
            3 Upcoming
        </span>
    </div>

    <!-- Content -->
    <div class="p-4 divide-y divide-[var(--color-neutral-100)] max-h-[18rem] overflow-y-auto scrollbar-thin scrollbar-thumb-[var(--color-neutral-300)] scrollbar-track-transparent">

        <!-- Holiday item -->
        <div class="flex justify-between items-center py-3 hover:bg-[var(--color-neutral-50)] transition">
            <div>
                <p class="font-semibold text-[var(--color-neutral-900)]">Eid al-Fitr</p>
                <p class="text-xs text-[var(--color-neutral-600)] mt-0.5">Mar 30 → Apr 1</p>
            </div>
            <span class="text-xs font-semibold text-[var(--color-primary-700)] border border-[var(--color-primary-200)] bg-[var(--color-primary-50)] px-2.5 py-0.5 rounded-full">
                National
            </span>
        </div>

        <div class="flex justify-between items-center py-3 hover:bg-[var(--color-neutral-50)] transition">
            <div>
                <p class="font-semibold text-[var(--color-neutral-900)]">Independence Day</p>
                <p class="text-xs text-[var(--color-neutral-600)] mt-0.5">May 25</p>
            </div>
            <span class="text-xs font-semibold text-rose-700 border border-rose-200 bg-rose-50 px-2.5 py-0.5 rounded-full">
                Public
            </span>
        </div>

        <div class="flex justify-between items-center py-3 hover:bg-[var(--color-neutral-50)] transition">
            <div>
                <p class="font-semibold text-[var(--color-neutral-900)]">Christmas</p>
                <p class="text-xs text-[var(--color-neutral-600)] mt-0.5">Dec 25</p>
            </div>
            <span class="text-xs font-semibold text-amber-700 border border-amber-200 bg-amber-50 px-2.5 py-0.5 rounded-full">
                Christian
            </span>
        </div>
    </div>
</div>
