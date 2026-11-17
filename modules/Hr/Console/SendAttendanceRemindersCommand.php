<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Notifications\AppNotification;
use App\Notifications\NotificationPayload;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\AttendanceMonth;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Attendance\Roster;
use Modules\Hr\Support\Attendance\Timesheet;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Throwable;

/**
 * Each morning of the first days of a month, nudges the people last month is waiting for: a
 * manager with timesheets to approve (or days to answer), HR of the city with approved months to
 * lock. Once everything is locked nobody is nudged. The ledger keeps it to one nudge a day per
 * person. Run by hand for another month with --month=YYYY-MM.
 *
 * The sentences are hr_attendance.notifications.reminder_{approve,lock} and open the timesheets.
 */
class SendAttendanceRemindersCommand extends Command
{
    public const LEDGER_APPROVE = 'attendance-approve';

    public const LEDGER_LOCK = 'attendance-lock';

    /** The month is nudged about only this many days into the next one. */
    private const DAYS = 10;

    protected $signature = 'attendance:send-reminders {--month= : The month to nudge about (YYYY-MM); default is last month}';

    protected $description = 'Remind managers to approve and HR to lock last month\'s attendance timesheets';

    public function handle(DeliveryLedger $ledger, Timesheet $timesheet, Roster $roster): int
    {
        $explicit = $this->option('month');
        $month = $explicit
            ? CarbonImmutable::createFromFormat('!Y-m', (string) $explicit)
            : CarbonImmutable::now()->startOfMonth()->subMonthNoOverflow();

        if (! $month instanceof CarbonImmutable) {
            $this->components->error('Use --month=YYYY-MM.');

            return self::FAILURE;
        }

        if (! $explicit && now()->day > self::DAYS) {
            $this->components->info('Past the first days of the month: nothing to do.');

            return self::SUCCESS;
        }

        $day = $ledger->today();
        $label = $month->format('Y-m');
        $people = $this->people($month, $timesheet, $roster);
        $sent = 0;

        // Managers: ready to approve, and days that wait for an answer.
        foreach ($people->where('manager_user_id', '!=', null)->groupBy('manager_user_id') as $managerUserId => $group) {
            $ready = $group->where('ready_to_approve', true)->count();
            $waiting = (int) $group->sum('to_decide');

            if ($ready === 0 && $waiting === 0) {
                continue;
            }

            $user = User::query()->find((int) $managerUserId);

            if ($user !== null && $this->send($ledger, self::LEDGER_APPROVE, $user, $group->pluck('employee_id')->all(), $day, $this->payload('approve', $label, $ready, $waiting))) {
                $sent++;
            }
        }

        // HR of each city: approved, waiting to be locked.
        foreach ($people->where('approved', true)->groupBy('city_slug') as $slug => $group) {
            foreach ($this->hrUsers((string) $slug) as $user) {
                if ($this->send($ledger, self::LEDGER_LOCK, $user, $group->pluck('employee_id')->all(), $day, $this->payload('lock', $label, $group->count()))) {
                    $sent++;
                }
            }
        }

        $this->components->info("Sent {$sent} attendance reminder(s) for {$label}.");

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $employeeIds
     */
    private function send(DeliveryLedger $ledger, string $kind, User $user, array $employeeIds, CarbonInterface $day, NotificationPayload $payload): bool
    {
        if ($ledger->claim($kind, (int) $user->id, $employeeIds, $day) === []) {
            return false;
        }

        try {
            $user->notify(new AppNotification($payload));
            $ledger->markDelivered($kind, (int) $user->id, $employeeIds, $day);

            return true;
        } catch (Throwable $e) {
            $ledger->release($kind, (int) $user->id, $employeeIds, $day);
            report($e);

            return false;
        }
    }

    /**
     * Everyone with a contract in the month, with where their month stands.
     *
     * @return Collection<int, array{employee_id: int, city_slug: string, manager_user_id: ?int, to_decide: int, ready_to_approve: bool, approved: bool}>
     */
    private function people(CarbonImmutable $month, Timesheet $timesheet, Roster $roster): Collection
    {
        $employees = Employee::query()
            ->with([
                'city',
                'allContracts' => fn ($contract) => $roster->overlapping($contract, $month)->with('position.directManager.activeContracts.employee.account'),
            ])
            ->whereHas('allContracts', fn (Builder $contract): Builder => $roster->overlapping($contract, $month))
            ->where('is_active', true)
            ->get();

        $records = AttendanceMonth::query()
            ->whereDate('month', $month->toDateString())
            ->get()
            ->keyBy('employee_id');

        return $employees->map(function (Employee $employee) use ($month, $timesheet, $records): array {
            $state = ($records[$employee->id] ?? null)?->state() ?? AttendanceMonth::OPEN;
            $toDecide = in_array($state, [AttendanceMonth::LOCKED, AttendanceMonth::APPROVED], true)
                ? 0
                : $timesheet->forEmployee($employee, $month)['totals']['to_decide'];
            $contract = $employee->allContracts->sortByDesc('start_date')->first();
            $managerId = $contract?->position?->directManager?->employee()?->account?->id;

            return [
                'employee_id' => (int) $employee->id,
                'city_slug' => strtoupper(trim((string) $employee->city?->slug)),
                'manager_user_id' => $managerId !== null ? (int) $managerId : null,
                'to_decide' => $toDecide,
                'ready_to_approve' => in_array($state, [AttendanceMonth::OPEN, AttendanceMonth::CONFIRMED], true) && $toDecide === 0,
                'approved' => $state === AttendanceMonth::APPROVED,
            ];
        })->values();
    }

    /**
     * HR of a city: its own HR role (or the roles config maps to it) and HR Syria.
     *
     * @return Collection<int, User>
     */
    private function hrUsers(string $slug): Collection
    {
        if ($slug === '') {
            return collect();
        }

        $roles = collect(config('access-control.hr_role_cities', []))
            ->filter(fn (mixed $slugs): bool => is_array($slugs) && in_array($slug, $slugs, true))
            ->keys()
            ->map(fn (mixed $name): string => (string) $name)
            ->push('HR-'.$slug, 'HR-'.City::NATIONAL_SLUG)
            ->unique()
            ->all();

        return User::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', $roles))
            ->whereHas('employee', fn (Builder $query): Builder => $query->where('is_active', true))
            ->get();
    }

    private function payload(string $kind, string $month, int $count, int $toDecide = 0): NotificationPayload
    {
        return new NotificationPayload(
            kind: NotificationKind::Action,
            module: 'attendance',
            priority: NotificationPriority::Important,
            key: 'hr_attendance.notifications.reminder_'.$kind.($kind === 'approve' && $toDecide > 0 ? '_with_days' : ''),
            params: ['month' => $month, 'count' => (string) $count, 'days' => (string) $toDecide],
            subjectType: null,
            subjectId: null,
            link: ['route' => 'hr.attendance.timesheets', 'parameters' => ['month' => $month]],
        );
    }
}
