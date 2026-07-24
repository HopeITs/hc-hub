<div class="w-full h-full bg-white rounded-surface shadow-e1 overflow-hidden flex flex-col
            max-w-full md:max-w-md lg:max-w-full md:h-full">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 pt-4 pb-4 flex-shrink-0">

        <div class="flex items-center gap-2 md:gap-3">
            <h3 class="sm:text-h4 md:text-body font-semibold text-[#58595B]">
                {{ __('general.pending-delivers') }}
            </h3>

            <span class="text-secondary-light sm:text-h4 md:text-body font-medium">
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
                scrollbar-thin scrollbar-thumb-[var(--color-secondary-medium)]
                scrollbar-track-[var(--color-neutral-100)]
                hover:scrollbar-thumb-[var(--color-secondary-medium)]">

        @forelse ($pendingRequests as $req)
            <div class="flex justify-between items-center pb-2 border-b border-b-secondary-light"
                 wire:key="pending-request-{{ $req['id'] }}">

                <p class="sm:text-body md:text-caption font-bold text-[#58595B]">
                    {{ ucfirst($req['item']) }}
                </p>

                <span class=" text-caption sm:text-caption md:text-caption text-[#888]">
                    {{ $req['request_date'] }}
                </span>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center  text-body font-light text-secondary-light h-full">
                <span>@lang('general.no-available-data')</span>
            </div>
        @endforelse
    </div>
</div>
