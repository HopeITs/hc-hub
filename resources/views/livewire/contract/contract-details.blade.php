@php
    /** @var \App\Models\Contract $contract */
    $locale     = $locale ?? app()->getLocale();
    $viewKey    = $displayView ?? 'annual'; // 'annual' | 'trial' | 'service'
    $partial    = $partialPath ?? "livewire.contract.partials.{$viewKey}";
@endphp

<div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.contracts', 1)"
            :crumbs="$crumbs ?? [
                ['label' => trans_choice('general.employee_management', 2)],
                ['label' => trans_choice('general.contracts', 2), 'url' => (route('contracts.index', [], false) ?? null)],
                ['label' => '#'.$contract->id],
            ]"
        />

        <div class="shrink-0 flex items-center gap-2">
            <x-primary-button :href="route('contracts.edit', $contract->id)">
                {{ __('general.edit') }}
            </x-primary-button>
            <x-secondary-button :href="route('contracts.index')">
                {{ __('general.back') }}
            </x-secondary-button>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <x-badge>
            {{ $contract->contractType?->getTranslation('name', $locale) ?? __('general.na') }}
        </x-badge>
        <x-badge>
            {{ Str::ucfirst($viewKey) }} {{ __('general.view') }}
        </x-badge>
    </div>

    @includeIf($partial, [
        'contract' => $contract,
        'locale'   => $locale,
    ])

</div>
