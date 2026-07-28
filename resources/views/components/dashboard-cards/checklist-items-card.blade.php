@php
    $locale = app()->getLocale();
    $nameCol = "name_{$locale}";
@endphp

<div class="flex h-full w-full flex-col overflow-hidden rounded-3xl bg-white shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)]">
    <!-- Header -->
    <div class="flex shrink-0 items-center justify-start px-4 pb-4 pt-5 sm:px-6">

        <div class="flex flex-wrap items-center gap-2">
            <h3 class="text-sm font-semibold leading-none text-[#58595B]">
                {{ __('general.personal-inventory') }}
            </h3>

            <span class="relative -top-px text-sm font-medium leading-none text-secondary-light">
                {{ $deliveredItems->count() }}
            </span>

            <div class="relative -top-px flex size-4 shrink-0 items-center justify-center text-primary">
                <img class="block size-full object-contain" src="{{ asset('assets/icons/personal-inventory.svg') }}"
                     alt="Personal inventory icon">
            </div>
        </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto
                px-4 md:px-6 pb-4 space-y-2
                scrollbar-thin scrollbar-thumb-[var(--color-secondary-medium)]
                scrollbar-track-[var(--color-neutral-100)]
                hover:scrollbar-thumb-[var(--color-secondary-medium)]">

        @forelse ($deliveredItems as $item)
            <div class="pb-2 border-b border-b-secondary-light">

                <div class="flex justify-between items-center">
                    <p class="text-[13px] font-bold text-[#58595B]">
                        {{ $item->item?->name ?? 'Item #'.$item->item_id }}
                    </p>

                    <span class="text-[11px] text-[#58595B]">
                        {{ optional($item->delivered_at)->format('d/m/Y') }}
                    </span>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center  text-sm font-light text-secondary-light h-full">
                <span>@lang('general.no-available-data')</span>
            </div>
        @endforelse

    </div>
</div>
