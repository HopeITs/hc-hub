<?php

use App\Livewire\Concerns\Desk\IsDeskPanel;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Hr\Support\Desk\Cards\HrCard;
use Modules\Hr\Support\Desk\HrPanels;

/**
 * An HR card on the Desk ("hr-…" panels, Modules\Hr\Support\Desk\HrPanels):
 * the old HR dashboard's tiles. One component draws every HR card; its
 * HrCard class says what the card holds (figures and shaped rows, as a page
 * card does), who is offered it and where its rows lead. Rows are computed,
 * never public state, and a card a person may no longer see draws nothing.
 */
new class extends Component {
    use IsDeskPanel;

    /**
     * The card's Desk item id ("hr-who-is-out").
     */
    #[Locked]
    public string $card = '';

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->page = 0;
    }

    #[Computed]
    public function source(): ?HrCard
    {
        return app(HrPanels::class)->forUser(auth()->user(), $this->card);
    }

    /**
     * @return list<array{label: string, value: string, tone: string}>
     */
    #[Computed]
    public function figures(): array
    {
        return $this->source?->figures(auth()->user()) ?? [];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function rows(): Collection
    {
        return $this->source?->rows(auth()->user(), $this->search) ?? new Collection;
    }

    /**
     * A row filters by its state (the badge) and, where a card mixes kinds, by kind.
     *
     * @return array<string, string>
     */
    protected function facetsOf(mixed $row): array
    {
        $facets = [];

        if (is_array($row) && is_array($row['badge'] ?? null) && $row['badge']['label'] !== '') {
            $facets['status'] = $row['badge']['label'];
        }

        if (is_array($row) && is_string($row['kind'] ?? null) && $row['kind'] !== '') {
            $facets['type'] = $row['kind'];
        }

        return $facets;
    }
};
?>

@php
    $source = $this->source;
    $key = $source?->key() ?? \Illuminate\Support\Str::after($card, HrPanels::Prefix);
    $figures = $this->figures;
    $rows = $this->rows;
    $pg = $this->paginate($rows, self::PerPage);
    $register = $source?->register();
    $register = $register !== null && \Illuminate\Support\Facades\Route::has($register) ? $register : null;
    $tagline = \Illuminate\Support\Facades\Lang::has("hr_desk.cards.{$key}.tagline") ? __("hr_desk.cards.{$key}.tagline") : '';
@endphp

<x-desk.panel :empty="$source === null || $rows->isEmpty()"
    :id="$card"
    :title="__('hr_desk.cards.'.$key.'.title')"
    :tagline="$tagline"
    :facets="$pg['facets']"
    :register="$register"
    :size="$size"
    :cols="$gridCols"
    :height="$gridRows"
    :index="$index"
    :total="$total"
    :count="$rows->isNotEmpty() ? $rows->count() : null"
    :health="$source?->health(auth()->user())"
    search="search"
    :search-value="$search"
    class="dk-pagecard"
    data-hr-card="{{ $key }}"
>
    @if ($source === null)
        <p class="dk-pempty">{{ __('hr_desk.unavailable') }}</p>
    @else
        <x-hr::desk.card-body
            :card="$card"
            :layout="$source->layout()"
            :figures="$figures"
            :pg="$pg"
            :fit="$this->fitAttributes($pg)"
            :empty="$search !== '' || $this->facets !== [] ? __('hr_desk.no_match') : $source->emptyText()"
        />
    @endif

    <x-slot:foot>
        <span class="dk-card-pager"><x-desk.pager :pg="$pg" /></span>
    </x-slot:foot>
</x-desk.panel>
