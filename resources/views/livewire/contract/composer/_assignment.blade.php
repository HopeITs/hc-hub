@php
    use App\Enums\ContractPlace;
    use App\Enums\ContractType as ContractTypeEnum;

    // Unknown/0 is a data-migration fallback, never a real choice — both
    // groups below list only the values a person may actually pick.
    $typeOptions = [
        ContractTypeEnum::FullTime => ContractTypeEnum::getLocalizedName(ContractTypeEnum::FullTime)['value'],
        ContractTypeEnum::PartTime => ContractTypeEnum::getLocalizedName(ContractTypeEnum::PartTime)['value'],
        ContractTypeEnum::Freelance => ContractTypeEnum::getLocalizedName(ContractTypeEnum::Freelance)['value'],
    ];

    $placeOptions = [
        ContractPlace::Onsite => ContractPlace::getLocalizedName(ContractPlace::Onsite)['value'],
        ContractPlace::Online => ContractPlace::getLocalizedName(ContractPlace::Online)['value'],
    ];
@endphp

{{--
    Section 1 — who the contract is for and the terms it runs on, in one
    step: picking the employee is what everything else cascades from (their
    own city fills in on its own, and a draft — see checkForDraftContract()
    in contract-composer.js — fills in the position/program/city too), so it
    opens the step rather than waiting behind a city choice nobody has a
    reason to make first.
--}}
<x-contract.section
    name="assignment"
    index="1"
    :title="__('general.composer.assignment')"
    summary="assignmentSummary"
>
    <div class="mt-4 flex flex-col gap-4">

        <div class="grid gap-x-6 gap-y-4 md:grid-cols-2">
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
                    disabled="loading"
                />

                <x-error-span>{{ $errors->first('form.employee_id') }}</x-error-span>
            </div>

            {{-- City — auto-selected from the employee's own city the moment
                 one is chosen (or from their draft's position, if that
                 differs — see onEmployeeChange()/checkForDraftContract()).
                 Still a normal, editable field: the guess is usually right,
                 not unoverridable. --}}
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
                    {{-- Names the auto-fill out loud, so a manual override (see
                         onEmployeeChange() in contract-composer.js) reads as a
                         deliberate choice rather than a surprise reset. --}}
                    <p class="mt-1.5 text-caption text-secondary-medium">@lang('general.composer.city-auto-hint')</p>
                @endif

                <x-error-span>{{ $errors->first('city_id') }}</x-error-span>
            </div>
        </div>

        {{-- Warn, never block: renewals and replacements legitimately overlap. --}}
        <template x-if="selectedEmployee && selectedEmployee.activeContractEndsAt">
            <p class="flex gap-2 rounded-control border border-neutral-200 border-s-4 border-s-primary
                      bg-neutral-50 px-3 py-2 text-small leading-relaxed text-neutral-800">
                <svg class="mt-0.5 size-4 shrink-0 text-primary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 1.5a8.5 8.5 0 100 17 8.5 8.5 0 000-17zM9 5.5h2v6H9v-6zm0 7.5h2v2H9v-2z"/>
                </svg>
                <span x-text="duplicateContractNotice"></span>
            </p>
        </template>

        {{-- The employee's own draft contract is being completed in place —
             see checkForDraftContract() / ContractCreate::resolveDraftUpgrade(). --}}
        <template x-if="draftContractNotice">
            <p class="flex gap-2 rounded-control border border-neutral-200 border-s-4 border-s-primary
                      bg-neutral-50 px-3 py-2 text-small leading-relaxed text-neutral-800">
                <svg class="mt-0.5 size-4 shrink-0 text-primary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 1.5a8.5 8.5 0 100 17 8.5 8.5 0 000-17zM9 5.5h2v6H9v-6zm0 7.5h2v2H9v-2z"/>
                </svg>
                <span x-text="draftContractNotice"></span>
            </p>
        </template>

        {{-- Program / Center: their own row, kept separate from Position /
             Capacity below so hiding Center for a Study Zone program (a
             `display:none` grid item) cannot pull Position into Center's
             column — CSS Grid auto-flow reclaims a hidden item's slot, which
             used to silently pair Position with Program and strand Capacity
             alone on the row after it. --}}
        <div class="grid gap-x-6 gap-y-4 md:grid-cols-2">
            {{-- Program --}}
            <div x-bind:class="isStudyZoneProgram && 'md:col-span-2'">
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

            {{-- Center — Study Zone positions are city-level by data, not by a
                 choice anyone makes, so there is nothing to pick here for it. --}}
            <div x-show="!isStudyZoneProgram" x-cloak>
                <x-forms.input-label for="composer_center">{{ trans_choice('general.centers', 1) }}</x-forms.input-label>
                <x-forms.combobox
                    id="composer_center"
                    options="centerOptions"
                    model="selected.center"
                    disabled="loading || !selected.program"
                />
            </div>
        </div>

        <div class="grid gap-x-6 gap-y-4 md:grid-cols-2">
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
    </div>

    <div x-cloak x-show="loading" class="mt-4" role="status" aria-live="polite">
        <span class="inline-flex items-center gap-2 rounded-control border border-neutral-200 bg-neutral-50
                     px-3 py-1.5 text-small font-medium text-secondary-dark">
            <x-icons.spinner class="size-3.5 animate-spin text-primary" />
            @lang('general.loading')
        </span>
    </div>

    {{-- This merged step still reads as two decisions — who/where, then on
         what terms — so the second half gets its own small eyebrow rather
         than just a rule, the only seam separating them otherwise. --}}
    <div class="mt-6 border-t border-neutral-100 pt-5">
        <p class="text-caption font-semibold uppercase tracking-wide text-secondary-medium">
            @lang('general.composer.contract-terms')
        </p>

        <x-forms.input-label class="mt-3" required>{{ trans_choice('general.contract-type', 1) }}</x-forms.input-label>

        {{--
            Each card states the dates it will write. Those dates come from
            ContractForm::updatedContractTypeId() itself, so a card can never
            promise something the form would not actually do.
        --}}
        <div class="mt-1 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3" role="radiogroup"
             aria-label="{{ trans_choice('general.contract-type', 1) }}">
            @foreach($types as $type)
                <label class="group cursor-pointer">
                    <input type="radio" class="peer sr-only" name="composer_contract_type"
                           value="{{ $type->id }}"
                           x-model="selected.contractType"
                           x-on:change="chooseType('{{ $type->id }}')"/>
                    <span class="flex h-full flex-col gap-2 rounded-control border-2 border-neutral-200 bg-white p-3
                                 shadow-e1 transition
                                 group-hover:border-neutral-400
                                 peer-checked:border-primary peer-checked:shadow-e2
                                 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2
                                 peer-focus-visible:outline-primary">
                        <span class="flex items-start gap-2">
                            {{-- Selection is stated, not just implied by a border. --}}
                            <span class="mt-0.5 grid size-4 shrink-0 place-items-center rounded-round border-2 transition"
                                  x-bind:class="selected.contractType === '{{ $type->id }}'
                                      ? 'border-primary bg-primary' : 'border-neutral-300 bg-white'"
                                  aria-hidden="true">
                                <span class="size-1.5 rounded-round bg-white"
                                      x-show="selected.contractType === '{{ $type->id }}'"></span>
                            </span>
                            <span class="text-body font-semibold text-neutral-900">{{ $type->name }}</span>
                        </span>

                        {{-- Nested, so peer-checked: cannot reach it — bind directly. --}}
                        <span class="mt-auto text-caption font-medium"
                              x-bind:class="selected.contractType === '{{ $type->id }}'
                                  ? 'text-primary' : 'text-secondary-medium'">
                            @if($type->category === 'trial')
                                → {{ __('general.composer.lasts-months', ['count' => 3]) }}
                            @else
                                → {{ __('general.composer.runs-to', [
                                        'date' => \Carbon\Carbon::parse($type->endDate)->translatedFormat('j M'),
                                   ]) }}
                            @endif
                        </span>
                    </span>
                </label>
            @endforeach
        </div>

        <x-error-span>{{ $errors->first('form.contract_type_id') }}</x-error-span>
    </div>

    {{-- Type and place describe this one contract, not the category it
         belongs to — two contracts of the same type above can still differ
         on both. --}}
    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <div>
            <x-forms.input-label required>@lang('general.type')</x-forms.input-label>

            {{--
                Full/Part time only make sense for a volunteer or trial
                contract; a service contract is always Freelance — chooseType()
                auto-corrects `type` the moment a card of one category or the
                other is picked, this just keeps the now-irrelevant options off
                screen instead of offering a choice that would fail validation.
            --}}
            <div x-cloak x-show="selectedType?.category !== 'service'"
                 class="mt-1 grid grid-cols-2 gap-2" role="radiogroup" aria-label="@lang('general.type')">
                @foreach ($typeOptions as $value => $label)
                    @continue($value === ContractTypeEnum::Freelance)
                    <label class="cursor-pointer">
                        <input type="radio" class="peer sr-only" name="composer_type"
                               value="{{ $value }}" x-model="type"/>
                        <span class="flex min-h-[2.625rem] items-center justify-center rounded-control
                                     border-2 border-neutral-200 bg-white px-2 py-2 text-body text-neutral-800
                                     shadow-e1 transition
                                     hover:border-neutral-400
                                     peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white
                                     peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2
                                     peer-focus-visible:outline-primary">
                            <span class="truncate font-semibold">{{ $label }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div x-cloak x-show="selectedType?.category === 'service'"
                 class="mt-1 flex min-h-[2.625rem] items-center rounded-control border-2 border-neutral-200
                        bg-neutral-50 px-3 text-body font-semibold text-neutral-700">
                {{ $typeOptions[ContractTypeEnum::Freelance] }}
            </div>

            <x-error-span>{{ $errors->first('form.type') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label required>@lang('general.place')</x-forms.input-label>
            <div class="mt-1 grid grid-cols-2 gap-2" role="radiogroup" aria-label="@lang('general.place')">
                @foreach ($placeOptions as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" class="peer sr-only" name="composer_place"
                               value="{{ $value }}" x-model="place"/>
                        <span class="flex min-h-[2.625rem] items-center justify-center rounded-control
                                     border-2 border-neutral-200 bg-white px-2 py-2 text-body text-neutral-800
                                     shadow-e1 transition
                                     hover:border-neutral-400
                                     peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white
                                     peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2
                                     peer-focus-visible:outline-primary">
                            <span class="truncate font-semibold">{{ $label }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <x-error-span>{{ $errors->first('form.place') }}</x-error-span>
        </div>
    </div>

    {{-- Start ─ duration ─ end, so the period reads as one thing. --}}
    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-start">
        <div class="sm:flex-1">
            <x-forms.input-label for="composer_start_date" required>@lang('general.start-date')</x-forms.input-label>
            <x-forms.date-input
                id="composer_start_date"
                x-model="startDate"
                x-bind:class="datesJustSet && 'border-primary ring-2 ring-neutral-300'"
            />
            <x-error-span>{{ $errors->first('form.start_date') }}</x-error-span>
        </div>

        {{-- The whole connector hides until there is a duration, otherwise two
             stray dashes float between the empty date fields. --}}
        <div x-cloak x-show="durationLabel"
             class="flex shrink-0 items-center gap-2 self-center pt-0 sm:pt-6" aria-hidden="true">
            <span class="hidden h-px w-5 bg-neutral-300 sm:block"></span>
            <span class="whitespace-nowrap rounded-round border border-primary bg-white px-3 py-1
                         text-caption font-semibold text-primary"
                  x-text="durationLabel"></span>
            <span class="hidden h-px w-5 bg-neutral-300 sm:block"></span>
        </div>

        <div class="sm:flex-1">
            <x-forms.input-label for="composer_end_date" required>@lang('general.end-date')</x-forms.input-label>
            <x-forms.date-input
                id="composer_end_date"
                x-model="endDate"
                x-bind:max="maxEndDate"
                x-bind:class="[
                    datesJustSet && 'border-primary ring-2 ring-neutral-300',
                    endDateTooFar && 'border-primary',
                ]"
            />
            {{-- The one-year ceiling is a hint, not a surprise. --}}
            <p class="mt-1.5 text-caption"
               x-bind:class="endDateTooFar ? 'font-semibold text-primary' : 'text-secondary-medium'"
               x-text="endDateTooFar
                   ? @js(__('general.composer.end-date-too-far'))
                   : @js(__('general.composer.max-end-date'))"></p>
            <x-error-span>{{ $errors->first('form.end_date') }}</x-error-span>
        </div>
    </div>

    <x-contract.continue section="assignment" />
</x-contract.section>
