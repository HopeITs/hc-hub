@php
    /*
     * Inlined rather than pulled from x-icons.*, because those components bake a
     * colour utility into their own class list (icons.calendar carries
     * text-secondary-light). Merged classes cannot beat it, so the icon stayed
     * grey on the selected red card. These inherit currentColor and nothing else.
     */
    $perOptions = [
        1 => [
            'label' => __('general.month'),
            'path' => '<rect x="3" y="4.5" width="14" height="12" rx="2"/><path d="M3 8.5h14M7 2.5v3M13 2.5v3"/>',
        ],
        2 => [
            'label' => trans_choice('general.hour', 1),
            'path' => '<circle cx="10" cy="10" r="7.25"/><path d="M10 6v4.2l2.6 2.1"/>',
        ],
        3 => [
            'label' => __('general.session'),
            'path' => '<path d="M10 3.25L17.5 7 10 10.75 2.5 7 10 3.25z"/><path d="M5.5 8.75v3.9c0 1.4 2 2.6 4.5 2.6s4.5-1.2 4.5-2.6v-3.9"/>',
        ],
    ];
@endphp

{{-- Section 4 — the one field where a typo costs the most. --}}
<x-contract.section
    name="pay"
    index="4"
    :title="__('general.composer.pay')"
    summary="paySummary"
>
    <div class="mt-4 grid gap-5 lg:grid-cols-[1.1fr_1fr]">
        <div>
            <x-forms.input-label for="composer_salary" required>@lang('general.salary')</x-forms.input-label>

            {{-- Amount and currency read as one control, because they are one value. --}}
            <div class="flex">
                <input
                    id="composer_salary"
                    type="number"
                    step="0.01"
                    min="0"
                    inputmode="decimal"
                    placeholder="0.00"
                    x-model="salary"
                    class="w-full min-w-0 rounded-control rounded-e-none border border-e-0 border-neutral-300
                           bg-white px-3 py-2 text-end font-mono text-body tabular-nums text-neutral-900
                           shadow-e1 transition placeholder:text-neutral-400
                           focus:z-10 focus:border-primary focus:outline-none focus:ring-2 focus:ring-neutral-300"
                />
                <select
                    aria-label="@lang('general.currency')"
                    x-model="currency"
                    class="shrink-0 cursor-pointer rounded-control rounded-s-none border border-neutral-300
                           bg-neutral-100 px-2 py-2 text-body font-medium text-neutral-800 shadow-e1 transition
                           hover:bg-neutral-200
                           focus:z-10 focus:border-primary focus:outline-none focus:ring-2 focus:ring-neutral-300"
                >
                    <option value="0">@lang('general.unknown')</option>
                    <option value="1">SYP</option>
                    <option value="2">USD</option>
                    <option value="3">EUR</option>
                </select>
            </div>

            {{-- Echoed back formatted, and annualised when paid monthly. --}}
            <p x-cloak x-show="salaryEcho"
               class="mt-2 rounded-control border border-neutral-200 bg-neutral-50 px-3 py-1.5
                      text-small font-medium text-neutral-800"
               x-text="salaryEcho"></p>

            <x-error-span>{{ $errors->first('form.salary') ?: $errors->first('form.currency') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label required>@lang('general.salary-per')</x-forms.input-label>

            <div class="mt-1 grid grid-cols-3 gap-2" role="radiogroup" aria-label="@lang('general.salary-per')">
                @foreach($perOptions as $value => $option)
                    <label class="cursor-pointer">
                        <input type="radio" class="peer sr-only" name="composer_salary_per"
                               value="{{ $value }}" x-model="salaryPer"/>
                        <span class="flex min-h-[2.625rem] items-center justify-center gap-1.5 rounded-control
                                     border-2 border-neutral-200 bg-white px-2 py-2 text-body text-neutral-800
                                     shadow-e1 transition
                                     hover:border-neutral-400
                                     peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white
                                     peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2
                                     peer-focus-visible:outline-primary">
                            <svg class="size-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                {!! $option['path'] !!}
                            </svg>
                            <span class="truncate font-semibold">{{ $option['label'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <x-error-span>{{ $errors->first('form.salary_per') }}</x-error-span>
        </div>
    </div>
</x-contract.section>
