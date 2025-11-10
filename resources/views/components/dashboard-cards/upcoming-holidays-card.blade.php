<div class="w-full h-full max-w-md bg-primary rounded-3xl shadow-sm ring-1 ring-[var(--color-neutral-200)] overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 pt-5 pb-4 flex-shrink-0">
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-semibold text-white">
                Upcoming Events
            </h3>
            <span class="text-white text-sm font-medium">
                10
            </span>
            <div class="text-white">
                <x-icons.calendar/>
            </div>
        </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto pt-4 px-6 pb-4 space-y-4 scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-primary-400)]">
        <!-- Leave Item -->
        <div class="pb-4 pt-1 border-b border-b-secondary-soft">
            <div class="flex justify-between items-center">
                <p class="text-[15px] font-bold text-white">Western Easter</p>
                <span class="text-[9.5px] h-4 w-[110px] font-light tracking-wide text-center text-white px-1  rounded-full">1/4/2026 - 20/4/2026</span>
            </div>
        </div>

        <div class="pb-4 pt-1 border-b border-b-secondary-soft">
            <div class="flex justify-between items-center">
                <p class="text-[15px] font-bold text-white">Eastern Easter</p>
                <span class="text-[9.5px] h-4 w-[110px] font-light tracking-wide text-center text-white px-1  rounded-full">1/4/2026 - 20/4/2026</span>
            </div>
        </div>

        <div class="pb-4 pt-1  border-b border-b-secondary-soft">
            <div class="flex justify-between items-center">
                <p class="text-[15px] font-bold text-white">Christmas Easter</p>
                <span class="text-[9.5px] h-4 w-[110px] font-light tracking-wide text-center text-white px-1  rounded-full">1/4/2026 - 20/4/2026</span>
            </div>
        </div>

    </div>
</div>
