<div class="w-full h-full bg-white rounded-3xl shadow-sm overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 pt-5 pb-10 flex-shrink-0">
        <div class="flex items-center gap-3">
            <h3 class="text-sm font-semibold text-[#58595B]">
                {{ __('general.pending-delivers') }}
            </h3>
            <span class="text-secondary-light text-sm font-medium">
                {{ $pendingRequests->count() }}
            </span>
            <div class="text-red-500 w-4 h-4 text-sm">
                <img src="{{ asset('assets/icons/pending-delivers.svg') }}" alt="Pending delivers icon">
            </div>
        </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto px-6 pb-4 space-y-2 scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-primary-400)]">
        @forelse ($pendingRequests as $req)
            <div class="flex justify-between items-center pb-2 border-b border-b-secondary-light" wire:key="pending-request-{{ $req['id'] }}">
                <p class="text-[11px] font-bold text-[#58595B]">
                    {{ ucfirst($req['item']) }}
                </p>
                <span class="text-[9px] text-[#888]">{{ $req['request_date'] }}</span>
            </div>
        @empty
            <div class="text-center text-[11px] italic text-secondary-light py-4">
                {{ __('general.no_pending_requests') }}
            </div>
        @endforelse
    </div>
</div>
