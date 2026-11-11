{{--
    The ID card tab of an employee profile: the card's public link and number,
    and for HR the controls to reissue, switch off or switch on the card. The
    employee can tell HR their card is lost. Included by ⚡show.blade.php.
--}}
@use('Modules\Hr\Support\Employees\DigitalId')

@php
    $cardLink = DigitalId::url($employee);
    $cardMissing = DigitalId::missingLinkReason($employee);
    $cardIndex = DigitalId::currentIndex($employee);
    $cardEnabled = (bool) $employee->digital_id_enabled;
@endphp

<x-ui.panel :title="__('hr_employees.card.title')" class="max-w-3xl">
    <div class="flex flex-col gap-4 p-4">
        <dl class="grid gap-4 @2xl/directory:grid-cols-3">
            <x-hr::employees.fact :label="__('hr_employees.card.kind')" :value="$cardIndex > DigitalId::CARDBOARD_INDEX ? __('hr_employees.card.plastic', ['index' => $cardIndex]) : __('hr_employees.card.cardboard')" />
            <x-hr::employees.fact :label="__('common.status')" :value="! $employee->is_active ? __('hr_employees.card.state_inactive') : ($cardEnabled ? __('hr_employees.card.state_on') : __('hr_employees.card.state_off'))" />
            <x-hr::employees.fact :label="__('hr_employees.card.issued_at')" :value="$employee->digital_id_rotated_at?->translatedFormat(__('formats.date'))" />
        </dl>

        @if ($cardMissing !== null)
            <p class="rounded-lg bg-status-warn-soft px-3 py-2 text-sm text-status-warn" data-test="card-missing">{{ __($cardMissing) }}</p>
        @elseif ($cardLink !== null)
            <div class="flex flex-wrap items-center gap-2" x-data="{ copied: false }">
                <code class="min-w-0 flex-1 truncate rounded-lg bg-secondary-soft px-3 py-2 text-sm {{ $ink }} dark:bg-surface-2" dir="ltr">{{ $cardLink }}</code>
                <x-ui.button size="sm" icon="clipboard" x-on:click="navigator.clipboard.writeText(@js($cardLink)); copied = true; setTimeout(() => copied = false, 2000)">
                    <span x-show="! copied">{{ __('hr_employees.card.copy_link') }}</span>
                    <span x-show="copied" x-cloak>{{ __('hr_employees.card.copied') }}</span>
                </x-ui.button>
                @if ($cardEnabled && $employee->is_active)
                    <x-ui.button size="sm" icon="arrow-top-right-on-square" :href="$cardLink" target="_blank" rel="noopener">{{ __('hr_employees.card.open') }}</x-ui.button>
                @endif
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-2">
            @if ($canManageCard)
                @if ($employee->is_active && $cardMissing === null)
                    <x-ui.button icon="arrow-path" wire:click="reissueCard({{ $cardIndex }})" wire:confirm="{{ __('hr_employees.card.reissue_confirm') }}" data-test="reissue-card">{{ __('hr_employees.card.reissue') }}</x-ui.button>
                @endif
                @if ($cardEnabled)
                    <x-ui.button icon="no-symbol" wire:click="setCardEnabled(false)" data-test="disable-card">{{ __('hr_employees.card.disable') }}</x-ui.button>
                @elseif ($employee->is_active)
                    <x-ui.button icon="check" wire:click="setCardEnabled(true)" data-test="enable-card">{{ __('hr_employees.card.enable') }}</x-ui.button>
                @endif
            @endif
            @can('reportCardLost', $employee)
                <x-ui.button icon="exclamation-triangle" wire:click="reportCardLost" data-test="report-card-lost">{{ __('hr_employees.card.report_lost') }}</x-ui.button>
            @endcan
        </div>
    </div>
</x-ui.panel>
