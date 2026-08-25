@php
    use Carbon\Carbon;

    $locale = app()->getLocale();

    /*
     * Day names come from Carbon rather than a hand-written list, so the two
     * strings written into work_days_en / work_days_ar always match the names
     * the rest of the application prints. Sunday-first, matching the work week.
     */
    $sunday = Carbon::now()->startOfWeek(Carbon::SUNDAY);
    $dayNames = [];
    $dayShort = [];

    foreach (['en', 'ar'] as $dayLocale) {
        foreach (range(0, 6) as $offset) {
            $day = $sunday->copy()->addDays($offset)->locale($dayLocale);
            $dayNames[$dayLocale][$offset] = $day->dayName;
            $dayShort[$dayLocale][$offset] = $day->shortDayName;
        }
    }

    $composerConfig = [
        'locale' => $locale,
        'cityId' => $lockedCity?->id,
        'cities' => $cities->map(fn ($city) => ['value' => $city->id, 'label' => $city->name])->values(),
        'dayNames' => $dayNames,
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
        'draftKey' => 'hc-contract-draft:'.auth()->id(),
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
    <livewire:layout.breadcrumbs :title="__('general.add-new')" :crumbs="[
        ['label' => trans_choice('general.employee-management', 2)],
        ['label' => trans_choice('general.contracts', 2), 'url' => route('contracts.index')],
        ['label' => __('general.add-new')],
    ]"/>

    <div
        x-data="contractComposer(@js($composerConfig))"
        x-init="initialize()"
        class="pb-2 max-lg:pb-28"
    >
        {{-- Survives an accidental refresh; dies with the tab. --}}
        <div
            x-cloak
            x-show="draftAvailable"
            x-collapse
            class="mb-3 flex flex-wrap items-center gap-3 rounded-surface border border-neutral-200
                   bg-white px-4 py-2.5 shadow-e1"
        >
            <p class="flex-1 text-small text-neutral-800">@lang('general.composer.restore-title')</p>
            <button type="button" class="text-small font-semibold text-primary transition hover:underline"
                    x-on:click="restoreDraft()">@lang('general.composer.restore')</button>
            <button type="button" class="text-small text-secondary-medium transition hover:text-neutral-900"
                    x-on:click="clearDraft()">@lang('general.composer.discard')</button>
        </div>

        <div class="mb-3">
            <h1 class="text-h4 font-semibold text-neutral-900">@lang('general.composer.title')</h1>
            <p class="mt-0.5 text-small text-secondary-light">
                <span x-cloak x-show="cityName"><span x-text="cityName"></span> · </span>@lang('general.composer.subtitle')
            </p>
        </div>

        <div class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_17rem] xl:gap-5">
            <div class="min-w-0 space-y-2.5">
                @include('livewire.contract.create._assignment')
                @include('livewire.contract.create._contract')
                @include('livewire.contract.create._schedule')
                @include('livewire.contract.create._pay')
                @include('livewire.contract.create._action-bar')
            </div>

            <aside class="hidden lg:sticky lg:top-6 lg:block">
                @include('livewire.contract.create._contract-card')
            </aside>
        </div>
    </div>
</div>
