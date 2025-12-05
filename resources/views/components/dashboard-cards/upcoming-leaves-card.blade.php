<div class="w-full h-full  bg-white rounded-3xl shadow-sm ring-1 ring-[var(--color-neutral-200)] overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 pt-5 pb-4 flex-shrink-0">
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-semibold text-[#58595B]">
                @lang('general.upcoming-leaves')
            </h3>
            <span class="text-secondary-light text-sm font-medium">
                4
            </span>
            <div class="text-red-500 w-4 h-4 text-sm">
                <img src="{{ asset('assets/icons/upcoming-leaves.svg') }}" alt="Notes icon">
            </div>
        </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto px-6 pb-4 space-y-4 scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-primary-400)]">
        <!-- Leave Item -->
        <div class="pb-3  border-b border-b-secondary-soft">
            <div class="flex justify-between items-center">
                <p class="text-[13px] font-bold text-[#58595B]">Fredy Youssef</p>
                <span class="text-[10px] h-5.5 w-16 font-normal tracking-wide mt-4 text-center text-white bg-primary px-1 py-1  rounded-full">Approved</span>
            </div>
            <p class="text-[10px] font-light -mt-1 text-gray-500">Annual Leave 12 Mar – 18 Mar</p>
        </div>

        <div class="pb-3  border-b border-b-secondary-soft">
            <div class="flex justify-between items-center">
                <p class="text-[13px] font-bold text-[#58595B]">Safir Salim</p>
                <span class="text-[10px] h-5.5 w-16 font-normal tracking-wide mt-4 text-center text-white bg-secondary-light px-1 py-1  rounded-full">Approved</span>

            </div>
            <p class="text-[10px] font-light -mt-1 text-gray-500">Annual Leave 12 Mar – 18 Mar</p>
        </div>

        <div class="pb-3  border-b border-b-secondary-soft">
            <div class="flex justify-between items-center">
                <p class="text-[13px] font-bold text-[#58595B]">Rama Daaboul</p>
                <span class="text-[10px] h-5.5 w-16 font-normal tracking-wide mt-4 text-center text-white bg-primary px-1 py-1  rounded-full">Approved</span>

            </div>
            <p class="text-[10px] font-light -mt-1 text-gray-500">Annual Leave 12 Mar – 18 Mar</p>

        </div>

        <div class="pb-3  border-b border-b-secondary-soft">
            <div class="flex justify-between items-center">
                <p class="text-[13px] font-bold text-[#58595B]">Mai Youssef</p>
                <span class="text-[10px] h-5.5 w-16 font-normal tracking-wide mt-4 text-center text-white bg-primary px-1 py-1  rounded-full">Approved</span>
            </div>
            <p class="text-[10px] font-light -mt-1 text-gray-500">Annual Leave 12 Mar – 18 Mar</p>
        </div>
    </div>
</div>
