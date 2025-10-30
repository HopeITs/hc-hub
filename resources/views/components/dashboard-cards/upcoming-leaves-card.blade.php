<div class="w-full bg-gradient-to-b from-white to-[var(--color-neutral-50)] rounded-3xl shadow-xl border border-[var(--color-neutral-200)] overflow-hidden">

    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 bg-primary">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-9 h-9 bg-white/20 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-white tracking-wide">Upcoming Leaves</h3>
        </div>
        <span class="text-xs font-semibold text-[var(--color-primary-700)] bg-white px-3 py-1 rounded-full shadow-sm">
            4 Scheduled
        </span>
    </div>

    <!-- Content -->
    <div class="p-6 max-h-[22rem] overflow-y-auto space-y-6 scrollbar-thin scrollbar-thumb-[var(--color-primary-200)] scrollbar-track-transparent">

        <!-- Leave Item -->
        <div class="group flex items-start gap-4 p-4 bg-white border border-[var(--color-neutral-200)] rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-200">
            <div class="flex flex-col items-center">
                <div class="w-3.5 h-3.5 rounded-full bg-[var(--color-success-500)] border-2 border-black shadow"></div>
                <div class="flex-1 w-px bg-[var(--color-neutral-200)] mt-2"></div>
            </div>
            <div class="flex-1">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-semibold text-[var(--color-neutral-900)]">Youssef Kharbout</p>
                    <span class="text-xs font-semibold text-[var(--color-success-600)] bg-[var(--color-success-50)] px-2 py-0.5 rounded-full">Approved</span>
                </div>
                <p class="text-xs text-[var(--color-neutral-600)] mt-1">Annual Leave • Mar 12 → Mar 18</p>
            </div>
        </div>

        <div class="group flex items-start gap-4 p-4 bg-white border border-[var(--color-neutral-200)] rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-200">
            <div class="flex flex-col items-center">
                <div class="w-3.5 h-3.5 rounded-full bg-[var(--color-warning-400)] border-2 border-black shadow"></div>
                <div class="flex-1 w-px bg-[var(--color-neutral-200)] mt-2"></div>
            </div>
            <div class="flex-1">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-semibold text-[var(--color-neutral-900)]">Rita Isbejerian</p>
                    <span class="text-xs font-semibold text-[var(--color-warning-700)] bg-[var(--color-warning-50)] px-2 py-0.5 rounded-full">Pending</span>
                </div>
                <p class="text-xs text-[var(--color-neutral-600)] mt-1">Sick Leave • Feb 10 → Feb 12</p>
            </div>
        </div>

        <div class="group flex items-start gap-4 p-4 bg-white border border-[var(--color-neutral-200)] rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-200">
            <div class="flex flex-col items-center">
                <div class="w-3.5 h-3.5 rounded-full bg-[var(--color-success-500)] border-2 border-black shadow"></div>
                <div class="flex-1 w-px bg-[var(--color-neutral-200)] mt-2"></div>
            </div>
            <div class="flex-1">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-semibold text-[var(--color-neutral-900)]">Mai Youssef</p>
                    <span class="text-xs font-semibold text-[var(--color-success-600)] bg-[var(--color-success-50)] px-2 py-0.5 rounded-full">Approved</span>
                </div>
                <p class="text-xs text-[var(--color-neutral-600)] mt-1">Maternity Leave • Apr 5 → Jun 5</p>
            </div>
        </div>

        <div class="group flex items-start gap-4 p-4 bg-white border border-[var(--color-neutral-200)] rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-200">
            <div class="flex flex-col items-center">
                <div class="w-3.5 h-3.5 rounded-full bg-[var(--color-error-500)] border-2 border-black shadow"></div>
            </div>
            <div class="flex-1">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-semibold text-[var(--color-neutral-900)]">Rami Nassar</p>
                    <span class="text-xs font-semibold text-[var(--color-error-600)] bg-[var(--color-error-50)] px-2 py-0.5 rounded-full">Rejected</span>
                </div>
                <p class="text-xs text-[var(--color-neutral-600)] mt-1">Emergency Leave • Jan 27 → Jan 29</p>
            </div>
        </div>

    </div>
</div>
