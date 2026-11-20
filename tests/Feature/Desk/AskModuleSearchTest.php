<?php

use App\Support\Modules\Contracts\SearchSource;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Search\EmployeeSearchSource;

/*
| Ask finds HR records through the module registry (roadmap 4.11, D10): an
| employee by name or username, by the profile page's own visibility rules.
*/

/**
 * The employee rows Ask shows a viewer for what they typed: label => href.
 *
 * @return array<string, string>
 */
function askEmployees(User $user, string $query): array
{
    $results = Livewire::actingAs($user)->test('desk.ask')->set('query', $query)->instance()->results;
    $group = collect($results['groups'])->firstWhere('source', 'employees');

    return array_column($group['items'] ?? [], 'href', 'label');
}

function employeeNamed(string $first, string $last, ?int $cityId = null, bool $isActive = true, string $firstAr = 'سامي', string $lastAr = 'حداد'): Employee
{
    return Employee::query()->findOrFail(insertHrEmployee($cityId, $first, $last, $firstAr, $lastAr, $isActive));
}

test('the HR module registers its employee search, and app code reads it only through the contract', function () {
    $sources = app(ModuleRegistry::class)->searchSources();

    expect(array_map(get_class(...), $sources))->toContain(EmployeeSearchSource::class)
        ->and($sources)->each->toBeInstanceOf(SearchSource::class);
});

test('a name finds the employee, with a link to their profile', function () {
    $nadia = employeeNamed('Nadia', 'Khoury', firstAr: 'نادية', lastAr: 'خوري');
    employeeNamed('Omar', 'Saleh');
    $viewer = userWithRole('Team Member');

    $profile = route('hr.employees.show', ['employee' => $nadia->username]);

    expect(askEmployees($viewer, 'nadia'))->toBe(['Nadia Khoury' => $profile])
        ->and(askEmployees($viewer, 'khoury nadia'))->toBe(['Nadia Khoury' => $profile])
        ->and(askEmployees($viewer, 'نادية خوري'))->toBe(['Nadia Khoury' => $profile])
        ->and(askEmployees($viewer, $nadia->username))->toBe(['Nadia Khoury' => $profile])
        ->and(askEmployees($viewer, 'employee nadia'))->toBe(['Nadia Khoury' => $profile])
        ->and(askEmployees($viewer, 'موظف نادية'))->toBe(['Nadia Khoury' => $profile]);

    $this->actingAs($viewer)->get($profile)->assertOk();
});

test('a former employee is found by national HR and the general manager, never by a plain employee', function () {
    employeeNamed('Farid', 'Former', isActive: false);
    employeeNamed('Farida', 'Present');

    expect(array_keys(askEmployees(userWithRole('HR-SYR'), 'farid')))->toBe(['Farida Present', 'Farid Former'])
        ->and(array_keys(askEmployees(userWithRole('General Manager'), 'farid')))->toBe(['Farida Present', 'Farid Former'])
        ->and(array_keys(askEmployees(userWithRole('Team Member'), 'farid')))->toBe(['Farida Present'])
        ->and(array_keys(askEmployees(userWithRole('employee'), 'farid')))->toBe(['Farida Present']);
});

test('a regional HR role finds former employees of its governorates only', function () {
    $aleppo = City::factory()->create(['slug' => 'ALP'])->id;
    $homs = City::factory()->create(['slug' => 'HMS'])->id;
    employeeNamed('Zaki', 'Aleppo', $aleppo, isActive: false);
    employeeNamed('Zaki', 'Homs', $homs, isActive: false);
    employeeNamed('Zaki', 'Working', $homs);

    expect(array_keys(askEmployees(userWithRole('HR-ALP'), 'zaki')))->toBe(['Zaki Working', 'Zaki Aleppo']);
});

test('everyone finds their own record, even when it is inactive', function () {
    $mine = employeeNamed('Selma', 'Self', isActive: false);
    employeeNamed('Selma', 'Other', isActive: false);

    expect(array_keys(askEmployees(userWithRole('Team Member', $mine->id), 'selma')))->toBe(['Selma Self']);
});

test('what is typed is bound, and % and _ are read as letters', function (string $typed) {
    employeeNamed('Percy', 'Plain');
    $user = userWithRole('HR-SYR');
    $source = app(EmployeeSearchSource::class);
    $before = Employee::query()->count();

    DB::enableQueryLog();
    $rows = $source->search($user, $typed, 6);
    $queries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'employees'));
    DB::disableQueryLog();

    expect($rows)->toBe([])
        ->and($queries)->not->toBeEmpty()
        ->and($queries->every(fn (array $query): bool => ! str_contains($query['query'], $typed)))->toBeTrue()
        ->and(Employee::query()->count())->toBe($before);
})->with([
    'a percent sign' => ['%'],
    'an underscore' => ['_____'],
    'a quote and a statement' => ["x'; DROP TABLE employees; --"],
    'an always-true clause' => ["' OR '1'='1"],
]);

test('a search reads at most the rows it shows, never the whole employee list', function () {
    foreach (range(1, 9) as $number) {
        employeeNamed('Lina', 'Number'.$number);
    }

    expect(app(EmployeeSearchSource::class)->search(userWithRole('HR-SYR'), 'lina', 6))->toHaveCount(6)
        ->and(askEmployees(userWithRole('Team Member'), 'lina'))->toHaveCount(6);
});

test('a question that names a Desk module stays in that module', function () {
    employeeNamed('Wells', 'Engineer');

    expect(askEmployees(userWithRole('General Manager'), 'project wells'))->toBe([]);
});

test('the search contract, the registry and the Ask bar never name the HR module', function (string $path) {
    expect(file_get_contents(base_path($path)))->not->toContain('Modules\Hr');
})->with([
    'app/Support/Modules/Contracts/SearchSource.php',
    'app/Support/Modules/ModuleRegistry.php',
    'resources/views/components/desk/⚡ask.blade.php',
]);
