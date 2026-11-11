{{--
    The Badges tab of an employee profile: warnings, rewards and salary
    increases, newest first. Included by ⚡show.blade.php.
--}}
@use('Modules\Hr\Models\Badge')

@php
    $canAddBadge = auth()->user()->can('create', [Badge::class, $employee]);
    $canDeleteBadge = auth()->user()->can('badges.delete') && $canAddBadge;
@endphp

<x-ui.panel :title="__('hr_employees.tabs.badges')" :count="$this->badges->count()">
    <x-slot:toolbar>
        @if ($canAddBadge)
            <x-ui.button size="sm" icon="plus" wire:click="openBadge" data-test="add-badge">{{ __('hr_employees.badges.add') }}</x-ui.button>
        @endif
    </x-slot:toolbar>

    @if ($this->badges->isEmpty())
        <p class="p-4 text-sm {{ $muted }}">{{ __('hr_employees.badges.none') }}</p>
    @else
        <ul class="divide-y divide-panel-border">
            @foreach ($this->badges as $badge)
                <li class="flex flex-wrap items-start gap-3 px-4 py-3" wire:key="profile-badge-{{ $badge->id }}">
                    <div class="min-w-0 flex-1">
                        <x-hr::employees.badge-summary :badge="$badge" :show-pay="$canViewFinancial" />
                    </div>
                    <span class="flex shrink-0 items-center gap-1">
                        @if ($canAddBadge)
                            <x-ui.button tone="ghost" size="sm" icon="pencil-square" wire:click="openBadge({{ $badge->id }})" :aria-label="__('hr_employees.badges.edit')" />
                        @endif
                        @if ($canDeleteBadge)
                            <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="confirmBadgeDelete({{ $badge->id }})" :aria-label="__('hr_employees.badges.delete')" />
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</x-ui.panel>
