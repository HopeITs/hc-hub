<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Livewire\Concerns\Leave\ManagesHolidayForm;
use Modules\Hr\Livewire\Concerns\Leave\ReadsTeamCalendar;
use Modules\Hr\Support\Leave\HolidayProjects;
use Modules\Hr\Support\Leave\TeamCoverage;

/*
 * The team calendar (C5; team-coverage-contract.md). Every absence and count
 * comes from TeamCoverage, the reader the leave form's hint and the deciding
 * manager's warning also use (AC-C5-01).
 *
 * Only scalars are public: the absences are computed per request and never
 * kept in the component's state, so no request id reaches a colleague-level
 * browser through the snapshot (§4.4). The URL parameters are client input:
 * each is checked against what the viewer may use, and an unknown or
 * forbidden value falls back to the default.
 */
new #[Layout('layouts::desk'), Title('hr_leave_calendar.titles.team_calendar')] class extends Component {
    use ManagesHolidayForm;
    use ReadsTeamCalendar;

    #[Url(except: 'month')]
    public string $view = 'month';

    #[Url(except: '')]
    public string $date = '';

    #[Url(except: '')]
    public string $scope = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(as: 'city', except: '')]
    public string $cityId = '';

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /** Previous (-1) or next (1) month, week or day, by the current view. */
    public function move(int $step): void
    {
        $step = $step < 0 ? -1 : 1;
        $anchor = $this->anchor();

        $next = match ($this->calendarView()) {
            'week' => $anchor->addWeeks($step),
            'day' => $anchor->addDays($step),
            default => $anchor->startOfMonth()->addMonthsNoOverflow($step),
        };

        $this->date = $next->toDateString();

        unset($this->calendar);
    }

    /** From a month cell to that day's list. */
    public function openDay(string $date): void
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $this->date = $date;
            $this->view = 'day';
            unset($this->calendar);
        }
    }

    public function goToday(): void
    {
        $this->date = '';
        unset($this->calendar);
    }

    protected function holidaysChanged(): void
    {
        unset($this->calendar);
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function calendar(): array
    {
        $user = $this->user();
        $coverage = app(TeamCoverage::class);

        $scopes = $coverage->scopesFor($user);
        $scope = in_array($this->scope, $scopes, true) ? $this->scope : $coverage->defaultScope($user);
        $managerView = $coverage->maxLevel($user) >= TeamCoverage::LEVEL_MANAGER;
        $status = $managerView && in_array($this->status, self::CALENDAR_STATUSES, true) ? $this->status : 'all';
        $cities = $scope === 'org' ? City::options(false) : [];
        $cityId = array_key_exists((int) $this->cityId, $cities) ? (int) $this->cityId : null;
        $view = $this->calendarView();
        [$from, $to] = $this->range($view);

        $failed = false;
        $birthdays = [];

        try {
            $days = $coverage->coverage($user, $scope, $from, $to, ['status' => $status, 'city_id' => $cityId]);
            $birthdays = $coverage->birthdays($user, $scope, $from, $to, $cityId);
        } catch (Throwable $exception) {
            report($exception);
            $days = [];
            $failed = true;
        }

        $projects = app(HolidayProjects::class);
        $canManageHolidays = $projects->canManage($user);

        try {
            $holidays = $this->holidayLabels($user, $from, $to, $projects, $canManageHolidays);
        } catch (Throwable $exception) {
            report($exception);
            $holidays = [];
        }

        return [
            'days' => $days,
            'birthdays' => $birthdays,
            'holidays' => $holidays,
            'failed' => $failed,
            'from' => $from,
            'to' => $to,
            'view' => $view,
            'scopes' => $scopes,
            'scope' => $scope,
            'managerView' => $managerView,
            'status' => $status,
            'cities' => $cities,
            'cityId' => $cityId,
            'canManageHolidays' => $canManageHolidays,
            // Expected head-counts mean something only for a whole team.
            'showCounts' => in_array($scope, ['reports', 'city', 'org'], true),
            'hasAbsences' => collect($days)->contains(fn (array $day): bool => $day['members'] !== []),
            // The leave page, when the leave area has it: a day from today on opens the form on that date.
            'requestRoute' => Route::has('hr.leave.index') && $user->can('leaves.create') ? 'hr.leave.index' : null,
        ];
    }
};
?>

@php
    $calendar = $this->calendar;
    $viewName = $calendar['view'];
    $from = $calendar['from'];
    $to = $calendar['to'];
    $anchor = $this->anchor();
    $today = \Carbon\CarbonImmutable::today()->toDateString();
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $periodLabel = match ($viewName) {
        'day' => $anchor->translatedFormat('l j F Y'),
        'week' => __('hr_leave_calendar.range', ['from' => $from->translatedFormat('j M'), 'to' => $to->translatedFormat('j M Y')]),
        default => $anchor->translatedFormat('F Y'),
    };
    $viewOptions = collect($this::CALENDAR_VIEWS)->mapWithKeys(fn (string $key): array => [$key => ['icon' => match ($key) {
        'month' => 'cal',
        'week' => 'board',
        'day' => 'list',
        default => 'table',
    }, 'label' => __('hr_leave_calendar.views.'.$key)]])->all();
@endphp

<x-desk.register heading-id="hr-team-calendar-heading" :title="__('hr_leave_calendar.titles.team_calendar')" :views="$viewOptions" :view="$viewName" view-model="view" data-hr-team-calendar>
    <x-slot:figures>{{ __('hr_leave_calendar.intro') }}</x-slot:figures>

    <x-slot:actions>
        @if ($calendar['canManageHolidays'])
            <x-ui.button tone="secondary" icon="plus" wire:click="openCreateHoliday" data-test="calendar-add-holiday">{{ __('hr_leave_calendar.add_holiday') }}</x-ui.button>
        @endif
        @if ($calendar['requestRoute'] !== null)
            <x-ui.button tone="primary" icon="plus" :href="route($calendar['requestRoute'], ['new' => 1])" wire:navigate>{{ __('hr_leave_calendar.request_leave') }}</x-ui.button>
        @endif
    </x-slot:actions>

    <x-slot:filters>
        @if (count($calendar['scopes']) > 1)
            <x-ui.header-filter-select :label="__('hr_leave_calendar.scopes.label')" wire:model.live="scope" :value="$calendar['scope']">
                @foreach ($calendar['scopes'] as $scopeOption)
                    <option value="{{ $scopeOption }}">{{ __('hr_leave_calendar.scopes.'.$scopeOption) }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif

        @if ($calendar['managerView'])
            <x-ui.header-filter-select :label="__('hr_leave_calendar.status.label')" wire:model.live="status" :value="$calendar['status']">
                @foreach ($this::CALENDAR_STATUSES as $statusOption)
                    <option value="{{ $statusOption }}">{{ __('hr_leave_calendar.status.'.$statusOption) }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif

        @if ($calendar['cities'] !== [])
            <x-ui.header-filter-select :label="__('hr_leave_calendar.city.label')" wire:model.live="cityId" :value="(string) ($calendar['cityId'] ?? '')">
                <option value="">{{ __('hr_leave_calendar.city.all') }}</option>
                @foreach ($calendar['cities'] as $cityOptionId => $cityOptionName)
                    <option value="{{ $cityOptionId }}">{{ $cityOptionName }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
    </x-slot:filters>

    <div class="mb-3 flex flex-wrap items-center gap-2" data-calendar-toolbar>
        <x-ui.button tone="ghost" size="sm" icon="chevron-left" class="size-9! rtl:rotate-180" wire:click="move(-1)" :aria-label="__('hr_leave_calendar.nav_buttons.previous')" />
        <x-ui.button tone="secondary" size="sm" wire:click="goToday">{{ __('hr_leave_calendar.nav_buttons.today') }}</x-ui.button>
        <x-ui.button tone="ghost" size="sm" icon="chevron-right" class="size-9! rtl:rotate-180" wire:click="move(1)" :aria-label="__('hr_leave_calendar.nav_buttons.next')" />
        <h2 class="ms-1 text-base font-bold text-secondary-dark dark:text-ink" aria-live="polite">{{ $periodLabel }}</h2>
        <span wire:loading.delay.flex class="hidden items-center gap-1.5 text-xs {{ $muted }}">
            <flux:icon.arrow-path class="size-3.5 animate-spin" />{{ __('common.updating') }}
        </span>
    </div>

    @if ($calendar['failed'])
        <x-ui.empty-state icon="exclamation-triangle" :heading="__('hr_leave_calendar.failed')" />
    @elseif ($viewName === 'month')
        @include('hr::pages.leave._calendar-month')
    @else
        @include('hr::pages.leave._calendar-list')
    @endif

    <p class="mt-3 type-meta {{ $muted }}">{{ __('hr_leave_calendar.legend') }}</p>

    @if ($calendar['canManageHolidays'])
        @include('hr::pages.leave._holidays-modal')
    @endif
</x-desk.register>
