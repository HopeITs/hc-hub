{{--
    The contract form's pay panel: a fixed figure per month/hour/session, or
    flexible tiers. Hidden from roles that may not see pay: their save keeps
    the clause on file (or the renewal source's). Included by ⚡form.blade.php.
--}}
@use('Modules\Hr\Enums\Currency')
@use('Modules\Hr\Enums\PayMode')
@use('Modules\Hr\Enums\SalaryPer')
@use('Modules\Hr\Enums\SessionDuration')
@use('Modules\Hr\Support\Employees\EnumLabel')

<x-ui.panel :title="__('hr_contracts.form.pay')">
    @if ($payHidden)
        <p class="p-4 text-sm text-secondary-medium dark:text-ink-muted" data-test="pay-hidden">{{ __('hr_contracts.form.pay_hidden') }}</p>
        @error('form.salary')
            <p class="px-4 pb-4 text-sm text-status-critical">{{ $message }}</p>
        @enderror
    @else
        @php
            $fixedPay = (int) ($form['pay_mode'] ?? 0) === PayMode::Fixed->value;
        @endphp
        <div class="p-4 {{ $grid }}">
            <x-ui.form-select wire:model.live="form.pay_mode" :label="__('hr_contracts.fields.pay_mode')" required>
                @foreach ([PayMode::Fixed, PayMode::Flexible] as $mode)
                    <option value="{{ $mode->value }}">{{ EnumLabel::of(PayMode::class, $mode->value) }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model="form.currency" :label="__('hr_contracts.fields.currency')" required>
                @foreach ([Currency::SYP, Currency::USD, Currency::EUR] as $currency)
                    <option value="{{ $currency->value }}">{{ EnumLabel::of(Currency::class, $currency->value) }}</option>
                @endforeach
            </x-ui.form-select>
            <span class="hidden @5xl/form:block"></span>

            @if ($fixedPay)
                <x-ui.form-input wire:model="form.salary" :label="__('hr_contracts.fields.salary')" type="number" min="0" step="any" required dir="ltr" />
                <x-ui.form-select wire:model.live="form.salary_per" :label="__('hr_contracts.fields.salary_per')" required>
                    @foreach ([SalaryPer::Month, SalaryPer::Hour, SalaryPer::Session] as $per)
                        <option value="{{ $per->value }}">{{ EnumLabel::of(SalaryPer::class, $per->value) }}</option>
                    @endforeach
                </x-ui.form-select>
                @if ((int) ($form['salary_per'] ?? 0) === SalaryPer::Session->value)
                    <x-ui.form-select wire:model="form.session_duration" :label="__('hr_contracts.fields.session_duration')" :placeholder="__('common.select')" required>
                        @foreach ([SessionDuration::Hour, SessionDuration::HourAndHalf, SessionDuration::TwoHours] as $duration)
                            <option value="{{ $duration->value }}">{{ EnumLabel::of(SessionDuration::class, $duration->value) }}</option>
                        @endforeach
                    </x-ui.form-select>
                @endif
            @endif
        </div>

        @unless ($fixedPay)
            <div class="flex flex-col gap-3 border-t border-panel-border p-4" data-pay-tiers>
                @error('tiers')
                    <p class="text-sm text-status-critical">{{ $message }}</p>
                @enderror
                @foreach ($tiers as $tierIndex => $tier)
                    <div class="grid items-end gap-3 @2xl/form:grid-cols-[1fr_1fr_10rem_auto]" wire:key="tier-{{ $tierIndex }}">
                        <x-ui.form-input wire:model="tiers.{{ $tierIndex }}.condition_en" :label="__('hr_contracts.fields.condition_en')" maxlength="255" dir="ltr" required />
                        <x-ui.form-input wire:model="tiers.{{ $tierIndex }}.condition_ar" :label="__('hr_contracts.fields.condition_ar')" maxlength="255" dir="rtl" required />
                        <x-ui.form-input wire:model="tiers.{{ $tierIndex }}.amount" :label="__('hr_contracts.fields.amount')" type="number" min="0" step="any" dir="ltr" required />
                        <x-ui.button tone="ghost" icon="trash" wire:click="removeTier({{ $tierIndex }})" :aria-label="__('common.delete')" />
                    </div>
                @endforeach
                <div>
                    <x-ui.button icon="plus" wire:click="addTier" data-test="add-tier">{{ __('hr_contracts.actions.add_tier') }}</x-ui.button>
                </div>
            </div>
        @endunless
    @endif
</x-ui.panel>
