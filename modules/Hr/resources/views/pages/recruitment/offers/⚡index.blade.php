<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Enums\RecruitmentJobOfferStatus as Status;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Models\RecruitmentJobOffer;
use Modules\Hr\Support\Recruitment\Labels;

/*
 * The job offers in the viewer's recruitment scope, and those of positions
 * the viewer requested. Nothing is changed from here. No salary column on
 * purpose: pay is shown on the offer itself, to those who may see it.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.offers')] class extends Component {
    use AuthorizesRecruitment;
    use WithPagination;

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $search = '';

    public function mount(): void
    {
        $this->viewer();
        Gate::authorize('viewAny', RecruitmentJobOffer::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'search'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return RecruitmentJobOffer::query()->visibleTo($this->viewer())
            ->select(['id', 'application_id', 'position_id', 'status', 'current_version_id', 'sent_at', 'expires_at', 'updated_at'])
            ->with([
                'application:id,applicant_id',
                'application.applicant:id,full_name',
                'position:id,title_en,title_ar',
                'currentVersion:id,version_no,start_date',
            ])
            ->when(in_array($this->status, Status::values(), true), fn (Builder $query) => $query->where('status', $this->status))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $match) => $match
                ->whereHas('application.applicant', fn (Builder $applicant) => $applicant->whereLike('full_name', '%'.$search.'%'))
                ->orWhereHas('position', fn (Builder $position) => $position->whereLike('title_en', '%'.$search.'%')->orWhereLike('title_ar', '%'.$search.'%'))))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(25);
    }

    /** @return array<string, int> status => count */
    #[Computed]
    public function counts(): array
    {
        return RecruitmentJobOffer::query()->visibleTo($this->viewer())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }
};
?>

@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $rows = $this->rows;
    $counts = $this->counts;
    $fmt = fn ($date) => $date?->translatedFormat(__('formats.date')) ?? '—';
@endphp

<x-desk.register class="@container" heading-id="recruitment-offers-heading" :title="__('hr_recruitment.hub.titles.offers')" :count="$rows->total()">
    @if ($counts !== [])
        <x-slot:figures>
            @foreach ([Status::Sent, Status::Accepted, Status::Draft] as $case)
                {{ __('hr_recruitment.applicant_ui.offers.statuses.'.$case) }}: <span class="font-semibold tabular-nums">{{ $counts[$case] ?? 0 }}</span>@if (! $loop->last) · @endif
            @endforeach
        </x-slot:figures>
    @endif

    <x-slot:search>
        <x-desk.register-search :label="__('hr_recruitment.hub.search')" :placeholder="__('hr_recruitment.hub.search_applicants')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.status')" wire:model.live="status" :value="$status">
            <option value="">{{ __('hr_recruitment.hub.all_statuses') }}</option>
            @foreach (Status::values() as $value)
                <option value="{{ $value }}">{{ __('hr_recruitment.applicant_ui.offers.statuses.'.$value) }}@if (isset($counts[$value])) ({{ $counts[$value] }})@endif</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    <p class="mb-3 text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.index_hint') }}</p>

    @if ($rows->isEmpty())
        <x-ui.empty-state icon="document-text" :heading="$search === '' && $status === '' ? __('hr_recruitment.hub.none') : __('hr_recruitment.hub.no_matching')" />
    @else
        <x-ui.data-table :caption="__('hr_recruitment.hub.titles.offers')">
            <x-slot:head>
                <th>{{ __('hr_recruitment.applicant_ui.name') }}</th>
                <th>{{ __('hr_recruitment.campaign_ui.position') }}</th>
                <th>{{ __('hr_recruitment.applicant_ui.status') }}</th>
                <th>{{ __('hr_recruitment.applicant_ui.offers.version') }}</th>
                <th>{{ __('hr_recruitment.applicant_ui.offers.start_date') }}</th>
                <th>{{ __('hr_recruitment.applicant_ui.offers.sent_at') }}</th>
                <th>{{ __('hr_recruitment.applicant_ui.offers.expires_at') }}</th>
                <th class="w-16!"><span class="sr-only">{{ __('hr_recruitment.hub.actions') }}</span></th>
            </x-slot:head>

            @foreach ($rows as $offer)
                <tr wire:key="offer-{{ $offer->id }}">
                    <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.name')"><span class="font-semibold"><bdi>{{ $offer->application?->applicant?->full_name ?? '—' }}</bdi></span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.campaign_ui.position')"><span dir="auto">{{ $offer->position?->title ?? '—' }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.status')">
                        <x-ui.enum-badge :case="$offer->status" :label="__('hr_recruitment.applicant_ui.offers.statuses.'.$offer->status)" :tone="Labels::tone($offer->status)" />
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.offers.version')"><span class="tabular-nums">v{{ $offer->currentVersion?->version_no }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.offers.start_date')"><span class="whitespace-nowrap tabular-nums">{{ $fmt($offer->currentVersion?->start_date) }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.offers.sent_at')"><span class="whitespace-nowrap tabular-nums">{{ $fmt($offer->sent_at) }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.offers.expires_at')"><span class="whitespace-nowrap tabular-nums">{{ $fmt($offer->expires_at) }}</span></x-ui.data-table.cell>
                    <td>
                        <span class="flex items-center justify-end">
                            <x-ui.button tone="ghost" size="sm" icon="eye" class="size-8!" :href="route('hr.recruitment.offers.show', ['offerId' => $offer->id])" wire:navigate :aria-label="__('hr_recruitment.hub.view')" />
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            <flux:pagination :paginator="$rows" scroll-to="#recruitment-offers-heading" />
        </div>
    @endif
</x-desk.register>
