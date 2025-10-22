@php
    $locale = app()->getLocale();
    $nameCol = "name_{$locale}";
@endphp

<div class="w-full bg-[var(--color-neutral-50)] rounded-3xl shadow-md overflow-hidden border border-[var(--color-neutral-200)]">
    <div class="px-6 py-4 flex items-center justify-between"
         style="background: var(--color-primary); color: var(--color-white);">
        <h2 class="text-xl font-bold tracking-wide">{{ __('Personal Inventory') }}</h2>
        <span class="text-sm opacity-90">{{ $deliveredItems->count() }} {{ __('items') }}</span>
    </div>

    <div class="divide-y divide-[var(--color-neutral-200)]">
        @forelse ($deliveredItems as $item)
            <div class="flex items-center justify-between px-6 py-4 hover:bg-[var(--color-neutral-100)] transition-colors duration-200">
                <div class="flex flex-col">
                    <span class="text-[var(--color-neutral-800)] font-semibold text-base">
                        {{ $item->item?->name ?? 'Item #'.$item->item_id }}
                    </span>

                    <span class="text-sm text-[var(--color-neutral-500)]">
                        {{ __('Delivered by') }}
                        <strong class="text-[var(--color-neutral-700)]">
                            {{ $item->delivered?->$nameCol ?? __('Unknown') }}
                        </strong>
                    </span>
                </div>

                <div class="text-right">
                    <span class="text-sm text-[var(--color-neutral-600)]">
                        {{ optional($item->delivered_at)->format('d M Y, h:i A') }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-10 text-[var(--color-neutral-400)] text-sm">
                {{ __('No delivered items found.') }}
            </div>
        @endforelse
    </div>
</div>
