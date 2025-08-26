<div class="grid gap-6 md:grid-cols-2">
    <x-card>
        <x-slot name="title">{{ __('general.employee') }}</x-slot>
        <x-contract.employee-summary :contract="$contract" :locale="$locale" />
    </x-card>

    <x-card>
        <x-slot name="title">{{ __('general.trial-details') }}</x-slot>
        <x-contract.period :contract="$contract" />

        {{-- trial-specific fields --}}
        <div class="mt-4 grid gap-3 text-sm">
            @isset($contract->probation_weeks)
                <div><span class="opacity-70">{{ __('general.probation-length') }}:</span>
                    <span class="font-medium">{{ $contract->probation_weeks }} {{ __('general.weeks') }}</span>
                </div>
            @endisset
            @isset($contract->evaluation_date)
                <div><span class="opacity-70">{{ __('general.evaluation-date') }}:</span>
                    <span class="font-medium">{{ optional($contract->evaluation_date)->format('Y-m-d') }}</span>
                </div>
            @endisset
        </div>
    </x-card>
</div>
