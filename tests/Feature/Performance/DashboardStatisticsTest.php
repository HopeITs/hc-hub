<?php

namespace Tests\Feature\Performance;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Hr\Livewire\Dashboard\DepartmentEmployeeStatistics;
use Modules\Hr\Livewire\Dashboard\JobLevelEmployeeStatistics;
use Modules\Hr\Livewire\Dashboard\SalaryPerEmployeeStatistics;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\StatisticsCache;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

/**
 * Covers the dashboard aggregate rewrite.
 *
 * Two distinct classes of regression are guarded here:
 *
 *   1. CORRECTNESS of the grouped queries that replaced the old
 *      load-everything-then-countBy-in-PHP implementations, including the
 *      eager-load `limit(1)` bug that made the job-level chart report a
 *      headcount of one.
 *
 *   2. CACHE SAFETY. StatisticsCache namespaces keys by locale and city
 *      precisely so an Arabic dashboard cannot serve English labels and one
 *      city cannot serve another's figures. Those failures are silent — the
 *      page still renders, just with someone else's numbers — so they need
 *      explicit tests rather than being left to manual QA.
 */
class DashboardStatisticsTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    private $city;

    private $department;

    private $jobLevel;

    private $program;

    private $contractType;

    protected function setUp(): void
    {
        parent::setUp();

        // The array cache store persists across tests inside one process;
        // start every case from a known-empty state.
        Cache::flush();

        $this->city = $this->makeCity('ALP', '001');
        $this->department = Department::query()->forceCreate(['name' => 'IT', 'code' => 'IT']);
        $this->jobLevel = JobLevel::query()->forceCreate(['name_en' => 'Officer', 'name_ar' => 'موظف']);
        $this->program = Program::query()->forceCreate([
            'name' => 'Core',
            'slug' => 'core',
            'code' => 'CORE',
            'start_date' => now()->subYear(),
            'is_active' => true,
        ]);
        $this->contractType = ContractType::query()->forceCreate([
            'name_en' => 'Annual Full',
            'name_ar' => 'سنوي',
        ]);
    }

    public function test_latest_per_employee_returns_exactly_one_row_per_employee(): void
    {
        $employee = $this->makeEmployee($this->city, 'multi-contract');
        $position = $this->makePosition('p1');

        $this->makeContract($employee->id, $position->id, ['start_date' => now()->subYears(3)]);
        $this->makeContract($employee->id, $position->id, ['start_date' => now()->subYear()]);
        $newest = $this->makeContract($employee->id, $position->id, ['start_date' => now()->subMonth()]);

        $rows = Contract::query()->latestPerEmployee()->get();

        $this->assertCount(1, $rows, 'Three contracts for one employee must collapse to a single row.');
        $this->assertSame($newest->id, $rows->first()->id);
    }

    public function test_latest_per_employee_breaks_start_date_ties_by_id(): void
    {
        $employee = $this->makeEmployee($this->city, 'tied-dates');
        $position = $this->makePosition('p1');

        $sameDay = now()->subMonth();
        $this->makeContract($employee->id, $position->id, ['start_date' => $sameDay]);
        $later = $this->makeContract($employee->id, $position->id, ['start_date' => $sameDay]);

        $rows = Contract::query()->latestPerEmployee()->get();

        $this->assertCount(1, $rows);
        $this->assertSame($later->id, $rows->first()->id, 'Identical start dates must resolve deterministically to the higher id.');
    }

    public function test_latest_per_employee_constraint_applies_to_the_newer_subquery_too(): void
    {
        $employee = $this->makeEmployee($this->city, 'expired-newest');
        $position = $this->makePosition('p1');

        $activeOlder = $this->makeContract($employee->id, $position->id, [
            'start_date' => now()->subYears(2),
            'is_active' => true,
        ]);
        // Newer, but inactive. If the filter were applied only to the outer
        // query this employee would vanish from the result entirely.
        $this->makeContract($employee->id, $position->id, [
            'start_date' => now()->subMonth(),
            'is_active' => false,
        ]);

        $rows = Contract::query()
            ->latestPerEmployee(fn ($query, $table = 'contracts') => $query->where($table.'.is_active', true))
            ->get();

        $this->assertCount(1, $rows, 'The employee must still be counted via their latest ACTIVE contract.');
        $this->assertSame($activeOlder->id, $rows->first()->id);
    }

    public function test_department_statistics_counts_each_employee_once(): void
    {
        $finance = Department::query()->forceCreate(['name' => 'Finance', 'code' => 'FIN']);

        $itPosition = $this->makePosition('it', $this->department);
        $finPosition = $this->makePosition('fin', $finance);

        // Two employees in IT, one of whom has a contract history that must not
        // inflate the count.
        $a = $this->makeEmployee($this->city, 'emp-a');
        $this->makeContract($a->id, $itPosition->id, ['start_date' => now()->subYears(2)]);
        $this->makeContract($a->id, $itPosition->id, ['start_date' => now()->subMonth()]);

        $b = $this->makeEmployee($this->city, 'emp-b');
        $this->makeContract($b->id, $itPosition->id, ['start_date' => now()->subMonth()]);

        $c = $this->makeEmployee($this->city, 'emp-c');
        $this->makeContract($c->id, $finPosition->id, ['start_date' => now()->subMonth()]);

        Livewire::test(DepartmentEmployeeStatistics::class)
            ->assertSet('departmentLabels', ['FIN', 'IT'])
            ->assertSet('employeeCounts', [1, 2]);
    }

    public function test_job_level_statistics_reports_every_employee_not_just_one(): void
    {
        // Regression test for the eager-load limit(1) bug: the old implementation
        // returned a single contract for the whole page, so this chart rendered a
        // headcount of 1 regardless of how many employees existed.
        $senior = JobLevel::query()->forceCreate(['name_en' => 'Senior', 'name_ar' => 'أول']);

        $officerPosition = $this->makePosition('officer', null, $this->jobLevel);
        $seniorPosition = $this->makePosition('senior', null, $senior);

        foreach (['j1', 'j2', 'j3'] as $username) {
            $employee = $this->makeEmployee($this->city, $username);
            $this->makeContract($employee->id, $officerPosition->id, ['is_active' => true]);
        }

        $lead = $this->makeEmployee($this->city, 'j4');
        $this->makeContract($lead->id, $seniorPosition->id, ['is_active' => true]);

        Livewire::test(JobLevelEmployeeStatistics::class)
            ->assertSet('jobLevelLabels', ['Officer', 'Senior'])
            ->assertSet('employeeCounts', [3, 1]);
    }

    public function test_job_level_statistics_ignores_inactive_contracts(): void
    {
        $position = $this->makePosition('officer', null, $this->jobLevel);

        $active = $this->makeEmployee($this->city, 'active-emp');
        $this->makeContract($active->id, $position->id, ['is_active' => true]);

        $inactive = $this->makeEmployee($this->city, 'inactive-emp');
        $this->makeContract($inactive->id, $position->id, ['is_active' => false]);

        Livewire::test(JobLevelEmployeeStatistics::class)
            ->assertSet('employeeCounts', [1]);
    }

    public function test_salary_per_statistics_buckets_by_the_current_contract(): void
    {
        $position = $this->makePosition('p1');

        // salary_per: 1 = Month, 2 = Hour, 3 = Session.
        $monthly = $this->makeEmployee($this->city, 's1');
        $this->makeContract($monthly->id, $position->id, ['salary_per' => 1]);

        $alsoMonthly = $this->makeEmployee($this->city, 's2');
        $this->makeContract($alsoMonthly->id, $position->id, ['salary_per' => 1]);

        $hourly = $this->makeEmployee($this->city, 's3');
        $this->makeContract($hourly->id, $position->id, ['salary_per' => 2]);

        // Unknown (0) must be excluded from the chart entirely.
        $unknown = $this->makeEmployee($this->city, 's4');
        $this->makeContract($unknown->id, $position->id, ['salary_per' => 0]);

        $component = Livewire::test(SalaryPerEmployeeStatistics::class);

        $component->assertSet('totalEmployees', 3);

        $series = collect($component->get('series'))->pluck('value', 'label')->all();

        $this->assertSame(2, $series['Per Month']);
        $this->assertSame(1, $series['Per Hour']);
        $this->assertSame(0, $series['Per Session']);

        // 2 of 3 charted employees are monthly.
        $component->assertSet('highlightPercentage', 67);
    }

    public function test_aggregates_are_served_from_cache_on_a_second_render(): void
    {
        $position = $this->makePosition('p1');
        $employee = $this->makeEmployee($this->city, 'cached');
        $this->makeContract($employee->id, $position->id);

        Livewire::test(DepartmentEmployeeStatistics::class);

        // Count the queries issued by a second mount. A cache hit must not touch
        // the contracts table at all.
        $queries = 0;
        DB::listen(function ($query) use (&$queries) {
            if (str_contains($query->sql, 'contracts')) {
                $queries++;
            }
        });

        Livewire::test(DepartmentEmployeeStatistics::class)
            ->assertSet('employeeCounts', [1]);

        $this->assertSame(0, $queries, 'A warm cache must not re-query the contracts table.');
    }

    public function test_saving_a_contract_invalidates_the_cached_aggregate(): void
    {
        $position = $this->makePosition('p1');

        $first = $this->makeEmployee($this->city, 'inv-1');
        $this->makeContract($first->id, $position->id);

        Livewire::test(DepartmentEmployeeStatistics::class)
            ->assertSet('employeeCounts', [1]);

        // ContractObserver::saved() must start a new cache generation, otherwise
        // the dashboard would keep showing the stale figure for the full TTL.
        $second = $this->makeEmployee($this->city, 'inv-2');
        $this->makeContract($second->id, $position->id);

        Livewire::test(DepartmentEmployeeStatistics::class)
            ->assertSet('employeeCounts', [2]);
    }

    public function test_cached_aggregates_do_not_leak_across_locales(): void
    {
        // Job level labels resolve through the {attribute}_{locale} convention,
        // so an un-scoped cache key would serve Arabic labels to English users.
        $position = $this->makePosition('officer', null, $this->jobLevel);
        $employee = $this->makeEmployee($this->city, 'locale-emp');
        $this->makeContract($employee->id, $position->id, ['is_active' => true]);

        app()->setLocale('en');
        Livewire::test(JobLevelEmployeeStatistics::class)
            ->assertSet('jobLevelLabels', ['Officer']);

        app()->setLocale('ar');
        Livewire::test(JobLevelEmployeeStatistics::class)
            ->assertSet('jobLevelLabels', ['موظف']);
    }

    public function test_cache_keys_are_scoped_by_city(): void
    {
        // Roles are city-scoped, so any aggregate that is ever made city-aware
        // must not be able to serve one city's numbers to another.
        $alp = StatisticsCache::remember('headcount', fn () => 10, cityId: 1);
        $hms = StatisticsCache::remember('headcount', fn () => 99, cityId: 2);

        $this->assertSame(10, $alp);
        $this->assertSame(99, $hms, 'A different city must miss the cache rather than reuse the first value.');
    }

    public function test_flush_starts_a_new_cache_generation(): void
    {
        $this->assertSame('first', StatisticsCache::remember('probe', fn () => 'first'));
        $this->assertSame('first', StatisticsCache::remember('probe', fn () => 'second'));

        StatisticsCache::flush();

        $this->assertSame('second', StatisticsCache::remember('probe', fn () => 'second'));
    }

    private function makePosition(string $prefix, $department = null, $jobLevel = null): Position
    {
        return Position::query()->forceCreate([
            'title_en' => $prefix.'-position',
            'title_ar' => $prefix.'-position',
            'city_id' => $this->city->id,
            'department_id' => ($department ?? $this->department)->id,
            'job_level_id' => ($jobLevel ?? $this->jobLevel)->id,
            'program_id' => $this->program->id,
            'is_active' => true,
        ]);
    }

    private function makeContract(int $employeeId, int $positionId, array $attributes = []): Contract
    {
        return Contract::query()->forceCreate(array_merge([
            'employee_id' => $employeeId,
            'position_id' => $positionId,
            'contract_type_id' => $this->contractType->id,
            'start_date' => now()->subMonths(6),
            'end_date' => now()->addMonths(6),
            'currency' => 2,
            'salary_per' => 1,
            'weekly_hours' => 40,
            'is_active' => true,
        ], $attributes));
    }
}
