<div class="w-full h-full max-w-md bg-white rounded-3xl shadow-sm  overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 pt-5 pb-10 flex-shrink-0">
        <div class="flex items-center gap-3">
            <h3 class="text-sm font-semibold text-[#58595B]">
                Badges and warnings
            </h3>
            <span class="text-secondary-light text-sm font-medium">
                2
            </span>
            <div class="text-red-500 w-4 h-4 text-sm">
                <img src="{{ asset('assets/icons/badges.svg') }}" alt="Notes icon">
            </div>
        </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto  px-6 pb-4 space-y-2 scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-primary-400)]">
        <!-- Leave Item -->
        <div class="flex justify-between items-center pb-2 border-b border-b-secondary-light">
            <p class="text-[11px] font-bold uppercase text-primary">warning about late arrival to work</p>
        </div>

        <div class="flex justify-between items-center pb-2 border-b border-b-secondary-light">
            <p class="text-[11px] font-bold uppercase text-[#58595B]">thanks and appreciation</p>
        </div>

    </div>
</div>
