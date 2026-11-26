<?php

use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Models\ActivityPlan;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Policies\PlanUpdatePolicy;
use Modules\Tracking\Policies\StrategicPlanPolicy;

/*
| Roadmap 4.3: a policy asks for a permission, never for a role by name. The
| three rules that named a role are permissions held by that same role.
*/

/**
 * @return list<string>
 */
function holdersOf(string $permission): array
{
    return Role::query()->whereHas('permissions', fn ($query) => $query->where('name', $permission))->pluck('name')->sort()->values()->all();
}

test('no policy names a role', function () {
    $names = [
        ...array_keys(config('access-control.role_baselines')),
        ...config('access-control.administrator_roles'),
        ...array_keys(config('access-control.role_aliases')),
        'HR', 'HR-SYR',
    ];
    $pattern = '/[\'"]('.implode('|', array_map(fn (string $name): string => preg_quote($name, '/'), $names)).')[\'"]/';
    $found = [];

    foreach ([app_path('Policies'), ...File::directories(base_path('modules'))] as $directory) {
        $directory = str_ends_with($directory, 'Policies') ? $directory : $directory.DIRECTORY_SEPARATOR.'Policies';

        foreach (File::isDirectory($directory) ? File::allFiles($directory) : [] as $file) {
            if (preg_match_all($pattern, php_strip_whitespace($file->getPathname()), $matches) > 0) {
                $found[] = $file->getFilename().': '.implode(', ', array_unique($matches[1]));
            }
        }
    }

    expect($found)->toBe([]);
});

test('each stand-in permission is held by the role its policy used to name', function (string $permission, array $roles) {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(holdersOf($permission))->toBe($roles);
})->with([
    'covering an unassigned project' => ['meal-coverage.stand-in', ['MEAL', 'Super Admin']],
    'deciding reopen requests with no MEAL manager' => ['reports.reopen-stand-in', ['Public Relations Manager', 'Super Admin']],
    'approving the plan of a program with no manager' => ['strategies.approve-unmanaged', ['General Manager', 'Super Admin']],
]);

test('the grant migration gives each permission to the same roles on an existing database', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $migration = 'database/migrations/2026_11_20_100100_grant_policy_stand_in_permissions.php';

    Artisan::call('migrate:rollback', ['--path' => $migration]);
    expect(holdersOf('meal-coverage.stand-in'))->toBe([]);

    Artisan::call('migrate', ['--path' => $migration]);

    expect(holdersOf('meal-coverage.stand-in'))->toBe(['MEAL', 'Super Admin'])
        ->and(holdersOf('reports.reopen-stand-in'))->toBe(['Public Relations Manager', 'Super Admin'])
        ->and(holdersOf('strategies.approve-unmanaged'))->toBe(['General Manager', 'Super Admin']);
});

test('a project with no responsible officer is covered by whoever holds the stand-in permission', function () {
    $project = Project::factory()->create();
    $meal = userWithRole('MEAL', Employee::factory()->create()->id);
    $manager = userWithRole('Managers', Employee::factory()->create()->id);
    $custom = User::factory()->role('Monitoring Lead')->create(['employee_id' => Employee::factory()->create()->id]);
    Role::findByName('Monitoring Lead', 'web')->givePermissionTo('meal-coverage.stand-in');

    expect(PlanUpdatePolicy::isOfficer($meal, $project))->toBeTrue()
        ->and(PlanUpdatePolicy::isOfficer($custom->fresh(), $project))->toBeTrue()
        ->and(PlanUpdatePolicy::isOfficer($manager, $project))->toBeFalse();
});

test('a MEAL role that lost the stand-in permission no longer covers an unassigned project', function () {
    $project = Project::factory()->create();
    $meal = userWithRole('MEAL', Employee::factory()->create()->id);
    Role::findByName('MEAL', 'web')->revokePermissionTo('meal-coverage.stand-in');

    expect(PlanUpdatePolicy::isOfficer($meal->fresh(), $project))->toBeFalse();
});

test('the plan of a program with no manager is approved by the General Manager only', function (string $role, bool $approves) {
    $program = Program::factory()->create(['manager_id' => null]);
    $user = userWithRole($role, Employee::factory()->create()->id);

    expect((new StrategicPlanPolicy)->approvesFor($user, $program->id))->toBe($approves);
})->with([
    'General Manager' => ['General Manager', true],
    'Super Admin' => ['Super Admin', true],
    'Managers' => ['Managers', false],
    'MEAL' => ['MEAL', false],
]);

test('the General Manager reads plan updates as a summary only, an administrator in full', function (string $role, bool $readsInFull) {
    $user = userWithRole($role, Employee::factory()->create()->id);
    $project = Project::factory()->create(['owner_employee_id' => $user->employee_id]);

    expect(PlanUpdatePolicy::canRead($user, ActivityPlan::factory()->create(), $project))->toBe($readsInFull);
})->with([
    'General Manager' => ['General Manager', false],
    'Super Admin' => ['Super Admin', true],
    'Managers' => ['Managers', true],
    'Team Member' => ['Team Member', true],
]);
