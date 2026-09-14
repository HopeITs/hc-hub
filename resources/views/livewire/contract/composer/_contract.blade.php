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
{{-- Section 2 — the type, and the period it implies. --}}
<x-contract.section
    name="contract"
    index="2"
    :title="__('general.composer.contract')"
    summary="contractSummary"
>
    <div class="mt-4">
        <x-forms.input-label required>{{ trans_choice('general.contract-type', 1) }}</x-forms.input-label>

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
            <div class="mt-1 grid grid-cols-3 gap-2" role="radiogroup" aria-label="@lang('general.type')">
                @foreach ($typeOptions as $value => $label)
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

    <x-contract.continue section="contract" />
</x-contract.section>
