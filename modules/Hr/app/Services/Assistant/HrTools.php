<?php

namespace Modules\Hr\Services\Assistant;

use App\Models\City;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\Position;
use Modules\Hr\Services\LeaveDecisionAuthority;
use Modules\Hr\Services\LeaveRequestNextStep;

/**
 * The read-only functions the assistant may call about OTHER people's
 * records, for HR staff and managers.
 *
 * Every tool is gated by the permission of the page that already shows the
 * same data, and scoped by that page's own query (the same model scopes, so
 * the two cannot drift): the Employees table (Employee::inActiveCities), the
 * Contracts table (Contract::visibleTo + inView), the Team leaves page
 * (LeaveRequest::manageableBy + exceptFormerStaffPending) and the employee
 * statistics (positions in the role's cities). An id the model passes is
 * looked up through the same scope again, never trusted.
 *
 * Nothing here ever reads pay: no salary, pay tier, currency or period. The
 * assistant never reads a pay figure at all, not even the asking employee's
 * own: pay never leaves the hub. A tool the user may not use is not declared
 * at all, and call() refuses it again in case the model names it.
 */
class HrTools
{
    public const TOOLS = [
        'find_employees',
        'get_employee_summary',
        'get_leave_requests_to_review',
        'get_expiring_contracts',
        'get_team_headcount',
    ];

    private const DESCRIPTIONS = [
        'find_employees' => 'HR/manager tool. Finds employees this user may see (the same people as on the Employees page) by a part of the name in English or Arabic, or by employee id or username: id, name, position, department, center, city and status. Never pay.',
        'get_employee_summary' => 'HR/manager tool. One employee this user may see (by id from find_employees): status, contracts (position, type, start, end, weekly hours, status), leave balances per type (entitled, used, remaining) and the 10 most recent leave requests. Never pay: salary is not available to the assistant for other people.',
        'get_leave_requests_to_review' => 'HR/manager tool. The leave requests this user sees on the Team leaves page (their city for HR, their direct reports for a manager): employee, leave type, dates, duration, status, where it stands and whether this user can decide it now.',
        'get_expiring_contracts' => 'HR/manager tool. Running contracts ending within N days among those this user may see on the Contracts page: employee, position, center, start and end dates, days left, contract type and whether it renews automatically.',
        'get_team_headcount' => 'HR tool. How many employees currently hold a contract, per center and per department, within the cities this user may see. Counts only.',
    ];

    private const PARAMETERS = [
        'find_employees' => [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'A part of the first or last name, in English or Arabic (several words narrow the match), or an employee id or username.'],
                'limit' => ['type' => 'integer', 'description' => 'How many to return at most, 1 to 20. Default 10.'],
            ],
            'required' => ['query'],
        ],
        'get_employee_summary' => [
            'type' => 'object',
            'properties' => [
                'employee_id' => ['type' => 'integer', 'description' => 'The employee id, from find_employees.'],
            ],
            'required' => ['employee_id'],
        ],
        'get_leave_requests_to_review' => [
            'type' => 'object',
            'properties' => [
                'status' => ['type' => 'string', 'enum' => ['pending', 'all'], 'description' => 'pending for the requests still waiting for a decision (default), all for decided ones too.'],
                'limit' => ['type' => 'integer', 'description' => 'How many to return at most, 1 to 30, most recent first. Default 15.'],
            ],
        ],
        'get_expiring_contracts' => [
            'type' => 'object',
            'properties' => [
                'days' => ['type' => 'integer', 'description' => 'Contracts ending within this many days from today, 1 to 366. Default 60.'],
            ],
        ],
    ];

    private const DEFAULT_LIMITS = ['find_employees' => 10, 'get_leave_requests_to_review' => 15];

    private const MAX_LIMITS = ['find_employees' => 20, 'get_leave_requests_to_review' => 30];

    private const MAX_EXPIRING = 50;

    /**
     * @return list<array{name: string, description: string, parameters?: array<string, mixed>}>
     */
    public function declarations(User $user): array
    {
        return array_map(
            fn (string $name) => ['name' => $name, 'description' => self::DESCRIPTIONS[$name]]
                + (isset(self::PARAMETERS[$name]) ? ['parameters' => self::PARAMETERS[$name]] : []),
            $this->available($user),
        );
    }

    /**
     * @param  array<string, mixed>  $args  the model's arguments, untrusted
     * @return array<string, mixed>
     */
    public function call(User $user, string $name, array $args = []): array
    {
        if (! in_array($name, $this->available($user), true)) {
            return ['error' => "The tool {$name} is not available to this user."];
        }

        return match ($name) {
            'find_employees' => $this->findEmployees($user, $args),
            'get_employee_summary' => $this->employeeSummary($user, $args),
            'get_leave_requests_to_review' => $this->leaveRequestsToReview($user, $args),
            'get_expiring_contracts' => $this->expiringContracts($user, $args),
            'get_team_headcount' => $this->teamHeadcount($user),
            default => ['error' => "The tool {$name} is not available to this user."],
        };
    }

    /**
     * Each tool behind the permission of the page that shows the same data:
     * the Employees route (employees.view), Team leaves (leaves.view-all), the
     * Contracts route (contracts.view) and Employee statistics (statistics.view).
     *
     * @return list<string>
     */
    private function available(User $user): array
    {
        $employees = $user->hasActivePermission('hr.employees.view');

        return array_values(array_filter([
            $employees ? 'find_employees' : null,
            $employees ? 'get_employee_summary' : null,
            $user->hasActivePermission('hr.leaves.view-all') ? 'get_leave_requests_to_review' : null,
            $user->hasActivePermission('hr.contracts.view') ? 'get_expiring_contracts' : null,
            $user->hasActivePermission('hr.statistics.view') ? 'get_team_headcount' : null,
        ]));
    }

    /**
     * The Employees table's own population (EmployeeTable::query): the active
     * role's cities, active staff for everyone, former staff only for HR, the
     * general manager and super-admin, and HR's drafts only for HR and super-admin.
     *
     * @return Builder<Employee>
     */
    private function visibleEmployees(User $user): Builder
    {
        $seesInactive = $user->activeRoleIs('HR', 'general-manager', 'super-admin');
        $seesDrafts = $user->activeRoleIs('HR', 'super-admin');

        return Employee::query()
            ->inActiveCities($user)
            ->when(! $seesInactive, fn (Builder $q) => $q->where('employees.is_active', true))
            ->when(! $seesDrafts, fn (Builder $q) => $q->completeRecords());
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function findEmployees(User $user, array $args): array
    {
        $search = trim((string) ($args['query'] ?? ''));
        $limit = $this->limit($args, 'find_employees');

        if ($search === '') {
            return ['error' => 'Give a part of the name, or an employee id or username.'];
        }

        $locale = app()->getLocale();
        $query = $this->visibleEmployees($user)
            ->with('city')
            ->withExists(['realContracts as has_real_contract']);

        if (ctype_digit($search)) {
            $query->where(fn (Builder $q) => $q->where('employees.id', (int) $search)->orWhere('employees.username', $search));
        } else {
            // Every word must match a first or last name in either language (EmployeeTable's name column).
            $parts = array_filter(preg_split('/\s+/u', $search) ?: []);

            foreach ($parts as $part) {
                $query->whereLikeAny(['first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar', 'username'], $part);
            }
        }

        $employees = $query
            ->orderBy(transColumn('first_name'))
            ->orderBy(transColumn('last_name'))
            ->limit($limit + 1)
            ->get();

        // Each one's running contracts and their positions, as primaryContract() reads them.
        Related::loadMany($employees, 'contracts', 'employee_id', Contract::query()->activeNow());
        $this->loadPositions($employees->flatMap(fn (Employee $employee) => Related::many($employee, 'contracts', Contract::class)));

        return [
            'employees' => $employees->take($limit)->map(function (Employee $employee) use ($locale) {
                $place = Related::place(Related::one($employee->primaryContract(), 'position', Position::class));

                return array_filter([
                    'id' => $employee->id,
                    'name' => $employee->nameForLocale($locale),
                    'username' => $employee->username,
                    'position' => $place['position'],
                    'department' => $place['department'],
                    'center' => $place['center'],
                    'city' => $place['city'] ?? Related::text(Related::one($employee, 'city', City::class)),
                    'status' => $this->employeeStatus($employee),
                ], fn ($value) => filled($value));
            })->values()->all(),
            'truncated' => $employees->count() > $limit ?: null,
            'note' => $employees->isEmpty() ? 'Nobody this user may see matches.' : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function employeeSummary(User $user, array $args): array
    {
        $id = filter_var($args['employee_id'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            return ['error' => 'employee_id must be the id of an employee from find_employees.'];
        }

        // Through the page's scope again (EmployeeTable::scopedEmployee), never Employee::find().
        $employee = $this->visibleEmployees($user)
            ->with('city')
            ->withExists(['realContracts as has_real_contract'])
            ->find($id);

        if (! $employee) {
            return ['error' => 'No employee with that id is visible to this user.'];
        }

        Related::loadMany([$employee], 'allContracts', 'employee_id', Contract::query());
        $this->loadPositions(Related::many($employee, 'allContracts', Contract::class));

        $locale = app()->getLocale();
        $contracts = Related::many($employee, 'allContracts', Contract::class)->sortByDesc('start_date')->values();

        $requests = LeaveRequest::query()
            ->whereHas('leaveBalance.contract', fn (Builder $q) => $q->where('employee_id', $employee->id))
            ->with(['leaveBalance.leaveType', 'leaveBalance.contract.position.center'])
            ->latest()
            ->limit(10)
            ->get();

        return [
            'id' => $employee->id,
            'name' => $employee->nameForLocale($locale),
            'username' => $employee->username,
            'city' => Related::text(Related::one($employee, 'city', City::class)),
            'status' => $this->employeeStatus($employee),
            'contracts' => $contracts->map(fn (Contract $contract) => array_filter([
                ...Related::place(Related::one($contract, 'position', Position::class)),
                'contract_type' => Related::one($contract, 'contractType', ContractType::class)?->{"name_{$locale}"},
                'start_date' => $contract->start_date?->format('Y-m-d'),
                'end_date' => $contract->end_date?->format('Y-m-d'),
                'weekly_hours' => $contract->weekly_hours,
                'auto_renew' => (bool) $contract->auto_renew,
                'status' => $this->contractStatus($contract),
            ], fn ($value) => filled($value) || $value === false))->values()->all(),
            'leave_balances' => $this->leaveBalances($employee),
            'recent_leave_requests' => $requests->map(fn (LeaveRequest $request) => $this->leaveRequestRow($request, $locale))->values()->all(),
        ];
    }

    /**
     * The balances of the latest active contract, as the employee's own card shows them.
     *
     * @return list<array<string, string|null>>
     */
    private function leaveBalances(Employee $employee): array
    {
        $balances = $employee->availableLeaves();

        if (! $balances || $balances->isEmpty()) {
            return [];
        }

        return $balances->map(fn (LeaveBalance $balance) => [
            'leave_type' => Related::text(Related::one($balance, 'leaveType', LeaveType::class)),
            'entitled' => $this->plain($balance->deserved_formatted),
            'used' => $this->plain($balance->used_formatted),
            'pending_approval' => $this->plain($balance->getFormattedLabel($balance->pending_days ?? 0, $balance->pending_hours ?? 0)),
            'remaining' => $this->plain($balance->remaining),
        ])->values()->all();
    }

    /**
     * The Team leaves page's own rows (AllLeaveRequestTable::query), with the
     * page's wording of where each request stands (LeaveRequestNextStep) and
     * the decision authority's answer on whether this user may decide it.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function leaveRequestsToReview(User $user, array $args): array
    {
        $status = in_array($args['status'] ?? null, ['pending', 'all'], true) ? $args['status'] : 'pending';
        $limit = $this->limit($args, 'get_leave_requests_to_review');
        $locale = app()->getLocale();

        $requests = LeaveRequest::query()
            ->with(['leaveBalance.contract.employee', 'leaveBalance.contract.position.center', 'leaveBalance.leaveType'])
            ->manageableBy($user)
            ->exceptFormerStaffPending()
            ->when($status === 'pending', fn (Builder $q) => $q->where('status', LeaveRequestStatus::Pending))
            ->orderBy('created_at', 'desc')
            ->limit($limit + 1)
            ->get();

        $authority = app(LeaveDecisionAuthority::class);
        $nextStep = app(LeaveRequestNextStep::class);

        return [
            'status_filter' => $status,
            'requests' => $requests->take($limit)->map(function (LeaveRequest $request) use ($user, $locale, $authority, $nextStep) {
                $pending = (int) $request->status === LeaveRequestStatus::Pending;
                $contract = Related::one(Related::one($request, 'leaveBalance', LeaveBalance::class), 'contract', Contract::class);

                return $this->leaveRequestRow($request, $locale) + array_filter([
                    'id' => $request->id,
                    'employee_id' => $contract?->employee_id,
                    'employee' => Related::one($contract, 'employee', Employee::class)?->nameForLocale($locale),
                    'position' => Related::text(Related::one($contract, 'position', Position::class), 'title'),
                    'reason' => $request->reason,
                    'current_step' => $nextStep->describe($request, $user),
                    'you_can_decide' => $pending ? $authority->basisFor($user, $request) !== null : null,
                ], fn ($value) => $value !== null && $value !== '');
            })->values()->all(),
            'truncated' => $requests->count() > $limit ?: null,
            'note' => $requests->isEmpty() ? ($status === 'pending' ? 'No pending leave request waits in this user\'s scope.' : 'No leave request in this user\'s scope.') : null,
        ];
    }

    /**
     * The Contracts page's "ending soon" view (Contract::inView) over the
     * contracts the role may see (Contract::visibleTo), with the page's N days.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function expiringContracts(User $user, array $args): array
    {
        $days = filter_var($args['days'] ?? 60, FILTER_VALIDATE_INT);
        $days = $days === false ? 60 : max(1, min(366, $days));
        $locale = app()->getLocale();
        $today = today();

        $contracts = Contract::query()
            ->visibleTo($user)
            ->inView('ending-soon', $days)
            ->orderBy('contracts.end_date')
            ->orderBy('contracts.id')
            ->limit(self::MAX_EXPIRING + 1)
            ->get();

        Related::loadOne($contracts, 'employee', 'employee_id', Employee::class);
        $this->loadPositions($contracts);

        return [
            'within_days' => $days,
            'contracts' => $contracts->take(self::MAX_EXPIRING)->map(fn (Contract $contract) => array_filter([
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
                'employee' => Related::one($contract, 'employee', Employee::class)?->nameForLocale($locale),
                ...Related::place(Related::one($contract, 'position', Position::class)),
                'contract_type' => Related::one($contract, 'contractType', ContractType::class)?->{"name_{$locale}"},
                'start_date' => $contract->start_date?->format('Y-m-d'),
                'end_date' => $contract->end_date?->format('Y-m-d'),
                'days_left' => $contract->end_date ? (int) $today->diffInDays($contract->end_date->copy()->startOfDay(), false) : null,
                'auto_renew' => (bool) $contract->auto_renew,
            ], fn ($value) => $value !== null && $value !== ''))->values()->all(),
            'truncated' => $contracts->count() > self::MAX_EXPIRING ?: null,
            'note' => $contracts->isEmpty() ? "No contract this user may see ends within {$days} days." : null,
        ];
    }

    /**
     * Employees on a contract running today, counted once per center and once
     * per department, within the Employee statistics page's scope
     * (EmployeeStatistics::contracts: positions in the role's cities).
     *
     * @return array<string, mixed>
     */
    private function teamHeadcount(User $user): array
    {
        $cityIds = $user->activeCityIds();
        $locale = app()->getLocale();

        $base = fn () => Contract::query()
            ->activeNow()
            ->whereIn('contracts.employee_id', Employee::query()->where('is_active', true)->select('id'))
            ->when($cityIds !== null, fn (Builder $q) => $q->whereIn('contracts.position_id', Position::query()->inCity($cityIds ?? [])->select('id')))
            ->join('positions', 'positions.id', '=', 'contracts.position_id');

        $byCenter = $base()
            ->join('centers', 'centers.id', '=', 'positions.center_id')
            ->groupBy('centers.id', "centers.name_{$locale}")
            ->orderBy("centers.name_{$locale}")
            ->select("centers.name_{$locale} as label")
            ->selectRaw('COUNT(DISTINCT contracts.employee_id) as total')
            ->get();

        $byDepartment = $base()
            ->join('departments', 'departments.id', '=', 'positions.department_id')
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('departments.name')
            ->select('departments.name as label')
            ->selectRaw('COUNT(DISTINCT contracts.employee_id) as total')
            ->get();

        $total = $base()->distinct()->count('contracts.employee_id');

        return [
            'total_employees' => $total,
            'by_center' => $this->counts($byCenter, 'center'),
            'by_department' => $this->counts($byDepartment, 'department'),
            'note' => $total === 0 ? 'Nobody holds a running contract in this user\'s scope.' : null,
        ];
    }

    /**
     * @param  Collection<int, Contract>  $rows  label and total per group
     * @return list<array<string, int|string>>
     */
    private function counts(Collection $rows, string $key): array
    {
        return array_values($rows->map(fn (Contract $row) => [$key => (string) $row->getAttribute('label'), 'employees' => (int) $row->getAttribute('total')])->all());
    }

    /** @return array<string, string|null> */
    private function leaveRequestRow(LeaveRequest $request, string $locale): array
    {
        return [
            'leave_type' => Related::text(Related::one(Related::one($request, 'leaveBalance', LeaveBalance::class), 'leaveType', LeaveType::class)),
            'unit' => $request->type,
            'start' => $request->start_at->format($request->type === 'hour' ? 'Y-m-d H:i' : 'Y-m-d'),
            'end' => $request->end_at->format($request->type === 'hour' ? 'Y-m-d H:i' : 'Y-m-d'),
            'duration' => $this->plain($request->duration),
            'status' => LeaveRequestStatus::getLocalizedName((int) $request->status)['value'],
        ];
    }

    /**
     * Each contract's position (with where it sits) and contract type, in
     * one query each.
     *
     * @param  iterable<Contract>  $contracts
     */
    private function loadPositions(iterable $contracts): void
    {
        Related::loadOne($contracts, 'position', 'position_id', Position::class, ['department', 'center.city', 'city', 'jobLevel']);
        Related::loadOne($contracts, 'contractType', 'contract_type_id', ContractType::class);
    }

    /** @param  array<string, mixed>  $args */
    private function limit(array $args, string $tool): int
    {
        $limit = filter_var($args['limit'] ?? self::DEFAULT_LIMITS[$tool], FILTER_VALIDATE_INT);

        return $limit === false ? self::DEFAULT_LIMITS[$tool] : max(1, min(self::MAX_LIMITS[$tool], $limit));
    }

    /**
     * An employee's state as the lists word it: a draft record, active,
     * waiting for a first contract, or inactive.
     */
    private function employeeStatus(Employee $employee): string
    {
        return match (true) {
            $employee->isDraft() => __('employees.onboarding.status-incomplete'),
            (bool) $employee->is_active => __('general.active'),
            $employee->isAwaitingContract() => __('employees.onboarding.status-awaiting'),
            default => __('general.inactive'),
        };
    }

    /**
     * A contract's state as the Contracts table words it, from the type, the
     * active flag and the dates, in the table's own order.
     */
    private function contractStatus(Contract $contract): string
    {
        return match (true) {
            (int) $contract->type === ContractTypeEnum::Draft => ContractTypeEnum::getLocalizedName(ContractTypeEnum::Draft)['value'],
            $contract->isTerminated() => __('contracts.status-terminated'),
            (bool) $contract->is_active => __('general.active'),
            $contract->end_date !== null && $contract->end_date->isPast() => __('contracts.status-expired'),
            default => __('general.inactive'),
        };
    }

    /** The formatted labels carry <strong> markup for the cards. */
    private function plain(?string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $text))) ?? '');
    }
}
