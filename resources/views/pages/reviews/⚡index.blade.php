<?php

use App\Models\HrEmployee;
use App\Models\ProposalReviewer;
use App\Support\Desk\DeskWork;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::desk'), Title('titles.my_reviews')] class extends Component {
    /**
     * A review waiting longer than this many days turns red, the same line
     * the Desk's My reviews panel draws.
     */
    public const int LongWaitDays = 7;

    /** awaiting (waiting on me), all (every review asked of me) or asked (reviews I wait on). */
    #[Url(except: 'awaiting')]
    public string $state = 'awaiting';

    /**
     * The filter in force; an unknown value from a stale link reads as awaiting.
     */
    #[Computed]
    public function mode(): string
    {
        return in_array($this->state, ['all', 'asked'], true) ? $this->state : 'awaiting';
    }

    /**
     * The rows of the chosen filter, the ones still waiting first, then the
     * oldest ask first (as on the Desk). Mine: the viewer's own reviewer rows.
     * Asked: other people's unreviewed rows on proposals of the viewer's
     * projects, exactly the reviews the Desk's Waiting on others panel lists
     * (DeskWork::waitingReviews). Anyone may open the page: it never reaches
     * beyond those rows, and each proposal opens through ProposalPolicy.
     *
     * @return Collection<int, ProposalReviewer>
     */
    #[Computed]
    public function reviews(): Collection
    {
        $user = auth()->user();

        if ($user?->employee_id === null) {
            return new Collection;
        }

        $query = $this->mode === 'asked'
            ? DeskWork::waitingReviews($user)
            :ProposalReviewer::query()->forEmployee((int) $user->employee_id)->when($this->mode === 'awaiting', fn ($query) => $query->awaitingReview());

        return $query
            ->with(['proposal:id,slug,name,status', 'proposal.projects:projects.id,projects.slug,projects.name'])
            ->oldest('created_at')
            ->oldest('id')
            ->get()
            ->sortBy(fn (ProposalReviewer $review): int => $review->isAwaitingReview($review->proposal) ? 0 : 1)
            ->values();
    }

    /**
     * The reviewers' names on Asked by me, one HR query.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function reviewerNames(): Collection
    {
        if ($this->mode !== 'asked' || $this->reviews->isEmpty()) {
            return new Collection;
        }

        return HrEmployee::query()
            ->whereKey($this->reviews->pluck('employee_id')->unique()->values()->all())
            ->get(HrEmployee::NameColumns)
            ->mapWithKeys(fn (HrEmployee $employee): array => [$employee->id => $employee->name]);
    }
};
?>

@php
    $mode = $this->mode;
    $asked = $mode === 'asked';
    $viewer = auth()->user();
    $today = \Carbon\CarbonImmutable::today();
@endphp

<x-desk.register
    class="@container/directory"
    heading-id="my-reviews-heading"
    :title="__('titles.my_reviews')"
    :count="$this->reviews->count()"
    data-my-reviews
>
    <x-slot:filters>
            <x-ui.header-filter-select :label="__('proposals.my_reviews_show')" wire:model.live="state" :value="$mode">
                <option value="awaiting">{{ __('proposals.my_reviews_awaiting') }}</option>
                <option value="all">{{ __('proposals.my_reviews_all') }}</option>
                <option value="asked">{{ __('proposals.my_reviews_asked') }}</option>
            </x-ui.header-filter-select>
        </x-slot:filters>


    @if ($this->reviews->isEmpty())
        <x-ui.empty-state
            icon="check-circle"
            :heading="match ($mode) { 'all' => __('proposals.my_reviews_none'), 'asked' => __('proposals.my_reviews_none_asked'), default => __('proposals.my_reviews_none_awaiting') }"
            :body="$asked ? __('proposals.my_reviews_asked_empty_body') : __('proposals.my_reviews_empty_body')"
        />
    @else
        <x-ui.data-table :caption="__('titles.my_reviews')">
            <x-slot:head>
                <th>{{ __('common.proposal') }}</th>
                <th class="w-48!">{{ __('proposals.my_reviews_stage') }}</th>
                <th class="w-28!">{{ __('proposals.my_reviews_waiting') }}</th>
                <th class="w-44!">{{ $asked ? __('proposals.my_reviews_reviewer') : __('proposals.my_reviews_your_review') }}</th>
                <th class="w-28!"><span class="sr-only">{{ __('common.actions') }}</span></th>
            </x-slot:head>

            @foreach ($this->reviews as $review)
                @php
                    $isAwaiting = $review->isAwaitingReview($review->proposal);
                    $project = $review->proposal->projects->sortBy('id')->first();
                    $waitedDays = $review->created_at === null ? 0 : (int) $review->created_at->startOfDay()->diffInDays($today);
                    $isLong = $isAwaiting && $waitedDays > $this::LongWaitDays;
                    $askedOn = $review->created_at?->translatedFormat(__('formats.date'));
                @endphp
                <tr wire:key="review-{{ $review->id }}" data-review-row="{{ $isAwaiting ? 'awaiting' : 'done' }}" @if ($isLong) data-review-long @endif>
                    <x-ui.data-table.cell>
                        <span class="block font-semibold" dir="auto">{{ $review->proposal->name }}</span>
                        @if ($project !== null)
                            <span class="mt-0.5 block text-xs text-secondary-medium dark:text-ink-muted" dir="auto" data-review-project>{{ $project->name }}</span>
                        @endif
                    </x-ui.data-table.cell>

                    <x-ui.data-table.cell :label="__('proposals.my_reviews_stage')">
                        {{ $review->stage->reviewStatus()->label() }}
                    </x-ui.data-table.cell>

                    <x-ui.data-table.cell :label="__('proposals.my_reviews_waiting')">
                        @if ($isAwaiting)
                            <time datetime="{{ $review->created_at?->toIso8601String() }}" title="{{ __('proposals.my_reviews_asked_on_date', ['date' => $askedOn]) }}" @class(['whitespace-nowrap tabular-nums', 'inline-flex rounded-full bg-status-critical-soft px-2 py-0.5 text-xs font-bold text-status-critical dark:bg-brand dark:text-white' => $isLong]) data-review-waiting="{{ $waitedDays }}">{{ trans_choice('proposals.my_reviews_waiting_days', $waitedDays, ['count' => $waitedDays]) }}</time>
                        @else
                            <span class="text-secondary-medium dark:text-ink-muted" title="{{ __('proposals.my_reviews_asked_on_date', ['date' => $askedOn]) }}">—</span>
                        @endif
                    </x-ui.data-table.cell>

                    <x-ui.data-table.cell :label="$asked ? __('proposals.my_reviews_reviewer') : __('proposals.my_reviews_your_review')">
                        @if ($asked)
                            <span class="block font-medium">{{ $this->reviewerNames->get($review->employee_id, '—') }}</span>
                            <span class="block text-xs text-secondary-medium dark:text-ink-muted">{{ $isAwaiting ? __('proposals.reviewer_not_seen') : __('proposals.my_reviews_closed') }}</span>
                        @elseif ($isAwaiting)
                            {{-- Dark red text on the dark ground reads under 3:1, so a long wait fills the pill in dark mode. --}}
                            <span @class([
                                'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold whitespace-nowrap',
                                'bg-status-critical-soft text-status-critical dark:bg-brand dark:text-white' => $isLong,
                                'bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink' => ! $isLong,
                            ])>{{ __('proposals.my_reviews_waiting_on_you') }}</span>
                        @elseif ($review->seen_at !== null)
                            <span class="text-xs text-secondary-medium dark:text-ink-muted">{{ __('proposals.reviewer_seen_on', ['date' => $review->seen_at->translatedFormat(__('formats.date'))]) }}</span>
                        @else
                            <span class="text-xs text-secondary-medium dark:text-ink-muted">{{ __('proposals.my_reviews_closed') }}</span>
                        @endif
                    </x-ui.data-table.cell>

                    {{-- A text button, not the register eye (user decision 2026-11-03): reviewing is this page's one job. --}}
                    <x-ui.data-table.cell class="@3xl:text-end @max-3xl:[&>span]:w-full">
                        @if ($asked)
                            <x-ui.button size="sm" class="min-h-10 touch-manipulation @max-3xl:w-full" :href="\App\Support\ProposalStoryLink::reviewers($viewer, $review->proposal)" wire:navigate :aria-label="__('proposals.my_reviews_open_name', ['name' => $review->proposal->name])" data-review-action>{{ __('proposals.my_reviews_open') }}</x-ui.button>
                        @elseif ($isAwaiting)
                            <x-ui.button tone="primary" size="sm" class="min-h-10 touch-manipulation @max-3xl:w-full" :href="\App\Support\ProposalStoryLink::review($viewer, $review->proposal)" wire:navigate :aria-label="__('proposals.my_reviews_review_name', ['name' => $review->proposal->name])" data-review-action>{{ __('desk.review_action') }}</x-ui.button>
                        @else
                            <x-ui.button size="sm" class="min-h-10 touch-manipulation @max-3xl:w-full" :href="\App\Support\ProposalStoryLink::review($viewer, $review->proposal, waiting: false)" wire:navigate :aria-label="__('proposals.my_reviews_open_name', ['name' => $review->proposal->name])" data-review-action>{{ __('proposals.my_reviews_open') }}</x-ui.button>
                        @endif
                    </x-ui.data-table.cell>
                </tr>
            @endforeach
        </x-ui.data-table>
    @endif
</x-desk.register>
