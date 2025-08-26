<div class="grid gap-6 md:grid-cols-2">
    <x-card>
        <x-slot name="title">{{ __('general.employee') }}</x-slot>
        <x-contract.employee-summary :contract="$contract" :locale="$locale" />
    </x-card>

    <x-card>
        <x-slot name="title">{{ __('general.contract-details') }}</x-slot>
        <x-contract.period :contract="$contract" />
        <x-contract.salary :contract="$contract" />

        <div class="mt-4 text-sm flex items-center gap-2">
            <span class="opacity-70">{{ __('general.yearly-leave-days') }}:</span>
            <span class="font-medium">{{ $contract->yearly_leave_days ?? '—' }}</span>
        </div>
    </x-card>
</div>
