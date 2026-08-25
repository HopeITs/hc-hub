{{-- Section 2 — the type, and the period it implies. --}}
<x-contract.section
    name="contract"
    index="2"
    :title="__('general.composer.contract')"
    :hint="__('general.composer.contract-hint')"
    summary="contractSummary"
>
    <div class="mt-4">
        <x-forms.input-label required>{{ trans_choice('general.contract-type', 1) }}</x-forms.input-label>

        {{--
            Each card states the dates it will write. Those dates come from
            ContractForm::updatedContractTypeId() itself, so a card can never
            promise something the form would not actually do.
        --}}
        <div class="mt-1 grid gap-2 sm:grid-cols-2 lg:grid-cols-3" role="radiogroup"
             aria-label="{{ trans_choice('general.contract-type', 1) }}">
            @foreach($types as $type)
                <label class="group cursor-pointer">
                    <input type="radio" class="peer sr-only" name="composer_contract_type"
                           value="{{ $type->id }}"
                           x-model="selected.contractType"
                           x-on:change="chooseType('{{ $type->id }}')"/>
                    <span class="flex h-full flex-col gap-2 rounded-control border border-neutral-200 bg-white p-3
                                 transition
                                 hover:border-neutral-300
                                 peer-checked:border-primary peer-checked:bg-red-50/60
                                 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2
                                 peer-focus-visible:outline-primary">
                        <span class="text-body font-semibold text-neutral-900">{{ $type->name }}</span>

                        <span class="flex flex-wrap gap-1">
                            <span class="rounded-round bg-neutral-100 px-2 py-0.5 text-caption text-secondary-medium">
                                {{ \App\Enums\ContractType::getLocalizedName($type->type)['value'] }}
                            </span>
                            <span class="rounded-round bg-neutral-100 px-2 py-0.5 text-caption text-secondary-medium">
                                {{ \App\Enums\ContractPlace::getLocalizedName($type->place)['value'] }}
                            </span>
                        </span>

                        {{-- Nested, so peer-checked: cannot reach it — bind directly. --}}
                        <span class="mt-auto text-caption"
                              x-bind:class="selected.contractType === '{{ $type->id }}'
                                  ? 'text-primary font-medium' : 'text-secondary-light'">
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

    {{-- Start ─ duration ─ end, so the period reads as one thing. --}}
    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-start">
        <div class="sm:flex-1">
            <x-forms.input-label for="composer_start_date" required>@lang('general.start-date')</x-forms.input-label>
            <x-forms.date-input
                id="composer_start_date"
                x-model="startDate"
                x-bind:class="datesJustSet && 'ring-2 ring-primary/40 border-primary'"
            />
            <x-error-span>{{ $errors->first('form.start_date') }}</x-error-span>
        </div>

        <div class="flex shrink-0 items-center gap-2 self-center pt-0 sm:pt-6" aria-hidden="true">
            <span class="hidden h-px w-5 bg-neutral-200 sm:block"></span>
            <span x-cloak x-show="durationLabel"
                  class="whitespace-nowrap rounded-round bg-red-50 px-3 py-1 text-caption font-medium text-primary"
                  x-text="durationLabel"></span>
            <span class="hidden h-px w-5 bg-neutral-200 sm:block"></span>
        </div>

        <div class="sm:flex-1">
            <x-forms.input-label for="composer_end_date" required>@lang('general.end-date')</x-forms.input-label>
            <x-forms.date-input
                id="composer_end_date"
                x-model="endDate"
                x-bind:max="maxEndDate"
                x-bind:class="[
                    datesJustSet && 'ring-2 ring-primary/40 border-primary',
                    endDateTooFar && 'border-red-500',
                ]"
            />
            {{-- The one-year ceiling is a hint, not a surprise. --}}
            <p class="mt-1 text-caption"
               x-bind:class="endDateTooFar ? 'text-red-600' : 'text-secondary-light'"
               x-text="endDateTooFar
                   ? @js(__('general.composer.end-date-too-far'))
                   : @js(__('general.composer.max-end-date'))"></p>
            <x-error-span>{{ $errors->first('form.end_date') }}</x-error-span>
        </div>
    </div>

    <x-contract.continue section="contract" />
</x-contract.section>
