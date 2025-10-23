<div class="w-full bg-white rounded-2xl shadow border border-[var(--color-neutral-200)] overflow-hidden">
    <!-- Header -->
    <div class="flex justify-between items-center px-6 py-4 bg-[var(--color-primary)]">
        <h3 class="text-lg font-semibold text-white">
            {{ __('general.pending_checklist_requests') }}
        </h3>
        <span class="text-sm bg-white/20 px-3 py-1 rounded-full text-white font-medium">
            {{ $pendingRequests->count() }} {{ __('Pending') }}
        </span>
    </div>

    @if($pendingRequests->isEmpty())
        <div class="text-center text-[var(--color-secondary-light)] italic py-10 bg-[var(--color-neutral-50)]">
            {{ __('general.no_pending_requests') }}
        </div>
    @else
        <!-- Scrollable list (3 visible cards max) -->
        <div class="max-h-[18rem] overflow-y-auto divide-y divide-[var(--color-neutral-200)] scrollbar-thin scrollbar-thumb-[var(--color-neutral-300)] scrollbar-track-[var(--color-neutral-100)]">
            @foreach ($pendingRequests as $req)
                <div class="flex flex-col px-6 py-4 hover:bg-[var(--color-neutral-50)] transition group" wire:key="pending-request-{{ $req['id'] }}">
                    <div class="flex items-center justify-between">
                        <!-- Item name + recipient -->
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-[var(--color-neutral-900)]">{{ ucfirst($req['item']) }}</p>
                            <span class="text-[var(--color-neutral-400)] text-sm">→</span>
                            <span class="text-sm font-medium text-[var(--color-neutral-700)]">For {{ $req['employee_name'] }}</span>
                        </div>

                        <!-- Compact icon button -->
                        <a href="{{ route('checklist-items.pending-delivers') }}"
                           class="inline-flex items-center justify-center w-8 h-8 bg-[var(--color-primary-500)] text-white rounded-md hover:bg-[var(--color-primary-600)]  transition shadow-sm"
                           title="Go to Pending Delivers">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                    <!-- Date line -->
                    <div class="mt-1 text-xs text-[var(--color-neutral-500)]">
                        {{ __('general.request_date') }}:
                        <span class="font-medium text-[var(--color-neutral-700)]">{{ $req['request_date'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
