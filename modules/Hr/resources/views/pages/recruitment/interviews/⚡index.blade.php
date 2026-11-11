<?php

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Enums\RecruitmentInterviewStatus;
use Modules\Hr\Enums\RecruitmentInterviewType;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Livewire\Concerns\Recruitment\ReadsInterviews;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\RecruitmentInterview;
use Modules\Hr\Models\RecruitmentInterviewParticipant;
use Modules\Hr\Support\Recruitment\Labels;

/*
 * The interviews page: a short briefing (the viewer's next interview, the
 * evaluations waiting on them, this week's figures) above a table, a board
 * of where each interview stands, or a month calendar. Every row comes
 * through RecruitmentInterview::visibleTo() and the filters only narrow it.
 * Nothing changes from here; each interview opens on its own page.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.interviews')] class extends Component
{
    use AuthorizesRecruitment;
    use ReadsInterviews;
    use WithPagination;

    private const LANES = ['upcoming', 'awaiting', 'held', 'closed'];

    private const LANE_PAGE = 8;

    #[Url(except: 'table')]
    public string $view = 'table';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $campaign = '';

    #[Url(except: '')]
    public string $participant = '';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    #[Url(except: false)]
    public bool $mine = false;

    #[Url(except: '')]
    public string $calendarMonth = '';

    /** Cards per board lane; only showMore() grows it, so a tampered request cannot ask for an unbounded page. */
    #[Locked]
    public array $limits = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', RecruitmentInterview::class);

        if (! in_array($this->view, ['table', 'board', 'calendar'], true)) {
            $this->view = 'table';
        }

        $this->limits = array_fill_keys(self::LANES, self::LANE_PAGE);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'type', 'campaign', 'participant', 'dateFrom', 'dateTo', 'mine'], true)) {
            $this->resetPage();
            $this->limits = array_fill_keys(self::LANES, self::LANE_PAGE);
        }
    }

    public function showMore(string $lane): void
    {
        if (in_array($lane, self::LANES, true)) {
            $this->limits[$lane] = min(($this->limits[$lane] ?? self::LANE_PAGE) + self::LANE_PAGE * 2, 500);
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type', 'campaign', 'participant', 'dateFrom', 'dateTo', 'mine');
        $this->resetPage();
        $this->limits = array_fill_keys(self::LANES, self::LANE_PAGE);
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return $this->filtered()
            ->select($this->interviewColumns())
            ->with($this->interviewRelations())
            ->orderByDesc('recruitment_interviews.scheduled_at')
            ->orderByDesc('recruitment_interviews.id')
            ->paginate(25);
    }

    /** @return array<string, array{count: int, items: \Illuminate\Support\Collection}> */
    #[Computed]
    public function lanes(): array
    {
        $pendingIds = $this->filtered()->awaitingEvaluationBy($this->viewer())->pluck('recruitment_interviews.id')->all();
        $lanes = [];

        foreach (self::LANES as $lane) {
            $query = $this->filtered();

            match ($lane) {
                'upcoming' => $query->upcoming()->orderBy('recruitment_interviews.scheduled_at'),
                'awaiting' => $query->heldOrPast()->evaluationsClosed(false)
                    // The viewer's own pending evaluations first, then the most recent.
                    ->when($pendingIds !== [], fn (Builder $q) => $q->orderByRaw('case when recruitment_interviews.id in ('.implode(',', array_map('intval', $pendingIds)).') then 0 else 1 end'))
                    ->orderByDesc('recruitment_interviews.scheduled_at'),
                'held' => $query->heldOrPast()->evaluationsClosed()->orderByDesc('recruitment_interviews.scheduled_at'),
                'closed' => $query->whereIn('recruitment_interviews.status', [RecruitmentInterviewStatus::Cancelled, RecruitmentInterviewStatus::NoShow])->orderByDesc('recruitment_interviews.scheduled_at'),
            };

            $lanes[$lane] = [
                'count' => (clone $query)->count(),
                'items' => $query->select($this->interviewColumns())->with($this->interviewRelations())->limit($this->limits[$lane] ?? self::LANE_PAGE)->get(),
            ];
        }

        return $lanes + ['pending' => array_flip($pendingIds)];
    }

    /** @return list<array{date: string, label: string, href: string}> */
    #[Computed]
    public function calendarItems(): array
    {
        $start = $this->calendarMonthStart();

        return $this->filtered()
            ->select($this->interviewColumns())
            ->with(['application:id,applicant_id', 'application.applicant:id,full_name'])
            ->whereBetween('recruitment_interviews.scheduled_at', [$start, $start->endOfMonth()])
            ->orderBy('recruitment_interviews.scheduled_at')
            ->limit(500)
            ->get()
            ->map(function (RecruitmentInterview $interview): array {
                $at = $this->zoned($interview, $interview->scheduled_at);

                return [
                    'date' => $at->toDateString(),
                    'label' => $at->format('H:i').' '.($interview->application?->applicant?->full_name ?? '#'.$interview->id),
                    'href' => route('hr.recruitment.interviews.show', ['interviewId' => $interview->id]),
                ];
            })
            ->all();
    }

    public function calendarMonthStart(): CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}$/', $this->calendarMonth) === 1) {
            try {
                return CarbonImmutable::createFromFormat('!Y-m', $this->calendarMonth)->startOfMonth();
            } catch (Throwable) {
            }
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    /** @return array<string, mixed> the viewer's next interview, what waits on them, and this week */
    #[Computed]
    public function briefing(): array
    {
        $user = $this->viewer();
        $upcoming = fn (): Builder => RecruitmentInterview::query()->visibleTo($user)->upcoming()
            ->select($this->interviewColumns())->with($this->interviewRelations())->orderBy('recruitment_interviews.scheduled_at');

        // The viewer's own next interview first; HR with nothing of their own
        // sees the next one in their scope instead, and the heading says so.
        $next = $upcoming()->whereHas('currentParticipants', fn (Builder $p) => $p->where('user_id', $user->getKey()))->first();
        $nextIsMine = $next !== null;
        $next ??= $upcoming()->first();

        $waiting = RecruitmentInterview::query()->visibleTo($user)->awaitingEvaluationBy($user);

        // The working week starts on Saturday, as in the old calendar.
        $weekStart = now()->startOfWeek(Carbon::SATURDAY);
        $week = RecruitmentInterview::query()->visibleTo($user)
            ->whereBetween('recruitment_interviews.scheduled_at', [$weekStart, $weekStart->copy()->addDays(7)->subSecond()])
            ->get(['recruitment_interviews.id', 'recruitment_interviews.status']);

        return [
            'next' => $next,
            'nextIsMine' => $nextIsMine,
            'waitingCount' => (clone $waiting)->count(),
            'waiting' => $waiting->select($this->interviewColumns())->with($this->interviewRelations())->orderBy('recruitment_interviews.scheduled_at')->limit(3)->get(),
            'week' => [
                'all' => $week->count(),
                'ahead' => $week->filter(fn (RecruitmentInterview $i): bool => RecruitmentInterviewStatus::isOpen($i->status))->count(),
                'held' => $week->where('status', RecruitmentInterviewStatus::Completed)->count(),
                'off' => $week->whereIn('status', [RecruitmentInterviewStatus::Cancelled, RecruitmentInterviewStatus::NoShow])->count(),
            ],
        ];
    }

    /** @return array{campaigns: array<int, string>, participants: array<int, string>} options that have at least one visible interview */
    #[Computed]
    public function options(): array
    {
        $user = $this->viewer();

        $campaigns = HiringCampaign::query()
            ->whereHas('applications.interviews', fn (Builder $q) => $q->visibleTo($user))
            ->with(['vacancy:id,position_id', 'vacancy.position:id,title_en,title_ar'])
            ->get(['hiring_campaigns.id', 'hiring_campaigns.vacancy_id'])
            ->mapWithKeys(fn (HiringCampaign $campaign): array => [$campaign->id => '#'.$campaign->id.' · '.($campaign->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.no_vacancy'))])
            ->all();

        $participants = RecruitmentInterviewParticipant::query()->current()
            ->whereHas('interview', fn (Builder $q) => $q->visibleTo($user))
            ->distinct()
            ->pluck('user_id')
            ->mapWithKeys(fn ($id): array => [(int) $id => \Modules\Core\Models\User::directory()[(int) $id]['name'] ?? '#'.$id])
            ->sort()
            ->all();

        return ['campaigns' => $campaigns, 'participants' => $participants];
    }

    private function filtered(): Builder
    {
        $user = $this->viewer();
        $search = trim($this->search);

        return RecruitmentInterview::query()
            ->visibleTo($user)
            ->when(in_array($this->type, RecruitmentInterviewType::values(), true), fn (Builder $q) => $q->where('recruitment_interviews.interview_type', $this->type))
            ->when(ctype_digit($this->campaign), fn (Builder $q) => $q->whereHas('application', fn (Builder $a) => $a->where('campaign_id', (int) $this->campaign)))
            ->when(ctype_digit($this->participant), fn (Builder $q) => $q->whereHas('currentParticipants', fn (Builder $p) => $p->where('user_id', (int) $this->participant)))
            ->when($this->validDate($this->dateFrom), fn (Builder $q) => $q->whereDate('recruitment_interviews.scheduled_at', '>=', $this->dateFrom))
            ->when($this->validDate($this->dateTo), fn (Builder $q) => $q->whereDate('recruitment_interviews.scheduled_at', '<=', $this->dateTo))
            ->when($this->mine, fn (Builder $q) => $q->whereHas('currentParticipants', fn (Builder $p) => $p->where('user_id', $user->getKey())))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                // Grouped, so the OR stays inside the application's own constraint.
                $q->whereHas('application', fn (Builder $a) => $a->where(fn (Builder $either) => $either
                    ->whereHas('applicant', fn (Builder $applicant) => $applicant->where('full_name', 'like', $like))
                    ->orWhereHas('vacancy.position', fn (Builder $position) => $position->where('title_en', 'like', $like)->orWhere('title_ar', 'like', $like))));
            });
    }

    private function validDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 && $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
};
?>

@php
    $t = 'hr_recruitment.applicant_ui.interviews.';
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $zoned = fn ($interview, $date) => $date?->copy()->setTimezone($interview->timezone ?: config('app.timezone'));
    $briefing = $this->briefing;
    $options = $this->options;
    $filtering = $search !== '' || $type !== '' || $campaign !== '' || $participant !== '' || $dateFrom !== '' || $dateTo !== '' || $mine;
    $total = match ($view) {
        'table' => $this->rows->total(),
        default => null,
    };
@endphp

<x-desk.register
    class="@container"
    heading-id="recruitment-interviews-heading"
    :title="__('hr_recruitment.hub.titles.interviews')"
    :count="$total"
    :view="$view"
    :views="[
        'table' => ['icon' => 'table', 'label' => __('desk.register.table')],
        'board' => ['icon' => 'board', 'label' => __('desk.register.board')],
        'calendar' => ['icon' => 'cal', 'label' => __('desk.register.calendar')],
    ]"
>
    <x-slot:figures>
        {{ __($t.'board.week_title') }}:
        @foreach (['all', 'ahead', 'held', 'off'] as $key)
            {{ __($t.'board.totals.'.$key) }} <span class="font-semibold tabular-nums">{{ $briefing['week'][$key] }}</span>@if (! $loop->last) · @endif
        @endforeach
    </x-slot:figures>

    <x-slot:search>
        <x-desk.register-search :label="__('hr_recruitment.hub.search')" :placeholder="__($t.'board.search')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select :label="__($t.'board.round')" wire:model.live="type" :value="$type">
            <option value="">{{ __($t.'board.all_rounds') }}</option>
            @foreach (RecruitmentInterviewType::values() as $value)
                <option value="{{ $value }}">{{ __($t.'types.'.$value) }}</option>
            @endforeach
        </x-ui.header-filter-select>
        @if ($options['campaigns'] !== [])
            <x-ui.header-filter-select :label="__($t.'board.campaign')" wire:model.live="campaign" :value="$campaign">
                <option value="">{{ __($t.'board.all_campaigns') }}</option>
                @foreach ($options['campaigns'] as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
        @if ($options['participants'] !== [])
            <x-ui.header-filter-select :label="__($t.'board.participant')" wire:model.live="participant" :value="$participant">
                <option value="">{{ __($t.'board.all_participants') }}</option>
                @foreach ($options['participants'] as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="date" wire:model.live="dateFrom" aria-label="{{ __($t.'board.date_from') }}" class="h-9 rounded-md border border-secondary-light bg-white px-2 text-sm dark:border-line dark:bg-surface-2" />
            <span aria-hidden="true">→</span>
            <input type="date" wire:model.live="dateTo" aria-label="{{ __($t.'board.date_to') }}" class="h-9 rounded-md border border-secondary-light bg-white px-2 text-sm dark:border-line dark:bg-surface-2" />
        </label>
        <flux:checkbox wire:model.live="mine" :label="__($t.'mine')" />
        @if ($filtering)
            <x-ui.button tone="ghost" size="sm" wire:click="clearFilters">{{ __($t.'board.clear') }}</x-ui.button>
        @endif
    </x-slot:filters>

    {{-- The briefing: what is next, and what is waiting on the viewer. --}}
    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <x-ui.panel :title="__($t.'board.'.($briefing['nextIsMine'] || ! $briefing['next'] ? 'next_mine' : 'next_scope'))">
            @if ($next = $briefing['next'])
                @php
                    $start = $zoned($next, $next->scheduled_at);
                    $end = $zoned($next, $next->ends_at);
                @endphp
                <div class="space-y-1 p-4 text-sm">
                    <a href="{{ route('hr.recruitment.interviews.show', ['interviewId' => $next->id]) }}" wire:navigate class="text-base font-semibold underline-offset-4 hover:underline" dir="auto">{{ $next->application?->applicant?->full_name }}</a>
                    <p class="{{ $muted }}" dir="auto">{{ $next->application?->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.no_vacancy') }} · {{ __($t.'types.'.$next->interview_type) }}</p>
                    <p class="tabular-nums">{{ $start->translatedFormat('l j F Y') }} · {{ $start->format('H:i') }}@if ($end)–{{ $end->format('H:i') }}@endif
                        <span class="{{ $muted }}">({{ $next->timezone ?: config('app.timezone') }})</span></p>
                    <p>{{ __($t.'location_types.'.$next->location_type) }}@if ($next->location_value) · <span dir="auto">{{ $next->location_value }}</span>@endif</p>
                    @if ($next->currentParticipants->isNotEmpty())
                        <p class="{{ $muted }}">{{ __($t.'board.panel') }}: {{ $next->currentParticipants->map(fn ($p) => $p->user_id === auth()->id() ? __($t.'board.you') : $p->user?->name)->implode('، ') }}</p>
                    @endif
                </div>
            @else
                <div class="p-4 text-sm">
                    <p class="font-medium">{{ __($t.'board.none_ahead') }}</p>
                    <p class="{{ $muted }}">{{ __($t.'board.none_ahead_hint') }}</p>
                </div>
            @endif
        </x-ui.panel>

        <x-ui.panel :title="__($t.'board.waiting_title')" :subtitle="__($t.'board.waiting_hint')" :count="$briefing['waitingCount']">
            @if ($briefing['waiting']->isEmpty())
                <div class="p-4 text-sm">
                    <p class="font-medium">{{ __($t.'board.caught_up') }}</p>
                    <p class="{{ $muted }}">{{ __($t.'board.caught_up_hint') }}</p>
                </div>
            @else
                <ul class="divide-y divide-panel-border">
                    @foreach ($briefing['waiting'] as $waiting)
                        <li class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm" wire:key="waiting-{{ $waiting->id }}">
                            <div class="min-w-0">
                                <p class="font-semibold" dir="auto">{{ $waiting->application?->applicant?->full_name }}</p>
                                <p class="text-xs {{ $muted }}">{{ __($t.'types.'.$waiting->interview_type) }} · <span class="tabular-nums">{{ $zoned($waiting, $waiting->scheduled_at)->format('Y-m-d H:i') }}</span></p>
                            </div>
                            <x-ui.button size="sm" tone="secondary" :href="route('hr.recruitment.interviews.show', ['interviewId' => $waiting->id]).'#my-evaluation'" wire:navigate>{{ __($t.'board.evaluate') }}</x-ui.button>
                        </li>
                    @endforeach
                </ul>
                @if ($briefing['waitingCount'] > 3)
                    <p class="border-t border-panel-border px-4 py-2 text-xs {{ $muted }}">{{ trans_choice($t.'board.waiting_more', $briefing['waitingCount'] - 3, ['count' => $briefing['waitingCount'] - 3]) }}</p>
                @endif
            @endif
        </x-ui.panel>
    </div>

    @if ($view === 'calendar')
        <x-desk.month-calendar :month="$this->calendarMonthStart()->toDateString()" :items="$this->calendarItems" />
    @elseif ($view === 'board')
        @php $lanes = $this->lanes; @endphp
        <x-desk.board data-test="interview-board">
            @foreach (['upcoming', 'awaiting', 'held', 'closed'] as $lane)
                <x-desk.board-column :label="__($t.'board.lanes.'.$lane)" :count="$lanes[$lane]['count']" :key="$lane" wire:key="interview-lane-{{ $lane }}">
                    @foreach ($lanes[$lane]['items'] as $card)
                        @php $cardStart = $zoned($card, $card->scheduled_at); @endphp
                        <x-desk.board-card :href="route('hr.recruitment.interviews.show', ['interviewId' => $card->id])" wire:key="interview-card-{{ $card->id }}">
                            <strong dir="auto">{{ $card->application?->applicant?->full_name ?? '—' }}</strong>
                            <span class="dk-is" dir="auto">{{ $card->application?->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.no_vacancy') }}</span>
                            <span class="dk-small">{{ __($t.'types.'.$card->interview_type) }} · <span class="dk-num">{{ $cardStart->format('Y-m-d H:i') }}</span></span>
                            @if ($lane === 'awaiting' && isset($lanes['pending'][$card->id]))
                                <x-ui.enum-badge class="self-start" :case="'pending'" :label="__('hr_recruitment.applicant_ui.evaluations.my_pending')" :tone="\App\Enums\StatusTone::Warn" />
                            @elseif ($lane === 'closed')
                                <x-ui.enum-badge class="self-start" :case="$card->status" :label="__($t.'statuses.'.$card->status)" :tone="Labels::tone($card->status)" />
                            @endif
                        </x-desk.board-card>
                    @endforeach
                    @if ($lanes[$lane]['count'] > $lanes[$lane]['items']->count())
                        <x-ui.button size="sm" tone="ghost" wire:click="showMore('{{ $lane }}')">{{ __($t.'board.show_more') }}</x-ui.button>
                    @endif
                </x-desk.board-column>
            @endforeach
        </x-desk.board>
    @else
        @php $rows = $this->rows; @endphp
        @if ($rows->isEmpty())
            <x-ui.empty-state icon="calendar-days" :heading="$filtering ? __($t.'board.no_match') : __($t.'none_yet')" />
        @else
            <x-ui.data-table :caption="__('hr_recruitment.hub.titles.interviews')">
                <x-slot:head>
                    <th>{{ __('hr_recruitment.applicant_ui.name') }}</th>
                    <th>{{ __('hr_recruitment.campaign_ui.position') }}</th>
                    <th>{{ __($t.'type') }}</th>
                    <th>{{ __($t.'when') }}</th>
                    <th>{{ __($t.'location') }}</th>
                    <th>{{ __('hr_recruitment.applicant_ui.status') }}</th>
                    <th>{{ __($t.'participants') }}</th>
                    <th class="w-16!"><span class="sr-only">{{ __('hr_recruitment.hub.actions') }}</span></th>
                </x-slot:head>

                @foreach ($rows as $interview)
                    @php $start = $zoned($interview, $interview->scheduled_at); @endphp
                    <tr wire:key="interview-{{ $interview->id }}">
                        <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.name')"><span class="font-semibold" dir="auto">{{ $interview->application?->applicant?->full_name ?? '—' }}</span></x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_recruitment.campaign_ui.position')"><span dir="auto">{{ $interview->application?->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.no_vacancy') }}</span></x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__($t.'type')">{{ __($t.'types.'.$interview->interview_type) }}</x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__($t.'when')"><span class="whitespace-nowrap tabular-nums">{{ $start->format('Y-m-d H:i') }}</span></x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__($t.'location')">{{ __($t.'location_types.'.$interview->location_type) }}</x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.status')">
                            <x-ui.enum-badge :case="$interview->status" :label="__($t.'statuses.'.$interview->status)" :tone="Labels::tone($interview->status)" />
                        </x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__($t.'participants')"><span class="tabular-nums">{{ $interview->currentParticipants->count() }}</span></x-ui.data-table.cell>
                        <td>
                            <span class="flex items-center justify-end">
                                <x-ui.button tone="ghost" size="sm" icon="eye" class="size-8!" :href="route('hr.recruitment.interviews.show', ['interviewId' => $interview->id])" wire:navigate :aria-label="__('hr_recruitment.hub.view')" />
                            </span>
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="mt-4">
                <flux:pagination :paginator="$rows" scroll-to="#recruitment-interviews-heading" />
            </div>
        @endif
    @endif
</x-desk.register>
