<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\JobLevel;

/**
 * The HR head counts behind the statistics cards and page. Each is one
 * grouped query, narrowed to the viewer's governorates and remembered for
 * ten minutes per scope and language: the numbers move a few times a day.
 *
 * Head counts read each person's newest contract (Contract::latestPerEmployee),
 * as the old dashboard did.
 */
final class HrStatistics
{
    private const int Seconds = 600;

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
        return Cache::remember('hr.stats.'.$what.'.'.($cities === null ? 'all' : md5(implode(',', $cities))).'.'.app()->getLocale(), self::Seconds, $read);
    }
}
