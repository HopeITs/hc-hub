<?php

use App\Support\Modules\Contracts\WorkItemSource;
use App\Support\Modules\ModuleRegistry;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Waiting on me (roadmap 4.9, D7): everything that waits on the viewer, from
 * every place that keeps such a list (the HR inbox, the Desk's Needs you, My
 * reviews), on one page. Each place is a WorkItemSource a module registers;
 * the page reads their rows and their own counts and never restates a rule.
 *
 * Under "Everything" an item two places list (a leave to decide is in the HR
 * inbox and in Needs you) is shown once, under the place registered first.
 */
new #[Layout('layouts::desk'), Title('titles.waiting')] class extends Component {
    #[Url(except: '')]
    public string $source = '';

    #[Url(except: '')]
    public string $search = '';

    /**
     * The places the viewer may have work in, each with its own count and rows.
     *
     * @return list<array{key: string, label: string, href: string|null, count: int, items: list<array{ref: string, title: string, context: string, href: string, since: CarbonInterface|null, urgent: bool}>}>
     */
    #[Computed]
    public function sources(): array
    {
        $user = auth()->user();
        $sources = [];

        foreach (app(ModuleRegistry::class)->workItemSources() as $source) {
            if ($user === null || ! $source->allows($user)) {
                continue;
            }

            $sources[] = [
                'key' => $source->key(),
                'label' => $source->label(),
                'href' => $source->href($user),
                'count' => $source->count($user),
                'items' => $source->items($user),
            ];
        }

        return $sources;
    }

    /**
     * The place picked in the filter; an unknown one from a stale link reads as everything.
     */
    #[Computed]
    public function picked(): string
    {
        return in_array($this->source, array_column($this->sources, 'key'), true) ? $this->source : '';
    }

    /**
     * Every item once, whichever places list it: late or pressing first, then the longest wait.
     *
     * @return Collection<int, array{ref: string, title: string, context: string, href: string, since: CarbonInterface|null, urgent: bool, source: string, from: string}>
     */
    #[Computed]
    public function everything(): Collection
    {
        return $this->itemsOf($this->sources)
            ->unique('ref')
            ->sortBy([
                fn (array $a, array $b): int => $b['urgent'] <=> $a['urgent'],
                fn (array $a, array $b): int => ($a['since']?->getTimestamp() ?? PHP_INT_MAX) <=> ($b['since']?->getTimestamp() ?? PHP_INT_MAX),
            ])
            ->values();
    }

    /**
     * The rows shown: one place's own rows in its own order, or everything, narrowed by the search.
     *
     * @return Collection<int, array{ref: string, title: string, context: string, href: string, since: CarbonInterface|null, urgent: bool, source: string, from: string}>
     */
    #[Computed]
    public function rows(): Collection
    {
        $term = mb_strtolower(trim($this->search));
        $rows = $this->picked === ''
            ? $this->everything
            : $this->itemsOf(array_values(array_filter($this->sources, fn (array $source): bool => $source['key'] === $this->picked)));

        return $rows
            ->filter(fn (array $row): bool => $term === '' || str_contains(mb_strtolower($row['title'].' '.$row['context'].' '.$row['from']), $term))
            ->values();
    }

    public function resetFilters(): void
    {
        $this->reset('source', 'search');
    }

    /**
     * @param  list<array{key: string, label: string, href: string|null, count: int, items: list<array{ref: string, title: string, context: string, href: string, since: CarbonInterface|null, urgent: bool}>}>  $sources
     * @return Collection<int, array{ref: string, title: string, context: string, href: string, since: CarbonInterface|null, urgent: bool, source: string, from: string}>
     */
    private function itemsOf(array $sources): Collection
    {
        $rows = new Collection;

        foreach ($sources as $source) {
            foreach ($source['items'] as $item) {
                $rows->push([...$item, 'source' => $source['key'], 'from' => $source['label']]);
            }
        }

        return $rows;
    }
}; ?>

@php
    $sources = $this->sources;
    $picked = $this->picked;
    $rows = $this->rows;
    $filtered = $picked !== '' || trim($search) !== '';
@endphp

<x-desk.register heading-id="waiting-heading" :title="__('titles.waiting')" :count="$this->everything->count()" data-test="waiting-page">
    @if ($sources !== [])
        <x-slot:figures>
            @foreach ($sources as $place)
                @if (! $loop->first) · @endif
                @if ($place['href'] !== null)
                    <a href="{{ $place['href'] }}" wire:navigate class="underline" title="{{ __('waiting.open_source', ['source' => $place['label']]) }}">{{ $place['label'] }}</a>
                @else
                    {{ $place['label'] }}
                @endif
                <span class="dk-num" data-waiting-count="{{ $place['key'] }}">{{ $place['count'] }}</span>
            @endforeach
        </x-slot:figures>
    @endif

    <x-slot:search>
        <x-desk.register-search :label="__('waiting.search')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select :label="__('waiting.source')" wire:model.live="source" :value="$picked">
            <option value="">{{ __('waiting.all_sources') }} ({{ $this->everything->count() }})</option>
            @foreach ($sources as $place)
                <option value="{{ $place['key'] }}">{{ $place['label'] }} ({{ $place['count'] }})</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    @if ($rows->isEmpty())
        <x-ui.empty-state icon="inbox" :heading="$filtered ? __('waiting.empty_filtered') : __('waiting.empty')">
            @if ($filtered)
                <x-slot:action>
                    <x-ui.button tone="ghost" size="sm" wire:click="resetFilters">{{ __('common.reset_filters') }}</x-ui.button>
                </x-slot:action>
            @endif
        </x-ui.empty-state>
    @else
        <x-ui.data-table :caption="__('titles.waiting')">
            <x-slot:head>
                <th>{{ __('waiting.columns.what') }}</th>
                <th>{{ __('waiting.columns.context') }}</th>
                <th class="w-44!">{{ __('waiting.columns.source') }}</th>
                <th class="w-36!">{{ __('waiting.columns.since') }}</th>
                <th class="w-12!"><span class="sr-only">{{ __('waiting.open') }}</span></th>
            </x-slot:head>

            @foreach ($rows as $row)
                <tr wire:key="waiting-{{ $row['source'] }}-{{ md5($row['ref']) }}" data-waiting-row="{{ $row['source'] }}">
                    <x-ui.data-table.cell>
                        <span class="font-semibold">{{ $row['title'] !== '' ? $row['title'] : '—' }}</span>
                        @if ($row['urgent'])
                            <span class="dk-pill dk-bad ms-1">{{ __('waiting.urgent') }}</span>
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('waiting.columns.context')">{{ $row['context'] !== '' ? $row['context'] : '—' }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('waiting.columns.source')"><span class="dk-pill dk-act">{{ $row['from'] }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('waiting.columns.since')">{{ $row['since']?->diffForHumans() ?? '—' }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell class="@3xl:text-end">
                        @if ($row['href'] !== '')
                            <a href="{{ $row['href'] }}" wire:navigate class="dk-btn">{{ __('waiting.open') }}</a>
                        @endif
                    </x-ui.data-table.cell>
                </tr>
            @endforeach
        </x-ui.data-table>
    @endif
</x-desk.register>
