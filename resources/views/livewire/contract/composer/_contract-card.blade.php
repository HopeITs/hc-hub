{{--
    The contract as it currently stands. Starts as a ghost outline with every
    label present, so the shape of what is being built is visible from the very
    first field rather than assembled in the user's head.

    Fills its grid cell, so it is always exactly as tall as the composer column
    beside it — by construction, not by tuning either side to the other. The
    rows share whatever height that turns out to be; the entitlement closes the
    card out at the foot, where a total belongs.
--}}
<div class="flex h-full flex-col rounded-surface border border-neutral-200 bg-white shadow-e1">
    <div class="flex items-center gap-3 border-b border-neutral-200 p-3">
        <template x-if="selectedEmployee">
            <img
                class="size-10 shrink-0 rounded-round object-cover ring-1 ring-neutral-200"
                x-bind:src="selectedEmployee.avatar"
                x-bind:alt="selectedEmployee.label"
                loading="lazy"
            />
        </template>
        <template x-if="!selectedEmployee">
            <span class="grid size-10 shrink-0 place-items-center rounded-round border-2 border-dashed
                         border-neutral-300 bg-neutral-50" aria-hidden="true">
                <svg class="size-5 text-neutral-300" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zM3 18a7 7 0 1114 0H3z"/>
                </svg>
            </span>
        </template>

        <div class="min-w-0 flex-1">
            <p class="truncate text-body font-semibold"
               x-bind:class="selectedEmployee ? 'text-neutral-900' : 'text-neutral-300'"
               x-text="selectedEmployee ? selectedEmployee.label : '—'"></p>
            <p class="truncate text-caption text-secondary-medium" x-text="cardSubtitle"></p>

            @if($currentPeriod ?? null)
                {{-- The period as the contract stands today. The Start/End rows
                     below move as the form is edited; this line does not, so
                     what is being changed stays comparable to what it was. --}}
                <p class="truncate text-caption tabular-nums text-secondary-light"
                   title="{{ __('general.current-period') }}: {{ $currentPeriod }}">
                    <span class="sr-only">@lang('general.current-period'):</span>
                    {{ $currentPeriod }}
                </p>
            @endif
        </div>
    </div>

        {{-- Rows grow to share the height, but only to a comfortable maximum;
         anything past that becomes even spacing rather than giant rows. --}}
    <dl class="flex flex-1 flex-col justify-between px-4">
        @foreach([
            ['key' => __('general.composer.card-position'), 'value' => 'cardPosition'],
            ['key' => __('general.composer.card-center'),   'value' => 'cardCenter'],
            ['key' => __('general.composer.capacity'),      'value' => 'cardCapacity', 'accent' => true],
            ['key' => trans_choice('general.contract-type', 1), 'value' => 'cardType'],
            ['key' => __('general.start-date'),             'value' => 'cardStart'],
            ['key' => __('general.end-date'),               'value' => 'cardEnd'],
            ['key' => __('general.period'),                 'value' => 'cardPeriod'],
            ['key' => __('general.work-days'),              'value' => 'cardWorkDays'],
            ['key' => __('general.weekly-hours'),           'value' => 'cardHours'],
            ['key' => __('general.daily-hours'),            'value' => 'cardDailyHours'],
            ['key' => __('general.shift-type'),             'value' => 'cardShiftType'],
            ['key' => __('general.salary'),                 'value' => 'cardSalary'],
        ] as $row)
            <div class="flex max-h-14 min-h-[1.75rem] flex-1 items-center justify-between gap-3 border-b border-neutral-100 py-1 last:border-b-0">
                <dt class="shrink-0 text-caption text-secondary-medium">{{ $row['key'] }}</dt>
                <dd
                    class="min-w-0 truncate text-end text-small font-medium tabular-nums"
                    @if($row['accent'] ?? false)
                        x-bind:class="capacity && capacity.left === 0
                            ? 'font-semibold text-primary'
                            : ({{ $row['value'] }} === '—' ? 'text-neutral-300' : 'text-neutral-900')"
                    @else
                        x-bind:class="{{ $row['value'] }} === '—' ? 'text-neutral-300' : 'text-neutral-900'"
                    @endif
                    x-text="{{ $row['value'] }}"
                ></dd>
            </div>
        @endforeach
    </dl>

    {{--
        Leave entitlement is created silently after save today. This is the only
        place the user ever learns what the contract actually grants, so it sits
        at the foot of the card like a total.
    --}}
    <div class="flex items-baseline justify-between gap-3 rounded-b-surface border-t border-neutral-200
                bg-neutral-50 px-4 py-2.5">
        <dt class="shrink-0 text-caption font-semibold uppercase tracking-wider text-secondary-medium">
            @lang('general.composer.annual-leave')
        </dt>
        <dd class="min-w-0 truncate text-end text-body font-semibold tabular-nums"
            x-bind:class="leaveDays === null ? 'text-neutral-300' : 'text-neutral-900'"
            x-text="cardLeave"></dd>
    </div>
</div>
