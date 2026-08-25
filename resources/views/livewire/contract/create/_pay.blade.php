{{-- Section 4 — the one field where a typo costs the most. --}}
<x-contract.section
    name="pay"
    index="4"
    :title="__('general.composer.pay')"
    :hint="__('general.composer.pay-hint')"
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
                           focus:z-10 focus:border-primary focus:outline-none focus:ring-2 focus:ring-secondary-light"
                />
                <select
                    aria-label="@lang('general.currency')"
                    x-model="currency"
                    class="shrink-0 rounded-control rounded-s-none border border-neutral-300 bg-neutral-50 px-2 py-2
                           text-body text-neutral-800 shadow-e1 transition
                           focus:z-10 focus:border-primary focus:outline-none focus:ring-2 focus:ring-secondary-light"
                >
                    <option value="0">@lang('general.unknown')</option>
                    <option value="1">SYP</option>
                    <option value="2">USD</option>
                    <option value="3">EUR</option>
                </select>
            </div>

            {{-- Echoed back formatted, and annualised when paid monthly. --}}
            <p x-cloak x-show="salaryEcho" class="mt-2 text-small text-secondary-medium" x-text="salaryEcho"></p>

            <x-error-span>{{ $errors->first('form.salary') ?: $errors->first('form.currency') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label required>@lang('general.salary-per')</x-forms.input-label>

            <div class="mt-1 grid grid-cols-3 gap-2" role="radiogroup" aria-label="@lang('general.salary-per')">
                @foreach([
                    1 => ['label' => __('general.month'), 'icon' => 'calendar'],
                    2 => ['label' => trans_choice('general.hour', 1), 'icon' => 'clock'],
                    3 => ['label' => __('general.session'), 'icon' => 'education'],
                ] as $value => $option)
                    <label class="cursor-pointer">
                        <input type="radio" class="peer sr-only" name="composer_salary_per"
                               value="{{ $value }}" x-model="salaryPer"/>
                        <span class="flex min-h-[2.375rem] items-center justify-center gap-1.5 rounded-control
                                     border border-neutral-200 bg-white px-2 py-2 text-body text-neutral-800
                                     transition hover:border-neutral-300
                                     peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white
                                     peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2
                                     peer-focus-visible:outline-primary">
                            <x-dynamic-component :component="'icons.'.$option['icon']" class="size-4 shrink-0" />
                            <span class="truncate font-medium">{{ $option['label'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <x-error-span>{{ $errors->first('form.salary_per') }}</x-error-span>
        </div>
    </div>
</x-contract.section>
