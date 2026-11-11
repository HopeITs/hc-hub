<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Center;
use Modules\Core\Models\Program;
use Modules\Hr\Enums\ContractPlace;
use Modules\Hr\Enums\ContractType as ContractKind;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;

/**
 * Every HR figure the Desk cards and the HR statistics pages show: head
 * counts, contract cards per program or center, personnel traffic and the
 * salary-period split. Each is one or a few grouped queries, narrowed to the
 * viewer's governorates ($cities: null = every governorate).
 *
 * Remembered ten minutes per scope and language, under a version number
 * that flush() moves on (ported from hc-hr's StatisticsCache): saving or
 * deleting a contract or an employee (HrStatisticsObserver) shows in the
 * next read, on every cache store, tags or not. Old entries are left to
 * expire. The ten minutes still catch a write that fires no model events.
 *
 * Head counts read each person's newest contract (Contract::latestPerEmployee),
 * as the old dashboard did.
 */
final class HrStatistics
{
    private const int Seconds = 600;

    /**
     * Where the current version is kept: forever, so an expired version can
     * never bring back the entries of an older one.
     */
    private const string VersionKey = 'hr.stats.version';

    public const array TrafficModes = ['monthly', 'quarterly', 'yearly'];

    /** Salary periods charted, in display order; Unknown is left out as the old page did. */
    public const array SalaryPeriods = [SalaryPer::Month, SalaryPer::Session, SalaryPer::Hour];

    /**
     * Make every figure read afresh.
     */
    public static function flush(): void
    {
        self::version();
        Cache::increment(self::VersionKey);
    }

    /**
     * @param  list<int>|null  $cities  null = every governorate
     * @return array{employees: int, active: int, contracts: int, ending: int}
     */
    public function totals(?array $cities): array
    {
        return $this->remember('totals', $cities, fn (): array => [
            'employees' => $this->employees($cities)->count(),
            'active' => $this->employees($cities)->where('is_active', true)->count(),
            'contracts' => $this->contracts($cities)->activeNow()->count(),
            'ending' => $this->contracts($cities)->inView('ending-soon', 30)->count(),
        ]);
    }

    /**
     * @param  list<int>|null  $cities
     * @return list<array{label: string, count: int}>
     */
    public function byDepartment(?array $cities): array
    {
        return $this->remember('departments', $cities, fn (): array => $this->contracts($cities)
            ->latestPerEmployee()
            ->join('positions', 'positions.id', '=', 'contracts.position_id')
            ->join('departments', 'departments.id', '=', 'positions.department_id')
            ->groupBy('departments.id', 'departments.name', 'departments.code')
            ->orderByRaw('COUNT(*) DESC')
            ->selectRaw('departments.name as label, departments.code as code, COUNT(*) as total')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => ['label' => trim((string) ($row->label ?: $row->code)), 'count' => (int) $row->total])
            ->all());
    }

    /**
     * @param  list<int>|null  $cities
     * @return list<array{label: string, count: int}>
     */
    public function byJobLevel(?array $cities): array
    {
        return $this->remember('levels', $cities, function () use ($cities): array {
            $counts = $this->contracts($cities)
                ->latestPerEmployee()
                ->join('positions', 'positions.id', '=', 'contracts.position_id')
                ->groupBy('positions.job_level_id')
                ->selectRaw('positions.job_level_id as level_id, COUNT(*) as total')
                ->toBase()
                ->pluck('total', 'level_id');

            return JobLevel::query()->select(['id', 'name_en', 'name_ar', 'grade'])->orderBy('grade')->get()
                ->map(fn (JobLevel $level): array => ['label' => (string) $level->name, 'count' => (int) ($counts[$level->id] ?? 0)])
                ->all();
        });
    }

    /**
     * @param  list<int>|null  $cities
     * @return list<array{label: string, count: int}>
     */
    public function byContractType(?array $cities): array
    {
        return $this->remember('types', $cities, function () use ($cities): array {
            $counts = $this->contracts($cities)->activeNow()
                ->groupBy('contracts.contract_type_id')
                ->selectRaw('contracts.contract_type_id as type_id, COUNT(*) as total')
                ->toBase()
                ->pluck('total', 'type_id');

            return ContractType::query()->select(['id', 'name_en', 'name_ar'])->orderBy('id')->get()
                ->map(fn (ContractType $type): array => ['label' => (string) $type->name, 'count' => (int) ($counts[$type->id] ?? 0)])
                ->all();
        });
    }

    /**
     * Active employees on a running contract who have never asked for annual leave.
     *
     * @param  list<int>|null  $cities
     * @return list<array{id: int, name: string, words: string}>
     */
    public function withoutLeaves(?array $cities, int $limit = 60): array
    {
        return $this->remember('no-leaves', $cities, fn (): array => $this->employees($cities)
            ->select(Employee::NameColumns)
            ->where('is_active', true)
            ->whereHas('contracts', fn (Builder $contract) => $contract->whereHas('leaveBalances', fn (Builder $balance) => $balance
                ->where('type_id', LeaveTypeCases::Annual)
                ->whereDoesntHave('leaveRequests')))
            ->orderBy('first_name_en')
            ->limit($limit)
            ->get()
            ->map(fn (Employee $employee): array => ['id' => (int) $employee->id, 'name' => (string) $employee->name, 'words' => (string) $employee->secondary_name])
            ->all());
    }

    /**
     * One card per program (no program chosen) or per center of the chosen
     * program, counting running contracts on positions in the given cities.
     * A position is in a city through its own city or its center's.
     *
     * @param  list<int>|null  $cities
     * @return list<array{id: int, name: string, centers: int|null, employees: int, contracts: int, annual: array{full: int, part: int}, service: array{online: int, onsite: int}, trial: array{full: int, part: int}}>
     */
    public function contractCards(?array $cities, ?int $programId): array
    {
        $key = 'contract-cards.'.($programId ?? 'all');

        return $this->remember($key, $cities, function () use ($cities, $programId): array {
            $group = $programId === null ? 'positions.program_id' : 'positions.center_id';

            $centers = Position::query()
                ->toBase()
                ->when($programId !== null, fn ($query) => $query->where('positions.program_id', $programId))
                ->whereNotNull($group)
                ->tap(fn ($query) => $this->onPositionsIn($query, $cities))
                ->groupBy($group)
                ->selectRaw("{$group} as grp, COUNT(DISTINCT positions.center_id) as centers")
                ->pluck('centers', 'grp');

            $contracts = fn () => Contract::query()
                ->toBase()
                ->join('positions', 'positions.id', '=', 'contracts.position_id')
                ->where('contracts.is_active', true)
                ->when($programId !== null, fn ($query) => $query->where('positions.program_id', $programId))
                ->whereNotNull($group)
                ->tap(fn ($query) => $this->onPositionsIn($query, $cities));

            $employees = $contracts()
                ->groupBy($group)
                ->selectRaw("{$group} as grp, COUNT(DISTINCT contracts.employee_id) as employees")
                ->pluck('employees', 'grp');

            $rows = $contracts()
                ->groupBy($group, 'contracts.contract_type_id', 'contracts.type', 'contracts.place')
                ->selectRaw("{$group} as grp, contracts.contract_type_id as type_id, contracts.type as kind, contracts.place as place, COUNT(*) as total")
                ->get();

            $categories = ContractType::query()->select(['id'])->get()
                ->mapWithKeys(fn (ContractType $type): array => [(int) $type->id => $type->category])
                ->all();

            $names = $programId === null ? Program::namesById() : Center::options();

            $cards = [];

            foreach ($names as $id => $name) {
                if (! $centers->has($id) && ! $employees->has($id)) {
                    continue;
                }

                $cards[$id] = [
                    'id' => (int) $id,
                    'name' => $name,
                    'centers' => $programId === null ? (int) ($centers[$id] ?? 0) : null,
                    'employees' => (int) ($employees[$id] ?? 0),
                    'contracts' => 0,
                    'annual' => ['full' => 0, 'part' => 0],
                    'service' => ['online' => 0, 'onsite' => 0],
                    'trial' => ['full' => 0, 'part' => 0],
                ];
            }

            foreach ($rows as $row) {
                $id = (int) $row->grp;

                if (! isset($cards[$id])) {
                    continue;
                }

                $total = (int) $row->total;
                $cards[$id]['contracts'] += $total;
                $category = $categories[(int) $row->type_id] ?? null;

                if ($category === 'service') {
                    $slot = match ((int) $row->place) {
                        ContractPlace::Online->value => 'online',
                        ContractPlace::Onsite->value => 'onsite',
                        default => null,
                    };
                } elseif ($category === 'annual' || $category === 'trial') {
                    $slot = match ((int) $row->kind) {
                        ContractKind::FullTime->value => 'full',
                        ContractKind::PartTime->value => 'part',
                        default => null,
                    };
                } else {
                    $slot = null;
                }

                if ($slot !== null) {
                    $cards[$id][$category][$slot] += $total;
                }
            }

            return array_values($cards);
        });
    }

    /**
     * Contracts started (running ones), ended and terminated per month (last
     * six), quarter (this year) or year (last five), in one query.
     *
     * @param  list<int>|null  $cities
     * @return array{labels: list<string>, new: list<int>, ended: list<int>, terminated: list<int>}
     */
    public function personnelTraffic(?array $cities, string $mode): array
    {
        $mode = in_array($mode, self::TrafficModes, true) ? $mode : 'monthly';

        return $this->remember('traffic.'.$mode, $cities, function () use ($cities, $mode): array {
            $buckets = $this->trafficBuckets($mode);
            $sums = [];
            $bindings = [];

            foreach (['new' => 'start_date', 'ended' => 'end_date', 'terminated' => 'terminated_at'] as $series => $column) {
                foreach ($buckets as $index => [, $from, $to]) {
                    $active = $series === 'new' ? 'contracts.is_active = 1 AND ' : '';
                    $sums[] = "SUM(CASE WHEN {$active}contracts.{$column} BETWEEN ? AND ? THEN 1 ELSE 0 END) as {$series}_{$index}";
                    array_push($bindings, $from, $to);
                }
            }

            $first = $buckets[0][1];
            $last = $buckets[array_key_last($buckets)][2];

            $row = $this->contracts($cities)
                ->toBase()
                ->where(fn ($query) => $query
                    ->whereBetween('contracts.start_date', [$first, $last])
                    ->orWhereBetween('contracts.end_date', [$first, $last])
                    ->orWhereBetween('contracts.terminated_at', [$first, $last]))
                ->selectRaw(implode(', ', $sums), $bindings)
                ->first();

            $read = fn (string $series): array => array_values(array_map(
                fn (int $index): int => (int) ($row?->{$series.'_'.$index} ?? 0),
                array_keys($buckets),
            ));

            return [
                'labels' => array_values(array_map(fn (array $bucket): string => $bucket[0], $buckets)),
                'new' => $read('new'),
                'ended' => $read('ended'),
                'terminated' => $read('terminated'),
            ];
        });
    }

    /**
     * How many people are paid by the month, session or hour, read on each
     * person's newest contract.
     *
     * @param  list<int>|null  $cities
     * @return array<int, int> salary_per value => people
     */
    public function salaryPeriods(?array $cities): array
    {
        return $this->remember('salary-periods', $cities, fn (): array => $this->contracts($cities)
            ->latestPerEmployee()
            ->whereIn('contracts.salary_per', array_map(fn (SalaryPer $per): int => $per->value, self::SalaryPeriods))
            ->groupBy('contracts.salary_per')
            ->selectRaw('contracts.salary_per as per, COUNT(*) as total')
            ->toBase()
            ->pluck('total', 'per')
            ->map(fn (mixed $total): int => (int) $total)
            ->all());
    }

    /**
     * The salary-period split per program, on each person's newest contract.
     *
     * @param  list<int>|null  $cities
     * @return list<array{program: string, counts: array<int, int>}>
     */
    public function salaryPeriodsByProgram(?array $cities): array
    {
        return $this->remember('salary-periods-by-program', $cities, function () use ($cities): array {
            $rows = $this->contracts($cities)
                ->latestPerEmployee()
                ->join('positions', 'positions.id', '=', 'contracts.position_id')
                ->whereNotNull('positions.program_id')
                ->whereIn('contracts.salary_per', array_map(fn (SalaryPer $per): int => $per->value, self::SalaryPeriods))
                ->groupBy('positions.program_id', 'contracts.salary_per')
                ->selectRaw('positions.program_id as program_id, contracts.salary_per as per, COUNT(*) as total')
                ->toBase()
                ->get();

            $programs = [];

            foreach ($rows as $row) {
                $programs[(int) $row->program_id][(int) $row->per] = (int) $row->total;
            }

            $names = Program::namesById();
            $result = [];

            foreach ($names as $id => $name) {
                if (isset($programs[$id])) {
                    $result[] = ['program' => $name, 'counts' => $programs[$id]];
                }
            }

            return $result;
        });
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}> label, from, to
     */
    private function trafficBuckets(string $mode): array
    {
        $today = CarbonImmutable::today();
        $span = fn (CarbonImmutable $from, CarbonImmutable $to): array => [$from->startOfDay()->toDateTimeString(), $to->endOfDay()->toDateTimeString()];

        return match ($mode) {
            'quarterly' => array_map(function (int $quarter) use ($today, $span): array {
                $from = $today->setDate($today->year, ($quarter - 1) * 3 + 1, 1);

                return [__('hr_statistics.traffic.quarter', ['number' => $quarter]), ...$span($from, $from->addMonths(2)->endOfMonth())];
            }, [1, 2, 3, 4]),
            'yearly' => array_map(function (int $year) use ($today, $span): array {
                $from = $today->setDate($year, 1, 1);

                return [(string) $year, ...$span($from, $from->endOfYear())];
            }, range($today->year - 4, $today->year)),
            default => array_map(function (int $back) use ($today, $span): array {
                $from = $today->startOfMonth()->subMonthsNoOverflow($back);

                return [$from->locale(app()->getLocale())->translatedFormat('M Y'), ...$span($from, $from->endOfMonth())];
            }, [5, 4, 3, 2, 1, 0]),
        };
    }

    /**
     * Narrow a query joined to positions to positions in the given cities.
     *
     * @param  list<int>|null  $cities
     */
    private function onPositionsIn(QueryBuilder $query, ?array $cities): void
    {
        if ($cities === null) {
            return;
        }

        $query->where(fn ($inCity) => $inCity
            ->whereIn('positions.city_id', $cities)
            ->orWhereIn('positions.center_id', Center::query()->select('id')->whereIn('city_id', $cities)));
    }

    /**
     * @param  list<int>|null  $cities
     * @return Builder<Employee>
     */
    private function employees(?array $cities): Builder
    {
        return Employee::query()->when($cities !== null, fn (Builder $query) => $query->whereIn('city_id', $cities));
    }

    /**
     * @param  list<int>|null  $cities
     * @return Builder<Contract>
     */
    private function contracts(?array $cities): Builder
    {
        return Contract::query()->when($cities !== null, fn (Builder $query) => $query->whereHas('employee', fn (Builder $employee) => $employee->whereIn('city_id', $cities)));
    }

    /**
     * @template TValue
     *
     * @param  list<int>|null  $cities
     * @param  callable(): TValue  $read
     * @return TValue
     */
    private function remember(string $what, ?array $cities, callable $read): mixed
    {
        return Cache::remember('hr.stats.v'.self::version().'.'.$what.'.'.($cities === null ? 'all' : md5(implode(',', $cities))).'.'.app()->getLocale(), self::Seconds, $read);
    }

    private static function version(): int
    {
        return (int) Cache::rememberForever(self::VersionKey, fn (): int => 1);
    }
}
