<div class="grid gap-6 md:grid-cols-2">
    <x-card>
        <x-slot name="title">{{ __('general.employee') }}</x-slot>
        <x-contract.employee-summary :contract="$contract" :locale="$locale" />
    </x-card>

    <x-card>
        <x-slot name="title">{{ __('general.agreement-details') }}</x-slot>
        <x-contract.period :contract="$contract" />
        <x-contract.salary :contract="$contract" />

        {{-- agreement-specific fields --}}
        <div class="mt-4 grid gap-3 text-sm">
            <div>
                <span class="opacity-70">{{ __('general.place') }}:</span>
                <span class="font-medium">
                    {{ $contract->contractType?->place === 1 ? __('general.onsite') : __('general.online') }}
                </span>
            </div>
            @isset($contract->scope)
                <div><span class="opacity-70">{{ __('general.scope') }}:</span>
                    <span class="font-medium">{{ $contract->scope }}</span></div>
            @endisset
        </div>
    </x-card>
</div>
