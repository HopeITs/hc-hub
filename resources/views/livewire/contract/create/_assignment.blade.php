{{-- Section 1 — who the contract is for, and where they sit. --}}
<x-contract.section
    name="assignment"
    index="1"
    :title="__('general.composer.assignment')"
    :hint="__('general.composer.assignment-hint')"
    summary="assignmentSummary"
>
    {{-- City is the root of both branches below, so it spans the full width. --}}
    <div class="mt-4">
        <x-forms.input-label for="composer_city" required>{{ trans_choice('general.city', 1) }}</x-forms.input-label>

        @if($lockedCity)
            {{-- A city-scoped role only ever has one option, and a dropdown of
                 one asks a question that has no answer. --}}
            <div class="flex min-h-[2.375rem] items-center gap-2 rounded-control border border-neutral-200
                        bg-neutral-50 px-3 py-2 text-body text-neutral-800">
                <svg class="size-3.5 shrink-0 text-secondary-light" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 2a3.5 3.5 0 00-3.5 3.5V8H6a1.5 1.5 0 00-1.5 1.5v6A1.5 1.5 0 006 17h8a1.5 1.5 0 001.5-1.5v-6A1.5 1.5 0 0014 8h-.5V5.5A3.5 3.5 0 0010 2zm2 6H8V5.5a2 2 0 114 0V8z"/>
                </svg>
                <span>{{ $lockedCity->name }}</span>
                <span class="ms-auto text-caption text-secondary-light">@lang('general.composer.locked-city')</span>
            </div>
        @else
            <x-forms.combobox id="composer_city" required options="cities" model="selected.city" />
        @endif

        <x-error-span>{{ $errors->first('city_id') }}</x-error-span>
    </div>

    <div class="mt-5 grid gap-x-6 gap-y-5 md:grid-cols-2">
        {{-- ── WHO ─────────────────────────────────────────────── --}}
        <div>
            <p class="mb-3 border-b border-neutral-100 pb-2 text-caption font-semibold uppercase tracking-wider text-secondary-light">
                @lang('general.composer.who')
            </p>

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

            {{-- Warn, never block: renewals and replacements legitimately overlap. --}}
            <template x-if="selectedEmployee && selectedEmployee.activeContractEndsAt">
                <p class="mt-2 rounded-control bg-red-50 px-3 py-2 text-small leading-relaxed text-primary"
                   x-text="duplicateContractNotice"></p>
            </template>

            <x-error-span>{{ $errors->first('form.employee_id') }}</x-error-span>
        </div>

        {{-- ── WHERE ───────────────────────────────────────────── --}}
        <div>
            <p class="mb-3 border-b border-neutral-100 pb-2 text-caption font-semibold uppercase tracking-wider text-secondary-light">
                @lang('general.composer.where')
            </p>

            <div class="space-y-3">
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

                <div>
                    <x-forms.input-label for="composer_center">{{ trans_choice('general.centers', 1) }}</x-forms.input-label>
                    <x-forms.combobox
                        id="composer_center"
                        options="centerOptions"
                        model="selected.center"
                        disabled="loading || !selected.program"
                    />
                    <p class="mt-1 text-caption text-secondary-light">@lang('general.composer.center-hint')</p>
                </div>

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

                    {{-- Capacity, stated before the choice rather than after it. --}}
                    <template x-if="capacity">
                        <div class="mt-2 flex items-center gap-2">
                            <span class="flex gap-0.5" aria-hidden="true">
                                <template x-for="seat in capacityBar" :key="seat.i">
                                    <span class="h-1 w-3.5 rounded-full"
                                          x-bind:class="seat.taken ? 'bg-primary' : 'bg-neutral-200'"></span>
                                </template>
                            </span>
                            <span class="text-caption tabular-nums"
                                  x-bind:class="capacity.left === 0 ? 'text-primary' : 'text-secondary-medium'"
                                  x-text="capacityLabel"></span>
                        </div>
                    </template>

                    <x-error-span>{{ $errors->first('form.position_id') }}</x-error-span>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak x-show="loading" class="mt-4" role="status" aria-live="polite">
        <span class="inline-flex items-center gap-2 rounded-control bg-neutral-100 px-3 py-1.5
                     text-small font-medium text-secondary-medium">
            <x-icons.spinner class="size-3.5 animate-spin text-primary" />
            @lang('general.loading')
        </span>
    </div>

    <x-contract.continue section="assignment" />
</x-contract.section>
