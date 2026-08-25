{{-- Section 1 — who the contract is for, and where they sit. --}}
<x-contract.section
    name="assignment"
    index="1"
    :title="__('general.composer.assignment')"
    :hint="__('general.composer.assignment-hint')"
    summary="assignmentSummary"
>
    {{--
        A balanced two-column grid. The dependency order still reads left to
        right, top to bottom: city, then who, then the org path down to the post.
    --}}
    <div class="mt-4 grid gap-x-6 gap-y-4 md:grid-cols-2">

        {{-- City --}}
        <div>
            <x-forms.input-label for="composer_city" required>{{ trans_choice('general.city', 1) }}</x-forms.input-label>

            @if($lockedCity)
                {{-- A city-scoped role only ever has one option, and a dropdown
                     of one asks a question that has no answer. --}}
                <div class="flex min-h-[2.375rem] items-center gap-2 rounded-control border border-neutral-200
                            bg-neutral-100 px-3 py-2 text-body font-medium text-neutral-800">
                    <svg class="size-3.5 shrink-0 text-secondary-medium" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10 2a3.5 3.5 0 00-3.5 3.5V8H6a1.5 1.5 0 00-1.5 1.5v6A1.5 1.5 0 006 17h8a1.5 1.5 0 001.5-1.5v-6A1.5 1.5 0 0014 8h-.5V5.5A3.5 3.5 0 0010 2zm2 6H8V5.5a2 2 0 114 0V8z"/>
                    </svg>
                    <span>{{ $lockedCity->name }}</span>
                    <span class="ms-auto text-caption text-secondary-medium">@lang('general.composer.locked-city')</span>
                </div>
            @else
                <x-forms.combobox id="composer_city" required options="cities" model="selected.city" />
            @endif

            <x-error-span>{{ $errors->first('city_id') }}</x-error-span>
        </div>

        {{-- Employee --}}
        <div>
            <x-forms.input-label for="composer_employee" required>{{ trans_choice('general.employee', 1) }}</x-forms.input-label>
            <x-forms.combobox
                id="composer_employee"
                required
                variant="employee"
                clearable
                options="options.employees"
                model="selected.employee"
                disabled="loading || !selected.city"
            />

            <x-error-span>{{ $errors->first('form.employee_id') }}</x-error-span>
        </div>

        {{--
            Warn, never block: renewals and replacements legitimately overlap.

            Spans the full width as its own grid row rather than sitting inside
            the Employee cell — growing that cell would stretch the whole row and
            leave a dead gap under City.
        --}}
        <template x-if="selectedEmployee && selectedEmployee.activeContractEndsAt">
            <p class="flex gap-2 rounded-control border border-neutral-200 border-s-4 border-s-primary
                      bg-neutral-50 px-3 py-2 text-small leading-relaxed text-neutral-800 md:col-span-2">
                <svg class="mt-0.5 size-4 shrink-0 text-primary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 1.5a8.5 8.5 0 100 17 8.5 8.5 0 000-17zM9 5.5h2v6H9v-6zm0 7.5h2v2H9v-2z"/>
                </svg>
                <span x-text="duplicateContractNotice"></span>
            </p>
        </template>

        {{-- Program --}}
        <div>
            {{-- Required in fact as well as in name: no program, no positions. --}}
            <x-forms.input-label for="composer_program" required>{{ trans_choice('general.program', 1) }}</x-forms.input-label>
            <x-forms.combobox
                id="composer_program"
                required
                options="options.programs"
                model="selected.program"
                disabled="loading || !selected.city"
            />
        </div>

        {{-- Center --}}
        <div>
            <x-forms.input-label for="composer_center">{{ trans_choice('general.centers', 1) }}</x-forms.input-label>
            <x-forms.combobox
                id="composer_center"
                options="centerOptions"
                model="selected.center"
                disabled="loading || !selected.program"
            />
            <p class="mt-1.5 text-caption text-secondary-medium">@lang('general.composer.center-hint')</p>
        </div>

        {{-- Position --}}
        <div>
            <x-forms.input-label for="composer_position" required>{{ trans_choice('general.positions', 1) }}</x-forms.input-label>
            <x-forms.combobox
                id="composer_position"
                required
                variant="position"
                clearable
                options="positionOptions"
                model="selected.position"
                disabled="loading || !selected.program"
            />
            <x-error-span>{{ $errors->first('form.position_id') }}</x-error-span>
        </div>

        {{-- Capacity of the chosen position. Deliberately built to the same
             label + control shape and height as the Positions field beside it,
             so the pair reads as one row rather than two odd blocks. --}}
        <div>
            <x-forms.input-label>@lang('general.composer.capacity')</x-forms.input-label>

            <div class="flex min-h-[2.375rem] w-full items-center gap-2.5 rounded-control
                        border border-neutral-200 bg-neutral-50 px-3 py-2">
                <template x-if="!capacity">
                    <span class="text-body text-secondary-light">@lang('general.composer.capacity-empty')</span>
                </template>

                {{-- A one-segment bar conveys nothing, so it only draws from two seats up. --}}
                <template x-if="capacity && capacity.max > 1">
                    <span class="flex gap-1" aria-hidden="true">
                        <template x-for="seat in capacityBar" :key="seat.i">
                            <span class="h-1.5 w-5 rounded-full"
                                  x-bind:class="seat.taken ? 'bg-primary' : 'bg-neutral-300'"></span>
                        </template>
                    </span>
                </template>

                <template x-if="capacity">
                    <span class="text-body font-medium tabular-nums"
                          x-bind:class="capacity.left === 0 ? 'text-primary' : 'text-neutral-800'"
                          x-text="capacityLabel"></span>
                </template>
            </div>
        </div>
    </div>

    <div x-cloak x-show="loading" class="mt-4" role="status" aria-live="polite">
        <span class="inline-flex items-center gap-2 rounded-control border border-neutral-200 bg-neutral-50
                     px-3 py-1.5 text-small font-medium text-secondary-dark">
            <x-icons.spinner class="size-3.5 animate-spin text-primary" />
            @lang('general.loading')
        </span>
    </div>

    <x-contract.continue section="assignment" />
</x-contract.section>
