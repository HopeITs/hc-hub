@php
    /*
     * The composer itself — identical for creating a contract and for editing
     * one. Everything that differs between the two screens arrives as a
     * variable, so neither page owns a private copy of this layout:
     *
     *   $mode               'create' | 'edit'
     *   $seed               the contract's current values, or null for a blank create
     *   $cities / $lockedCity / $types   option data
     *   $terminationReasons only meaningful in edit mode
     *   $cancelUrl          where the Cancel link goes back to
     *   $currentPeriod      the contract's stored term, shown in the card header
     */
    $mode ??= 'create';
    $seed ??= null;
    $terminationReasons ??= collect();
    $cancelUrl ??= route('contracts.index');
    $currentPeriod ??= null;

    $isEdit = $mode === 'edit';

    // Editing adds the one section a contract that does not exist cannot have.
    $sections = ['assignment', 'contract', 'schedule', 'pay'];

    if ($isEdit) {
        $sections[] = 'termination';
    }

    // Create demands work days; edit does not, because contracts imported from
    // Excel before the field existed have none and must still be editable.
    $requireWorkDays = ! $isEdit;

    $composerConfig = [
        'locale' => app()->getLocale(),
        // Draft-upgrade detection (see checkForDraftContract() in
        // contract-composer.js) calls a method that exists only on the
        // create screen's component — an edit is always about a contract
        // that already exists, so there is no "latest contract" to check.
        'allowDraftUpgrade' => ! $isEdit,
        'cityId' => $lockedCity?->id ?? ($seed['cityId'] ?? null),
        'cities' => $cities->map(fn ($city) => ['value' => $city->id, 'label' => $city->name])->values(),
        'sections' => $sections,
        'requireWorkDays' => $requireWorkDays,
        // An edit opens with everything collapsed and summarised: there is
        // nothing to fill in, only something to change.
        'initialSection' => $isEdit ? '' : 'assignment',
        'typeDefaults' => $types->mapWithKeys(fn ($type) => [
            (string) $type->id => [
                'name' => $type->name,
                'start' => $type->startDate,
                'end' => $type->endDate,
                'category' => $type->category,
            ],
        ]),
        'terminationReasons' => $terminationReasons
            ->map(fn ($reason) => ['value' => $reason->id, 'label' => $reason->title])
            ->values(),
        // Present whenever this page opened on an existing contract — a renewal
        // or an edit — so Alpine can fill every field from it instead of blank.
        'seed' => $seed,
        'labels' => [
            'allCenters' => __('general.composer.all-centers'),
            'cityLevel' => __('general.composer.city-level'),
            'lockedBecause' => __('general.composer.locked-because'),
            'notTerminated' => __('general.composer.not-terminated'),
            'sections' => [
                'assignment' => __('general.composer.assignment'),
                'contract' => __('general.composer.contract'),
                'schedule' => __('general.composer.schedule'),
                'pay' => __('general.composer.pay'),
                'termination' => __('general.composer.termination'),
            ],
            // Passed with their :placeholders intact; the composer substitutes
            // them client-side so no formatting logic is duplicated in PHP.
            'progress' => __('general.composer.progress'),
            'seatsLeft' => __('general.composer.seats-left'),
            'noSeatsLeft' => __('general.composer.no-seats-left'),
            'duplicate' => __('general.composer.duplicate-contract'),
            'draftFound' => __('general.composer.draft-found'),
            // :count substituted client-side, same as the duration labels below.
            'daysPerWeek' => __('general.composer.days-per-week'),
            'hoursPerWeek' => __('general.composer.hours-per-week'),
            'weeklyHoursValue' => __('general.composer.weekly-hours-value'),
            'flexible' => __('general.composer.schedule-flexible'),
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
            @include('livewire.contract.composer._assignment')
            @include('livewire.contract.composer._contract')
            @include('livewire.contract.composer._schedule', ['requireWorkDays' => $requireWorkDays])

            @include('livewire.contract.composer._pay')

            @if($isEdit)
                @include('livewire.contract.composer._termination')
            @endif

            @include('livewire.contract.composer._action-bar', [
                'cancelUrl' => $cancelUrl,
                'submitLabel' => $isEdit ? __('general.save') : __('general.composer.create'),
            ])
        </div>

        <aside class="hidden lg:block">
            @include('livewire.contract.composer._contract-card')
        </aside>
    </div>
</div>
