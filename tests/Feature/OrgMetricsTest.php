<?php

use App\Models\Permission;
use App\Support\Metrics\OrgMetrics;
use App\Support\Modules\Contracts\MetricSource;
use App\Support\Modules\ModuleRegistry;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Desk\Cards\DepartmentsCard;
use Modules\Hr\Support\Desk\HrStatistics;
use Modules\Tracking\Enums\ProjectStatus;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;

/*
| One definition per organisational number (roadmap 4.12): modules register
| their numbers, and the Desk and the HR statistics page read "employees in
| projects" from that one definition, so they can never disagree.
*/

/**
 * Someone who may read HR statistics, with the given role's reach.
 */
function statisticsReader(string $role): User
{
    $user = userWithRole($role);
    $user->givePermissionTo(Permission::findOrCreate('statistics.view', 'web'));

    return $user;
}

/**
 * Put an employee on a project team; $endedOn closes the membership.
 */
function onProject(int $employeeId, Project $project, ?string $endedOn = null): void
{
    ProjectMember::factory()->create(['employee_id' => $employeeId, 'project_id' => $project->id, 'ends_on' => $endedOn]);
}

/**
 * "Employees in projects" as the Desk's HR statistics card shows it.
 */
function inProjectsOnDesk(User $user): string
{
    return collect((new DepartmentsCard)->figures($user))->firstWhere('label', __('org_metrics.employees_in_projects'))['value'];
}

/**
 * "Employees in projects" as the HR statistics page shows it.
 */
function inProjectsOnReport(User $user, string $city = ''): int
{
    return Livewire::actingAs($user)->test('hr::statistics.general', ['city' => $city])->instance()->figures['inProjects']['value'];
}

beforeEach(function () {
    $this->projects = Project::factory()->count(2)->create();
    ProjectMember::query()->delete();
});

test('the Desk card and the statistics page show the same number of employees in projects', function () {
    onProject(insertHrEmployee(), $this->projects[0]);
    onProject(insertHrEmployee(), $this->projects[1]);
    insertHrEmployee();
    $user = statisticsReader('HR-SYR');

    expect(app(OrgMetrics::class)->value('employees_in_projects'))->toBe(2)
        ->and(inProjectsOnDesk($user))->toBe('2')
        ->and(inProjectsOnReport($user))->toBe(2);

    Livewire::actingAs($user)->test('hr::statistics.general')
        ->assertSeeHtml('data-metric="employees_in_projects"')
        ->assertSee(__('org_metrics.employees_in_projects'));
});

test('one employee on two projects counts once', function () {
    $employeeId = insertHrEmployee();
    onProject($employeeId, $this->projects[0]);
    onProject($employeeId, $this->projects[1]);

    expect(app(OrgMetrics::class)->value('employees_in_projects'))->toBe(1);
});

test('an ended membership, an inactive employee and an archived employee are not counted', function () {
    onProject(insertHrEmployee(), $this->projects[0], '2026-01-31');
    onProject(insertHrEmployee(isActive: false), $this->projects[0]);
    $archived = insertHrEmployee();
    onProject($archived, $this->projects[0]);
    Employee::query()->whereKey($archived)->update(['is_active' => false, 'archived_at' => now()]);
    onProject(insertHrEmployee(), $this->projects[0]);

    $user = statisticsReader('HR-SYR');

    expect(app(OrgMetrics::class)->value('employees_in_projects'))->toBe(1)
        ->and(inProjectsOnDesk($user))->toBe('1')
        ->and(inProjectsOnReport($user))->toBe(1);
});

test('an employee with one ended and one open membership is still on a project', function () {
    $employeeId = insertHrEmployee();
    onProject($employeeId, $this->projects[0], '2026-01-31');
    onProject($employeeId, $this->projects[1]);

    expect(app(OrgMetrics::class)->value('employees_in_projects'))->toBe(1);
});

test('a regional HR reader gets the number of its governorates in both places', function () {
    $aleppo = City::factory()->create(['slug' => 'ALP'])->id;
    $homs = City::factory()->create(['slug' => 'HMS'])->id;
    onProject(insertHrEmployee($aleppo), $this->projects[0]);
    onProject(insertHrEmployee($aleppo), $this->projects[0]);
    onProject(insertHrEmployee($homs), $this->projects[0]);

    $regional = statisticsReader('HR-ALP');
    $national = statisticsReader('HR-SYR');

    expect(inProjectsOnDesk($regional))->toBe('2')
        ->and(inProjectsOnReport($regional))->toBe(2)
        ->and(inProjectsOnReport($regional, (string) $homs))->toBe(2)
        ->and(inProjectsOnDesk($national))->toBe('3')
        ->and(inProjectsOnReport($national))->toBe(3)
        ->and(inProjectsOnReport($national, (string) $homs))->toBe(1)
        ->and(app(OrgMetrics::class)->value('employees_in_projects', []))->toBe(0);
});

test('running projects are the active ones, and At a glance shows that number', function () {
    Project::factory()->status(ProjectStatus::Active)->count(2)->create();
    Project::factory()->status(ProjectStatus::Closed)->create();

    expect(app(OrgMetrics::class)->value('running_projects'))->toBe(2);

    $figures = Livewire::actingAs(userWithRole('General Manager'))->test('desk.panels.glance')->instance()->figures;

    expect(collect($figures)->firstWhere('key', 'running')['value'])->toBe('2');
});

test('the head counts of HR are the statistics page figures under a key', function () {
    insertHrEmployee();
    insertHrEmployee(isActive: false);

    $metrics = app(OrgMetrics::class);
    $totals = app(HrStatistics::class)->totals(null);

    expect($metrics->value('active_employees'))->toBe($totals['active'])
        ->and($metrics->value('employees_on_record'))->toBe($totals['employees'])
        ->and($metrics->value('running_contracts'))->toBe($totals['contracts'])
        ->and($metrics->value('contracts_ending_soon'))->toBe($totals['ending'])
        ->and($metrics->label('active_employees'))->toBe(__('hr_statistics.totals.active'));
});

test('a number has one owner: a second definition of a key, or an unknown key, fails loudly', function () {
    $duplicate = new class implements MetricSource
    {
        public function metrics(): array
        {
            return ['employees_in_projects' => ['label' => 'Again', 'resolve' => fn (?array $cities): int => 99]];
        }
    };

    $registry = app(ModuleRegistry::class);
    $registry->addMetrics($duplicate::class);

    expect(fn () => (new OrgMetrics($registry))->value('employees_in_projects'))->toThrow(LogicException::class)
        ->and(fn () => app(OrgMetrics::class)->has('no_such_number'))->toThrow(LogicException::class);
});

test('an unknown number is refused', function () {
    expect(app(OrgMetrics::class)->has('no_such_number'))->toBeFalse()
        ->and(fn () => app(OrgMetrics::class)->value('no_such_number'))->toThrow(InvalidArgumentException::class);
});

test('the metrics service and its contract name no module', function (string $path) {
    expect(file_get_contents(base_path($path)))->not->toContain('Modules\\Hr', 'Modules\\Tracking', 'Modules\\Core');
})->with([
    'app/Support/Metrics/OrgMetrics.php',
    'app/Support/Modules/Contracts/MetricSource.php',
]);

test('HR shows the number without importing Tracking', function (string $path) {
    expect(file_get_contents(base_path($path)))->not->toContain('Modules\\Tracking');
})->with([
    'modules/Hr/Support/Desk/Cards/HeadcountCard.php',
    'modules/Hr/Support/Metrics/HrMetrics.php',
    'modules/Hr/resources/views/pages/statistics/⚡general.blade.php',
]);
