<div
    class="relative w-full max-w-sm h-[340px] bg-[var(--color-neutral-50)] rounded-3xl shadow-md ring-1 ring-primary overflow-hidden">

    <div class="flex items-center justify-between px-5 pt-4 pb-3">
        <div class="flex items-center gap-2">
            <div
                class="flex items-center justify-center h-8 w-8 rounded-lg bg-[var(--color-primary-500)] text-white shadow">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 7V3m8 4V3m-9 8h10m-11 4h12m-13 4h14M3 9h18v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                </svg>
            </div>
            <h2 class="text-lg font-extrabold text-[var(--color-primary)] tracking-tight">
                Leave Requests
            </h2>
        </div>

        <span
            class="text-xs font-semibold bg-[var(--color-primary-100)] text-[var(--color-primary-700)] px-2 py-0.5 rounded-full shadow-sm">
      6 Pending
    </span>
    </div>

    <hr class="border-primary mx-5">

    <div
        class="relative p-4 space-y-2 overflow-y-auto scroll-smooth max-h-[270px]
           scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)]
           hover:scrollbar-thumb-[var(--color-primary-400)]">

        <div
            class="group flex items-center justify-between p-3 bg-white rounded-2xl border border-[var(--color-neutral-200)]
             hover:border-[var(--color-primary-400)] hover:shadow-md transition-all duration-200">

            <div class="flex items-center gap-3">
                <img
                    src="https://randomuser.me/api/portraits/men/32.jpg"
                    alt="Employee"
                    class="h-10 w-10 rounded-full object-cover ring-2 ring-[var(--color-primary-200)] group-hover:ring-[var(--color-primary-400)] transition">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-[var(--color-neutral-800)] leading-tight truncate">
                        Youssef kharbout
                    </p>
                    <p class="text-xs text-[var(--color-neutral-500)] truncate">
                        Annual Leave · <span class="text-[var(--color-primary-600)] font-medium">Pending</span>
                    </p>
                    <p class="text-[11px] text-[var(--color-neutral-400)] mt-0.5">Dec 1 – Dec 5</p>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                <button title="View Details"
                        class="p-1.5 rounded-md hover:bg-[var(--color-primary-50)] hover:text-[var(--color-primary-600)] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
                <button title="Approve"
                        class="p-1.5 rounded-md hover:bg-green-50 hover:text-green-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
                <button title="Reject"
                        class="p-1.5 rounded-md hover:bg-red-50 hover:text-red-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <div
            class="group flex items-center justify-between p-3 bg-white rounded-2xl border border-[var(--color-neutral-200)]
             hover:border-[var(--color-primary-400)] hover:shadow-md transition-all duration-200">
            <div class="flex items-center gap-3">
                <img
                    src="https://randomuser.me/api/portraits/women/46.jpg"
                    alt="Employee"
                    class="h-10 w-10 rounded-full object-cover ring-2 ring-[var(--color-primary-200)] group-hover:ring-[var(--color-primary-400)] transition">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-[var(--color-neutral-800)] leading-tight truncate">
                        Rita Isbejerian
                    </p>
                    <p class="text-xs text-[var(--color-neutral-500)] truncate">
                        Sick Leave · <span class="text-[var(--color-primary-600)] font-medium">Pending</span>
                    </p>
                    <p class="text-[11px] text-[var(--color-neutral-400)] mt-0.5">Feb 10 – Feb 12</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <button title="View Details"
                        class="p-1.5 rounded-md hover:bg-[var(--color-primary-50)] hover:text-[var(--color-primary-600)] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
                <button title="Approve"
                        class="p-1.5 rounded-md hover:bg-green-50 hover:text-green-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
                <button title="Reject"
                        class="p-1.5 rounded-md hover:bg-red-50 hover:text-red-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

    </div>
</div>
