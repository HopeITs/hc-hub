<?php

use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Core\Models\User;
use Modules\Hr\Http\Controllers\Attendance\AttendanceSignedController;
use Modules\Hr\Models\AttendanceDayDecision;
use Modules\Hr\Models\AttendanceMonth;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\MailList;
use Modules\Hr\Support\Attendance\Grouping;
use Modules\Hr\Support\Attendance\MonthWorkflow;
use Modules\Hr\Support\Attendance\Roster;
use Modules\Hr\Support\Attendance\Timesheet;
use Modules\Hr\Support\Attendance\WorkflowRefused;
use Modules\Hr\Support\Employees\HrMedia;

/*
 * The month from the device report, in two steps: a list of the people (for each
 * city, grouped by project or by department), one line each with one status in
 * plain words, then one person's month as a calendar. A day with no record (or a
 * single punch) is red and answered with two buttons (worked / absent); the month
 * is then confirmed, and HR may lock it and keep the signed paper. Only people the
 * uploaded reports hold punches for in the month are listed, and never a month to
 * come. Who sees whom is Roster; who may do what is MonthWorkflow::abilities().
 */
new #[Layout('layouts::desk'), Title('hr_attendance.titles.timesheets')] class extends Component {
    use WithFileUploads;

    #[Url(as: 'month', history: false)]
    public string $month = '';

    #[Url(as: 'city', history: false)]
    public ?int $cityId = null;

    #[Url(as: 'by', history: false)]
    public string $groupBy = Grouping::PROJECT;

    #[Url(except: '')]
    public string $search = '';

    /** The list's filter: everyone, only those with days waiting, or only the confirmed. */
    #[Url(except: 'all', history: false)]
    public string $tab = 'all';

    /** @var array<string, bool> Groups the viewer folded (true) or opened (false) by hand ("<city>:<group>"). */
    public array $collapsed = [];

    #[Locked]
    public ?int $dayEmployeeId = null;

    #[Locked]
    public ?string $dayDate = null;

    public string $dayKind = AttendanceDayDecision::WORKED;

    public string $dayNote = '';

    /** The person whose month is open. */
    #[Locked]
    public ?int $monthEmployeeId = null;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $signedFile = null;

    public function mount(): void
    {
        abort_unless($this->user()->can('attendance.view-own'), 403);

        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = now()->subDays(10)->format('Y-m');
        }

        $this->clampMonth();

        $this->groupBy = Grouping::normalize($this->groupBy);

        // Someone who sees every city starts on one (their own, else the first) rather than on the
        // whole organisation: the page is quick and useful at once, and "All cities" is one choice
        // away. Everyone else is already limited to their own people or place. A ?city= link wins.
        if ($this->cityId === null && ! request()->has('city')) {
            $this->cityId = app(Roster::class)->defaultCityId($this->user());
        }
    }

    public function shiftMonth(int $delta): void
    {
        $this->month = $this->monthDate()->addMonthsNoOverflow($delta > 0 ? 1 : -1)->format('Y-m');
        $this->clampMonth();
        // An open person stays open: their next or previous month. Without a contract then, the list shows.
        $this->closeDay();
        $this->signedFile = null;
    }

    /** The month picker or the link was changed by hand: still never a month to come. */
    public function updatedMonth(): void
    {
        $this->clampMonth();
        $this->closeDay();
    }

    public function updatedTab(): void
    {
        $this->tab = in_array($this->tab, ['all', 'need', 'done'], true) ? $this->tab : 'all';
    }

    public function updatedGroupBy(): void
    {
        $this->groupBy = Grouping::normalize($this->groupBy);
    }

    /**
     * Groups start folded (see foldedByDefault()), so the toggle says which way it goes.
     */
    public function toggleGroup(string $key, bool $fold): void
    {
        $this->collapsed[$key] = $fold;
    }

    public function openDay(int $employeeId, string $date): void
    {
        $employee = $this->scopedEmployee($employeeId, $date);
        $row = $this->rowFor($employee, $date);

        abort_unless($row !== null && $row['decidable'] && $this->mayDecide($employee, $date), 403);

        $this->resetValidation();
        $this->dayEmployeeId = $employee->id;
        $this->dayDate = $date;
        $this->dayKind = $row['decision'] ?? AttendanceDayDecision::WORKED;
        $this->dayNote = (string) ($row['note'] ?? '');
    }

    public function closeDay(): void
    {
        $this->dayEmployeeId = null;
        $this->dayDate = null;
        $this->dayNote = '';
        $this->resetValidation();
    }

    public function saveDecision(): void
    {
        $this->validate([
            'dayKind' => ['required', 'in:'.AttendanceDayDecision::WORKED.','.AttendanceDayDecision::ABSENT],
            'dayNote' => ['nullable', 'string', 'max:200'],
        ]);

        $employee = $this->scopedEmployee((int) $this->dayEmployeeId, $this->dayDate);
        $row = $this->rowFor($employee, (string) $this->dayDate);

        // Only a day that is waiting, or already decided, is answered here; leave, holidays and weekends never are.
        abort_unless($row !== null && $row['decidable'] && $this->mayDecide($employee, (string) $this->dayDate), 403);

        AttendanceDayDecision::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'work_date' => $this->dayDate],
            ['kind' => $this->dayKind, 'note' => $this->dayNote !== '' ? $this->dayNote : null, 'decided_by' => $this->user()->id],
        );

        app(MonthWorkflow::class)->withdrawApproval($employee, $this->dayMonth((string) $this->dayDate));

        $this->closeDay();
        Flux::toast(variant: 'success', text: __('hr_attendance.timesheets.saved'));
    }

    /**
     * The person's calendar answers a day with one of two buttons. The answer is saved through the
     * same checks as before, then the next day that still waits opens by itself.
     */
    public function answerDay(string $kind): void
    {
        $employeeId = $this->dayEmployeeId;
        $this->dayKind = $kind;
        $this->dayNote = '';
        $this->saveDecision();

        if ($employeeId === null || $this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $employee = Employee::query()->find($employeeId);
        $next = $employee === null ? null : collect(app(Timesheet::class)->forEmployee($employee, $this->monthDate())['rows'])
            ->first(fn (array $row): bool => in_array($row['kind'], [Timesheet::ONE_PUNCH, Timesheet::NO_RECORD], true) && $row['decidable']);

        if ($employee !== null && $next !== null && $this->mayDecide($employee, $next['date']->toDateString())) {
            $this->dayEmployeeId = $employee->id;
            $this->dayDate = $next['date']->toDateString();
            $this->dayKind = AttendanceDayDecision::WORKED;
        }
    }

    public function clearDecision(): void
    {
        $employee = $this->scopedEmployee((int) $this->dayEmployeeId, $this->dayDate);

        abort_unless($this->mayDecide($employee, (string) $this->dayDate), 403);

        AttendanceDayDecision::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', (string) $this->dayDate)
            ->delete();

        app(MonthWorkflow::class)->withdrawApproval($employee, $this->dayMonth((string) $this->dayDate));

        $this->closeDay();
        Flux::toast(variant: 'success', text: __('hr_attendance.timesheets.cleared'));
    }

    public function openMonth(int $employeeId): void
    {
        $this->scopedEmployee($employeeId);

        $this->resetValidation();
        $this->monthEmployeeId = $employeeId;
        $this->signedFile = null;
        $this->closeDay();
    }

    public function closeMonth(): void
    {
        $this->monthEmployeeId = null;
        $this->signedFile = null;
        $this->closeDay();
        $this->resetValidation();
    }

    public function confirmMonth(int $employeeId): void
    {
        $this->step('confirm', $employeeId);
    }

    public function approveMonth(int $employeeId): void
    {
        $this->step('approve', $employeeId);
    }

    public function lockMonth(int $employeeId): void
    {
        $this->step('lock', $employeeId);
    }

    public function reopenMonth(int $employeeId): void
    {
        $this->step('reopen', $employeeId);
    }

    /** Approve every month on this page that is ready and that the viewer may approve. */
    public function approveAllReady(): void
    {
        $this->bulk('approve');
    }

    /** Lock every approved month on this page that the viewer may lock. */
    public function lockAllApproved(): void
    {
        $this->bulk('lock');
    }

    public function saveSigned(): void
    {
        $this->validate(['signedFile' => ['required', 'file', 'max:10240', 'extensions:'.implode(',', AttendanceSignedController::ALLOWED_EXTENSIONS)]]);

        $employee = $this->scopedEmployee((int) $this->monthEmployeeId);
        $record = AttendanceMonth::for($employee, $this->monthDate());

        abort_unless($this->abilitiesFor($employee, $record)['signed'], 403);

        if (! $record->exists) {
            $record->save();
        }

        HrMedia::clear(HrMedia::ATTENDANCE_MONTH, (int) $record->id, AttendanceSignedController::COLLECTION);
        HrMedia::store($this->signedFile, HrMedia::ATTENDANCE_MONTH, (int) $record->id, AttendanceSignedController::COLLECTION);

        $this->signedFile = null;
        Flux::toast(variant: 'success', text: __('hr_attendance.workflow.signed-saved'));
    }

    public function removeSigned(): void
    {
        $employee = $this->scopedEmployee((int) $this->monthEmployeeId);
        $record = AttendanceMonth::for($employee, $this->monthDate());

        abort_unless($record->exists && $this->abilitiesFor($employee, $record)['signed'], 403);

        HrMedia::clear(HrMedia::ATTENDANCE_MONTH, (int) $record->id, AttendanceSignedController::COLLECTION);
        Flux::toast(variant: 'success', text: __('hr_attendance.workflow.signed-removed'));
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $month = $this->monthDate();
        $by = Grouping::normalize($this->groupBy);
        $roster = app(Roster::class);
        $user = $this->user();
        $mode = $roster->mode($user);
        $items = $this->items($month);

        // The figures above the groups describe the whole selection, not just what is unfolded.
        $summary = [
            'people' => $items->count(),
            'to_decide' => (int) $items->sum(fn (array $item): int => $item['sheet']['totals']['to_decide']),
            'overtime_hours' => round((float) $items->sum(fn (array $item): float => $item['sheet']['totals']['overtime_hours']), 2),
            'overtime_days' => round((float) $items->sum(fn (array $item): float => $item['sheet']['totals']['overtime_days']), 2),
            'ready_to_approve' => $items->filter(fn (array $item): bool => $item['can']['approve'])->count(),
            'ready_to_lock' => $items->filter(fn (array $item): bool => $item['can']['lock'])->count(),
        ];

        $panel = $this->monthEmployeeId === null
            ? null
            : $items->first(fn (array $item): bool => $item['employee']->id === $this->monthEmployeeId);

        // Someone who sees only their own month has no list before it: it opens straight away.
        if ($panel === null && $mode === Roster::OWN) {
            $panel = $items->first();
        }

        $counts = [
            'all' => $items->count(),
            'need' => $items->filter(fn (array $item): bool => $this->listStatus($item) === 'need')->count(),
            'done' => $items->filter(fn (array $item): bool => $this->listStatus($item) === 'done')->count(),
        ];

        $tab = in_array($this->tab, ['need', 'done'], true) ? $this->tab : 'all';
        $listed = $tab === 'all' ? $items : $items->filter(fn (array $item): bool => $this->listStatus($item) === $tab)->values();

        $openDay = null;

        if ($this->dayEmployeeId !== null && $this->dayDate !== null) {
            $employee = Employee::query()->find($this->dayEmployeeId);
            $openDay = $employee === null ? null : ['employee' => $employee, 'row' => $this->rowFor($employee, $this->dayDate)];
        }

        $blocks = app(Grouping::class)->group($listed, $by);

        return [
            'blocks' => $blocks,
            // A long page opens as a short list of groups; one group, a search or the
            // days-to-decide filter opens them all, so a match is never hidden.
            'searching' => trim($this->search) !== '' || $tab !== 'all',
            'foldedByDefault' => trim($this->search) === '' && $tab === 'all'
                && collect($blocks)->sum(fn (array $block): int => count($block['groups'])) > 1,
            'summary' => $summary,
            'by' => $by,
            'days' => collect(range(1, $month->daysInMonth))->map(fn (int $day): array => [
                'n' => $day,
                'w' => $month->day($day)->locale(app()->getLocale())->isoFormat('dd'),
            ]),
            'cities' => $roster->cities($user),
            'mode' => $mode,
            'tab' => $tab,
            'counts' => $counts,
            // Printing waits until every day is answered and every leave request of the month is.
            'printBlocked' => $items->contains(fn (array $item): bool => $item['sheet']['totals']['to_decide'] > 0 || $item['sheet']['totals']['leave_pending'] > 0),
            'monthDate' => $month,
            'openDay' => $openDay,
            'panel' => $panel,
            'panelSigned' => $panel !== null && $panel['record']->exists
                ? HrMedia::for(HrMedia::ATTENDANCE_MONTH, (int) $panel['record']->id, AttendanceSignedController::COLLECTION)->last()
                : null,
            'canManage' => $user->can('attendance.manage'),
            // The mailing lists that stand for a city, project or department: "Email this group".
            'mailLists' => MailList::customRuled(),
        ];
    }

    /**
     * Everyone the viewer may see this month, with the month's record and what the viewer may do
     * with it. The roster is already scoped to the viewer, so every person is covered.
     *
     * @return Collection<int, array{employee: Employee, sheet: array<string, mixed>, record: AttendanceMonth, state: string, can: array<string, bool>}>
     */
    private function items(CarbonImmutable $month): Collection
    {
        $user = $this->user();
        $workflow = app(MonthWorkflow::class);
        // Only people the uploaded reports hold punches for in this month; the print follows the same rule.
        $roster = app(Roster::class);
        $employees = $roster->withPunches($roster->employees($user, $month, $this->cityId), $month)
            ->when(trim($this->search) !== '', fn (Collection $all): Collection => $all->filter(fn (Employee $employee): bool => $this->matchesSearch($employee)));
        // One batch of reads for everyone on the page, not a set per person.
        $sheets = app(Timesheet::class)->forEmployees($employees, $month);

        $records = AttendanceMonth::query()
            ->with('approver', 'locker')
            ->whereIn('employee_id', $employees->modelKeys())
            ->whereDate('month', $month->toDateString())
            ->get()
            ->keyBy('employee_id');

        return $employees->map(function (Employee $employee) use ($sheets, $workflow, $records, $month, $user): array {
            $sheet = $sheets[$employee->id];
            // No record yet is a blank one, not a query per person.
            $record = $records[$employee->id] ?? new AttendanceMonth(['employee_id' => $employee->id, 'month' => $month->toDateString()]);

            return [
                'employee' => $employee,
                'sheet' => $sheet,
                'record' => $record,
                'state' => $record->state(),
                'can' => $workflow->abilities($user, $employee, $month, $record, $sheet['totals']['to_decide'], true),
            ];
        })->values();
    }

    /**
     * One status per person in plain words: days waiting for an answer, confirmed (approved or
     * locked), or still on its way.
     *
     * @param  array<string, mixed>  $item
     */
    private function listStatus(array $item): string
    {
        return match (true) {
            $item['sheet']['totals']['to_decide'] > 0 => 'need',
            in_array($item['state'], ['approved', 'locked'], true) => 'done',
            default => 'other',
        };
    }

    /** There are no punches in the future, so the page never goes past the current month. */
    private function clampMonth(): void
    {
        $current = now()->format('Y-m');

        if (! preg_match('/^\d{4}-\d{2}$/', $this->month) || $this->month > $current) {
            $this->month = $current;
        }
    }

    private function step(string $step, int $employeeId): void
    {
        $employee = $this->scopedEmployee($employeeId);

        try {
            app(MonthWorkflow::class)->{$step}($this->user(), $employee, $this->monthDate());
            Flux::toast(variant: 'success', text: __('hr_attendance.workflow.done.'.$step));
        } catch (WorkflowRefused $refused) {
            Flux::toast(variant: 'danger', text: __($refused->getMessage()));
        }
    }

    private function bulk(string $step): void
    {
        $workflow = app(MonthWorkflow::class);
        $done = 0;

        foreach ($this->items($this->monthDate()) as $item) {
            if (! $item['can'][$step]) {
                continue;
            }

            try {
                $workflow->{$step}($this->user(), $item['employee'], $this->monthDate());
                $done++;
            } catch (WorkflowRefused) {
                // One that cannot go through now does not stop the others.
            }
        }

        Flux::toast(variant: 'success', text: trans_choice('hr_attendance.workflow.bulk-'.$step, $done, ['count' => $done]));
    }

    /** @return array{decide: bool, confirm: bool, approve: bool, lock: bool, reopen: bool, signed: bool} */
    private function abilitiesFor(Employee $employee, AttendanceMonth $record): array
    {
        $month = $this->monthDate();
        $toDecide = app(Timesheet::class)->forEmployee($employee, $month)['totals']['to_decide'];

        return app(MonthWorkflow::class)->abilities($this->user(), $employee, $month, $record, $toDecide);
    }

    private function mayDecide(Employee $employee, string $date): bool
    {
        $month = $this->dayMonth($date);

        return app(MonthWorkflow::class)
            ->abilities($this->user(), $employee, $month, AttendanceMonth::for($employee, $month), 1)['decide'];
    }

    private function matchesSearch(Employee $employee): bool
    {
        $needle = mb_strtolower(trim($this->search));

        foreach (['en', 'ar'] as $locale) {
            if (str_contains(mb_strtolower($employee->nameForLocale($locale)), $needle)) {
                return true;
            }
        }

        return $needle === (string) $employee->id;
    }

    private function monthDate(): CarbonImmutable
    {
        $parsed = CarbonImmutable::createFromFormat('!Y-m', $this->month);

        return $parsed instanceof CarbonImmutable ? $parsed : CarbonImmutable::now()->startOfMonth();
    }

    private function dayMonth(string $date): CarbonImmutable
    {
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date);

        return $day instanceof CarbonImmutable ? $day->startOfMonth() : $this->monthDate();
    }

    private function scopedEmployee(int $id, ?string $date = null): Employee
    {
        $employee = Employee::query()->findOrFail($id);
        $month = $date !== null ? $this->dayMonth($date) : $this->monthDate();

        abort_unless(app(Roster::class)->covers($this->user(), $employee, $month), 403);

        return $employee;
    }

    /** @return array<string, mixed>|null */
    private function rowFor(Employee $employee, string $date): ?array
    {
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date);

        if (! $day instanceof CarbonImmutable) {
            return null;
        }

        $sheet = app(Timesheet::class)->forEmployee($employee, $day->startOfMonth());

        return collect($sheet['rows'])->first(fn (array $row): bool => $row['date']->toDateString() === $date);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
};
?>

@php
    $own = $mode === 'own';
    $title = match ($mode) {
        'own' => __('hr_attendance.timesheets.title-own'),
        'team' => __('hr_attendance.timesheets.title-team'),
        default => __('hr_attendance.timesheets.title'),
    };
    $printBase = array_filter(['month' => $month, 'city_id' => $cityId, 'group_by' => $by]);
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<x-desk.register class="@container" heading-id="hr-attendance-heading" :title="$title" :count="$summary['people']" data-hr-attendance>
    <x-slot:actions>
        @if ($canManage)
            @if (config('attendance.devices_page'))
                <x-ui.button tone="action" icon="signal" :href="route('hr.attendance.devices')" wire:navigate data-test="attendance-devices-link">{{ __('hr_attendance.devices.title') }}</x-ui.button>
            @endif
            <x-ui.button tone="action" icon="arrow-up-tray" :href="route('hr.attendance.upload')" wire:navigate data-test="attendance-upload-link">{{ __('hr_attendance.timesheets.upload') }}</x-ui.button>
        @endif
        {{-- The paper is signed: it prints only when every day is answered and no leave request waits. --}}
        @if ($printBlocked)
            <x-ui.button tone="action" icon="printer" disabled title="{{ __('hr_attendance.timesheets.print-blocked') }}" data-test="attendance-print-blocked">{{ $own ? __('hr_attendance.timesheets.print-own') : __('hr_attendance.timesheets.print-all') }}</x-ui.button>
        @else
            <x-ui.button tone="action" icon="printer" :href="route('hr.attendance.print', $printBase)" target="_blank" data-test="attendance-print-link">{{ $own ? __('hr_attendance.timesheets.print-own') : __('hr_attendance.timesheets.print-all') }}</x-ui.button>
        @endif
    </x-slot:actions>

    @unless ($own || $panel)
        <x-slot:search>
            <x-desk.register-search :label="__('hr_attendance.timesheets.search')" :placeholder="__('hr_attendance.timesheets.search')" wire:model.live.debounce.300ms="search" />
        </x-slot:search>
    @endunless

    <x-slot:filters>
        <div class="flex items-center gap-1" role="group" aria-label="{{ __('hr_attendance.timesheets.month') }}">
            <x-ui.button tone="ghost" size="sm" icon="chevron-left" class="rtl:rotate-180" wire:click="shiftMonth(-1)" :aria-label="__('hr_attendance.timesheets.prev-month')" />
            <flux:input type="month" size="sm" wire:model.live="month" :max="now()->format('Y-m')" :aria-label="__('hr_attendance.timesheets.month')" class="w-40!" data-test="attendance-month" />
            <x-ui.button tone="ghost" size="sm" icon="chevron-right" class="rtl:rotate-180" wire:click="shiftMonth(1)" :disabled="$month >= now()->format('Y-m')" :aria-label="__('hr_attendance.timesheets.next-month')" />
        </div>
        @unless ($panel)
            @if ($cities->count() > 1)
                <x-ui.header-filter-select :label="__('hr_attendance.timesheets.city')" wire:model.live="cityId" :value="$cityId">
                    <option value="">{{ __('hr_attendance.timesheets.all-cities') }}</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                    @endforeach
                </x-ui.header-filter-select>
            @endif
            @unless ($own)
                <x-ui.header-filter-select :label="__('hr_attendance.timesheets.group-by')" wire:model.live="groupBy" :value="$by">
                    <option value="{{ Grouping::PROJECT }}">{{ __('hr_attendance.timesheets.by-project') }}</option>
                    <option value="{{ Grouping::DEPARTMENT }}">{{ __('hr_attendance.timesheets.by-department') }}</option>
                </x-ui.header-filter-select>
            @endunless
        @endunless
    </x-slot:filters>

    @if ($panel)
        @include('hr::pages.attendance._timesheets-person')
    @else
        @include('hr::pages.attendance._timesheets-list')
    @endif
</x-desk.register>
