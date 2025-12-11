<div class="w-full h-full bg-white rounded-3xl shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] overflow-hidden flex flex-col
            max-w-full md:max-w-md lg:max-w-full md:h-full">
    <!-- Header -->
    <div class="flex items-center justify-between
                px-4 md:px-6 pt-4 md:pt-5 pb-6 md:pb-10 flex-shrink-0">

        <div class="flex items-center gap-2 md:gap-3">
            <h3 class="sm:text-[20px] md:text-sm font-semibold text-[#58595B]">
                {{ __('general.pending-delivers') }}
            </h3>

            <span class="text-secondary-light sm:text-[20px] md:text-sm font-medium">
                {{ $pendingRequests->count() }}
            </span>

            <div class="text-primary w-4 h-4">
                <img src="{{ asset('assets/icons/pending-delivers.svg') }}"
                     alt="Pending delivers icon">
            </div>
        </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto
                px-4 md:px-6 pb-4 space-y-2
                scrollbar-thin scrollbar-thumb-[var(--color-primary-300)]
                scrollbar-track-[var(--color-neutral-100)]
                hover:scrollbar-thumb-[var(--color-primary-400)]">

        @forelse ($pendingRequests as $req)
            <div class="flex justify-between items-center pb-2 border-b border-b-secondary-light"
                 wire:key="pending-request-{{ $req['id'] }}">

                <p class="sm:text-[14px] md:text-[11px] font-bold text-[#58595B]">
                    {{ ucfirst($req['item']) }}
                </p>

                <span class=" text-[10px] sm:text-[10px] md:text-[11px] text-[#888]">
                    {{ $req['request_date'] }}
                </span>
            </div>
        @empty
            <div class="text-center text-[10px] md:text-[11px] italic text-secondary-light py-4">
                {{ __('general.no_pending_requests') }}
            </div>
        @endforelse
    </div>
</div>
