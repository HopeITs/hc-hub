<?php

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Hr\Support\Employees\WorkPresence;
use Modules\Hr\Support\Inbox\WorkInbox;

/**
 * Waiting on you: every HR item this person must act on, oldest first —
 * leave requests to decide or review, checklist items to hand over, take or
 * close, profile changes to approve, and onboarding, renewal and exit
 * journeys still open. Each row opens the page where the decision is made.
 */
new #[Layout('layouts::desk'), Title('hr_communications.titles.inbox')] class extends Component {
    public const array Kinds = ['decide', 'review', 'checklist', 'profile', 'journey'];

    #[Url(except: '')]
    public string $kind = '';

    #[Url(except: '')]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(app(WorkInbox::class)->hasSources(auth()->user()), 403);
    }

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, counts: array<string, int>}
     */
    #[Computed]
    public function inbox(): array
    {
        return app(WorkInbox::class)->for(auth()->user());
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function rows(): Collection
    {
        $term = mb_strtolower(trim($this->search));

        return $this->inbox['rows']
            ->filter(fn (array $row): bool => match ($this->kind) {
                'decide', 'review' => $row['source'] === 'leave' && $row['action'] === $this->kind,
                'checklist', 'profile', 'journey' => $row['source'] === $this->kind,
                default => true,
            })
            ->filter(fn (array $row): bool => $term === '' || str_contains(mb_strtolower($row['name'].' '.$row['words'].' '.$row['what'].' '.$row['city']), $term))
            ->values();
    }

    /**
     * The presence dot of each person in the rows shown (WorkPresence), read
     * once for the whole list.
     *
     * @return array<int, array{tone: string, label: string}>
     */
    #[Computed]
    public function presence(): array
    {
        return app(WorkPresence::class)->for(auth()->user(), $this->rows->pluck('employee_id')->filter()->unique()->all());
    }

    public function resetFilters(): void
    {
        $this->reset('kind', 'search');
    }
}; ?>

@php
    $rows = $this->rows;
    $counts = $this->inbox['counts'];
    $presence = $rows->isEmpty() ? [] : $this->presence;
@endphp

<x-desk.register heading-id="hr-inbox-heading" :title="__('hr_communications.titles.inbox')" :count="$counts['all']">
    <x-slot:search>
        <x-desk.register-search :label="__('hr_communications.inbox.search')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select :label="__('hr_communications.inbox.kind')" wire:model.live="kind" :value="$kind">
            <option value="">{{ __('hr_communications.inbox.kinds.all') }} ({{ $counts['all'] }})</option>
            @foreach (['decide', 'review', 'checklist', 'profile', 'journey'] as $option)
                @if (($counts[$option] ?? 0) > 0)
                    <option value="{{ $option }}">{{ __('hr_communications.inbox.kinds.'.$option) }} ({{ $counts[$option] }})</option>
                @endif
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    @if ($rows->isEmpty())
        <x-ui.empty-state icon="inbox" :heading="$kind !== '' || $search !== '' ? __('hr_communications.inbox.empty_filtered') : __('hr_communications.inbox.empty')">
            @if ($kind !== '' || $search !== '')
                <x-slot:action>
                    <x-ui.button tone="ghost" size="sm" wire:click="resetFilters">{{ __('common.reset_filters') }}</x-ui.button>
                </x-slot:action>
            @endif
        </x-ui.empty-state>
    @else
        <x-ui.data-table :caption="__('hr_communications.titles.inbox')">
            <x-slot:head>
                <th>{{ __('hr_communications.inbox.columns.who') }}</th>
                <th class="w-44!">{{ __('hr_communications.inbox.columns.action') }}</th>
                <th>{{ __('hr_communications.inbox.columns.what') }}</th>
                <th class="w-40!">{{ __('hr_communications.inbox.columns.when') }}</th>
                <th class="w-32!">{{ __('hr_communications.inbox.columns.since') }}</th>
                <th class="w-12!"><span class="sr-only">{{ __('hr_communications.inbox.open') }}</span></th>
            </x-slot:head>

            @foreach ($rows as $row)
                <tr wire:key="inbox-{{ $row['key'] }}">
                    <x-ui.data-table.cell>
                        <x-hr::employees.presence :status="$presence[$row['employee_id']] ?? null" class="me-1" />
                        <span class="font-semibold">{{ $row['name'] }}</span>
                        @if ($row['city'] !== '')
                            <span class="type-meta block text-secondary-medium dark:text-ink-muted">{{ $row['city'] }}</span>
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_communications.inbox.columns.action')">
                        <span @class(['dk-pill', 'dk-bad' => $row['urgent'] || $row['started'], 'dk-act' => ! $row['urgent'] && ! $row['started']])>{{ __('hr_communications.inbox.action.'.$row['action']) }}</span>
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_communications.inbox.columns.what')">{{ $row['what'] !== '' ? $row['what'] : '—' }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_communications.inbox.columns.when')" numeric>
                        @if ($row['from'])
                            {{ $row['from']->translatedFormat(__('formats.date_short')) }}@if ($row['to'] && ! $row['to']->isSameDay($row['from'])) – {{ $row['to']->translatedFormat(__('formats.date_short')) }}@endif
                            @if ($row['started'])
                                <span class="type-meta block text-status-critical">{{ __('hr_communications.inbox.started') }}</span>
                            @endif
                        @else
                            —
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_communications.inbox.columns.since')">{{ $row['since']?->diffForHumans() ?? '—' }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell class="@3xl:text-end">
                        @if ($row['href'] !== '')
                            <a href="{{ $row['href'] }}" wire:navigate class="dk-btn">{{ __('hr_communications.inbox.open') }}</a>
                        @endif
                    </x-ui.data-table.cell>
                </tr>
            @endforeach
        </x-ui.data-table>
    @endif
</x-desk.register>
