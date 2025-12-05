@php
    $locale = app()->getLocale();
    $nameCol = "name_{$locale}";
@endphp

<div class="w-full h-full  bg-white rounded-3xl shadow-sm overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 pt-5 pb-10 flex-shrink-0">
        <div class="flex items-center gap-3">
            <h3 class="text-sm font-semibold text-[#58595B]">
                {{ __('general.personal-inventory') }}
            </h3>
            <span class="text-secondary-light text-sm font-medium">
                {{ $deliveredItems->count() }}
            </span>
            <div class="text-red-500 w-3.5 h-3.5 text-sm">
                <img src="{{ asset('assets/icons/personal-inventory.svg') }}" alt="Personal inventory icon">
            </div>
        </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto px-6 space-y-2 scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-primary-400)]">
        @forelse ($deliveredItems as $item)
            <div class="pb-2 border-b border-b-secondary-light">
                <div class="flex justify-between items-center">
                    <p class="text-[11px] font-bold text-[#58595B]">
                        {{ $item->item?->name ?? 'Item #'.$item->item_id }}
                    </p>
                    <span class="text-[9px] font-normal text-[#58595B]">
                        {{ optional($item->delivered_at)->format('d/m/Y') }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center text-[11px] italic text-secondary-light py-4">
                {{ __('No delivered items found.') }}
            </div>
        @endforelse
    </div>
</div>
