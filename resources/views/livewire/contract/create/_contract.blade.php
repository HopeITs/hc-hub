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

                        <span class="flex flex-wrap gap-1">
                            <span class="rounded-round bg-neutral-100 px-2 py-0.5 text-caption font-medium text-secondary-dark">
                                {{ \App\Enums\ContractType::getLocalizedName($type->type)['value'] }}
                            </span>
                            <span class="rounded-round bg-neutral-100 px-2 py-0.5 text-caption font-medium text-secondary-dark">
                                {{ \App\Enums\ContractPlace::getLocalizedName($type->place)['value'] }}
                            </span>
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
