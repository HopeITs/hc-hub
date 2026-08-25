{{--
    The contract as it currently stands. Starts as a ghost outline with every
    label present, so the shape of what is being built is visible from the very
    first field rather than assembled in the user's head.
--}}
<div class="rounded-surface border border-neutral-200 bg-white shadow-e1">
    <div class="flex items-center gap-3 border-b border-neutral-100 p-4">
        <template x-if="selectedEmployee">
            <img
                class="size-10 shrink-0 rounded-round object-cover ring-1 ring-neutral-200"
                x-bind:src="selectedEmployee.avatar"
                x-bind:alt="selectedEmployee.label"
                loading="lazy"
            />
        </template>
        <template x-if="!selectedEmployee">
            <span class="size-10 shrink-0 rounded-round border border-dashed border-neutral-200 bg-neutral-50"
                  aria-hidden="true"></span>
        </template>

        <div class="min-w-0 flex-1">
            <p class="truncate text-body font-semibold"
               x-bind:class="selectedEmployee ? 'text-neutral-900' : 'text-neutral-300'"
               x-text="selectedEmployee ? selectedEmployee.label : '—'"></p>
            <p class="truncate text-caption text-secondary-light" x-text="cardSubtitle"></p>
        </div>
    </div>

    <dl class="divide-y divide-neutral-50 p-4 pt-2">
        @foreach([
            ['key' => trans_choice('general.positions', 1),      'value' => 'cardPosition'],
            ['key' => trans_choice('general.centers', 1),        'value' => 'cardCenter'],
            ['key' => __('general.composer.capacity'),           'value' => 'cardCapacity', 'accent' => true],
            ['key' => trans_choice('general.contract-type', 1),  'value' => 'cardType'],
            ['key' => __('general.period'),                      'value' => 'cardPeriod'],
            ['key' => __('general.weekly-hours'),                'value' => 'cardHours'],
            ['key' => __('general.salary'),                      'value' => 'cardSalary'],
        ] as $row)
            <div class="flex items-baseline justify-between gap-3 py-1.5">
                <dt class="shrink-0 text-caption text-secondary-light">{{ $row['key'] }}</dt>
                <dd
                    class="min-w-0 truncate text-end text-small tabular-nums"
                    @if($row['accent'] ?? false)
                        x-bind:class="capacity && capacity.left === 0
                            ? 'text-primary font-semibold'
                            : ({{ $row['value'] }} === '—' ? 'text-neutral-300' : 'text-neutral-900')"
                    @else
                        x-bind:class="{{ $row['value'] }} === '—' ? 'text-neutral-300' : 'text-neutral-900'"
                    @endif
                    x-text="{{ $row['value'] }}"
                ></dd>
            </div>
        @endforeach

        {{--
            Leave entitlement is created silently after save today. Showing it
            here is the only place the user ever learns what this contract grants.
        --}}
        <div class="mt-1 flex items-baseline justify-between gap-3 border-t border-neutral-100 pt-2.5">
            <dt class="shrink-0 text-caption text-secondary-light">@lang('general.composer.annual-leave')</dt>
            <dd class="min-w-0 truncate text-end text-small font-semibold tabular-nums"
                x-bind:class="leaveDays === null ? 'text-neutral-300' : 'text-neutral-900'"
                x-text="cardLeave"></dd>
        </div>
    </dl>
</div>
