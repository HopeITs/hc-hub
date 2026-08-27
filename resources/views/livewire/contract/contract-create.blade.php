@php
    use Carbon\Carbon;

    $locale = app()->getLocale();
    $heading = $renewalSource ? __('general.renew-contract') : __('general.add-new');

    /*
     * Day names come from Carbon rather than a hand-written list, so the two
     * strings written into work_days_en / work_days_ar always match the names
     * the rest of the application prints. Monday-first.
     */
    $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
    $dayNames = [];
    $dayShort = [];

    foreach (['en', 'ar'] as $dayLocale) {
        foreach (range(0, 6) as $offset) {
            $day = $weekStart->copy()->addDays($offset)->locale($dayLocale);
            $dayNames[$dayLocale][$offset] = $day->dayName;
            $dayShort[$dayLocale][$offset] = $day->shortDayName;
        }
    }

    $composerConfig = [
        'locale' => $locale,
        'cityId' => $lockedCity?->id ?? ($renewalSeed['cityId'] ?? null),
        'cities' => $cities->map(fn ($city) => ['value' => $city->id, 'label' => $city->name])->values(),
        'dayNames' => $dayNames,
        'dayShort' => $dayShort,
        // The Arabic string is always joined with the Arabic comma, whatever
        // locale the interface happens to be in.
        'listSeparator' => '، ',
        'typeDefaults' => $types->mapWithKeys(fn ($type) => [
            (string) $type->id => [
                'name' => $type->name,
                'start' => $type->startDate,
                'end' => $type->endDate,
            ],
        ]),
        // Present only when this page was opened as a renewal, so Alpine can
        // seed every field from the source contract instead of opening blank.
        'renewal' => $renewalSeed,
        'labels' => [
            'allCenters' => __('general.composer.all-centers'),
            'cityLevel' => __('general.composer.city-level'),
            'lockedBecause' => __('general.composer.locked-because'),
            'sections' => [
                'assignment' => __('general.composer.assignment'),
                'contract' => __('general.composer.contract'),
                'schedule' => __('general.composer.schedule'),
                'pay' => __('general.composer.pay'),
            ],
            // Passed with their :placeholders intact; the composer substitutes
            // them client-side so no formatting logic is duplicated in PHP.
            'progress' => __('general.composer.progress'),
            'seatsLeft' => __('general.composer.seats-left'),
            'noSeatsLeft' => __('general.composer.no-seats-left'),
            'duplicate' => __('general.composer.duplicate-contract'),
            'derivation' => __('general.composer.hours-derivation'),
            // Lowercase units, because these are read inside a phrase
            // ("3 months") rather than as standalone labels.
            'month' => __('general.composer.duration-month'),
            'months' => __('general.composer.duration-months'),
            'day' => __('general.composer.duration-day'),
            'days' => __('general.composer.duration-days'),
            'daysUnit' => __('general.composer.days-unit'),
            'unknownCurrency' => __('general.unknown'),
            'perMonth' => __('general.composer.per-month'),
            'perHour' => __('general.composer.per-hour'),
            'perSession' => __('general.composer.per-session'),
            'perYear' => __('general.composer.per-year'),
            'empty' => '—',
        ],
    ];
@endphp

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <livewire:layout.breadcrumbs :title="$heading" :crumbs="[
        ['label' => trans_choice('general.employee-management', 2)],
        ['label' => trans_choice('general.contracts', 2), 'url' => route('contracts.index')],
        ['label' => $heading],
    ]"/>

    @if($renewalSource)
        {{-- Grey surface with a solid red edge. Never `bg-primary/5` here: a
             low-opacity brand red reads as pink, which app.css disallows. --}}
        <div class="mt-2 flex items-center gap-4 rounded-control border border-neutral-200 border-s-4 border-s-primary
                    bg-neutral-50 px-4 py-3">
            <img
                src="{{ $renewalSource->employee?->avatar_url ?: asset('images/default-avatar.png') }}"
                alt=""
                loading="lazy"
                class="size-11 shrink-0 rounded-full object-cover bg-neutral-100 ring-1 ring-neutral-300"
            />
            <div class="min-w-0">
                {{-- Escaped output, not @lang: the translator substitutes :name raw,
                     so @lang would inject the stored employee name as markup. --}}
                <p class="truncate text-body font-semibold text-neutral-900">
                    {{ __('general.renewing-contract-for', ['name' => $renewalSource->employee?->name ?? __('general.na')]) }}
                </p>
                <p class="mt-0.5 text-caption tabular-nums text-neutral-600">
                    <span class="font-medium">@lang('general.current-period'):</span>
                    {{ $renewalSource->start_date?->format('d-m-Y') }} — {{ $renewalSource->end_date?->format('d-m-Y') }}
                </p>
            </div>
        </div>
    @endif

    <div
        x-data="contractComposer(@js($composerConfig))"
        x-init="initialize()"
        class="pb-2 max-lg:pb-28"
    >
        {{--
            items-stretch is what actually guarantees the two columns share a
            height: a grid row is as tall as its tallest cell, and both cells
            fill it. Neither side is tuned to the other.
        --}}
        <div class="grid items-stretch gap-4 lg:grid-cols-[minmax(0,1fr)_18rem] xl:gap-5">
            <div class="flex min-w-0 flex-col gap-2.5">
                @include('livewire.contract.create._assignment')
                @include('livewire.contract.create._contract')
                @include('livewire.contract.create._schedule')
                @include('livewire.contract.create._pay')
                @include('livewire.contract.create._action-bar')
            </div>

            <aside class="hidden lg:block">
                @include('livewire.contract.create._contract-card')
            </aside>
        </div>
    </div>
</div>
