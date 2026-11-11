@props(['options', 'mode' => 'create'])

{{-- The offer terms form shared by the create / edit page and the revise page (ManagesJobOfferForm). --}}
@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<form wire:submit="save" class="space-y-6">
    @error('offer')
        <p role="alert" class="rounded-md border border-primary bg-primary/5 p-3 text-sm text-primary">{{ $message }}</p>
    @enderror

    <x-ui.panel :title="__('hr_recruitment.applicant_ui.offers.pay')" :subtitle="__('hr_recruitment.applicant_ui.offers.salary_confidential')" :clip="false">
        <div class="grid gap-5 p-4 sm:grid-cols-3">
            <x-ui.form-input wire:model="salary" :label="__('hr_recruitment.applicant_ui.offers.salary')" type="number" step="0.01" min="0" inputmode="decimal" required />
            <x-ui.form-select wire:model="currency" :label="__('hr_recruitment.applicant_ui.offers.currency')" required>
                @foreach ($options['currencies'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model="salaryPer" :label="__('hr_recruitment.applicant_ui.offers.salary_per')" required>
                @foreach ($options['salaryPers'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.form-select>
        </div>
    </x-ui.panel>

    <x-ui.panel :title="__('hr_recruitment.applicant_ui.offers.contract_details')" :clip="false">
        <div class="grid gap-5 p-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.form-select wire:model="contractTypeId" :label="__('hr_recruitment.applicant_ui.offers.contract_type')" :placeholder="__('hr_recruitment.choose')" required>
                @foreach ($options['contractTypes'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model="employmentType" :label="__('hr_recruitment.applicant_ui.offers.employment_type')" :placeholder="__('hr_recruitment.choose')" required>
                @foreach ($options['employmentTypes'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model="place" :label="__('hr_recruitment.applicant_ui.offers.place')" :placeholder="__('hr_recruitment.choose')" required>
                @foreach ($options['places'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-input wire:model="startDate" :label="__('hr_recruitment.applicant_ui.offers.start_date')" type="date" required />
            <x-ui.form-input wire:model="endDate" :label="__('hr_recruitment.applicant_ui.offers.end_date')" type="date" required />
            <x-ui.form-input wire:model="probationDays" :label="__('hr_recruitment.applicant_ui.offers.probation_days')" type="number" min="0" inputmode="numeric" required />
            <x-ui.form-input wire:model="weeklyHours" :label="__('hr_recruitment.applicant_ui.offers.weekly_hours')" type="number" min="1" inputmode="numeric" required />
            <div class="sm:col-span-2 lg:col-span-3">
                <x-ui.form-textarea wire:model="notes" :label="__('hr_recruitment.applicant_ui.offers.other_terms')" rows="3" required dir="auto" />
            </div>
        </div>
    </x-ui.panel>

    <x-ui.panel :clip="false">
        <div class="space-y-4 p-4">
            @if ($mode === 'revise')
                <x-ui.form-textarea wire:model="reason" :label="__('hr_recruitment.applicant_ui.offers.revision_reason')" :help="__('hr_recruitment.applicant_ui.offers.revision_reason_hint')" rows="2" required dir="auto" />
            @endif
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.draft_hint') }}</p>
                <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled" wire:target="save">{{ __('hr_recruitment.applicant_ui.offers.save.'.$mode) }}</x-ui.button>
            </div>
        </div>
    </x-ui.panel>
</form>
