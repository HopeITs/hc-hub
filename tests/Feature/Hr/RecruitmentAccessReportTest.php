<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Recruitment\AccessComparison;

/*
| Roadmap 4.5: before recruitment adds up the permissions of every role a
| person holds, hub:recruitment-access shows the owner who would change. It
| reads only, and recruitment still decides by the active role.
*/

beforeEach(function () {
    foreach (['recruitment-requests.view', 'recruitment-requests.submit', 'recruitment-applicants.view', 'recruitment-applicants.view-offer-salary'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    Role::findOrCreate('employee', 'web')->givePermissionTo('recruitment-requests.view');
    Role::findOrCreate('manager', 'web')->givePermissionTo('recruitment-requests.view', 'recruitment-requests.submit');
    Role::findOrCreate('HR-ALP', 'web')->givePermissionTo('recruitment-applicants.view', 'recruitment-applicants.view-offer-salary');
    Role::findOrCreate('super-admin', 'web');

    $this->account = function (array $roles, ?string $actingAs = null): User {
        $user = User::factory()->create();
        $user->syncRoles($roles);

        if ($actingAs !== null) {
            $user->forceFill(['active_role_id' => Role::findByName($actingAs, 'web')->id])->save();
        }

        return $user;
    };
    $this->rowFor = fn (User $user): array => collect(app(AccessComparison::class)->rows())->firstWhere('user_id', $user->id);
});

test('someone acting as an employee who is also HR would gain HR\'s permissions, governorate and pay', function () {
    $row = ($this->rowFor)(($this->account)(['employee', 'HR-ALP'], 'employee'));

    expect($row['before']['permissions'])->toBe(['recruitment-requests.view'])
        ->and($row['gained'])->toBe(['recruitment-applicants.view', 'recruitment-applicants.view-offer-salary'])
        ->and($row['lost'])->toBe([])
        ->and([$row['before']['hr'], $row['after']['hr']])->toBe([false, true])
        ->and([$row['before']['cities'], $row['after']['cities']])->toBe(['all', 'ALP'])
        ->and([$row['before']['salary'], $row['after']['salary']])->toBe([false, true])
        ->and($row['changes'])->toBe(['hr', 'cities', 'salary']);
});

test('the before column is what recruitment answers today', function () {
    $user = ($this->account)(['employee', 'manager', 'HR-ALP'], 'manager');
    $row = ($this->rowFor)($user);

    foreach (app(AccessComparison::class)->recruitmentPermissions() as $permission) {
        expect(in_array($permission, $row['before']['permissions'], true))->toBe(Access::hasActivePermission($user, $permission));
    }

    expect($row['before']['salary'])->toBe(Access::canViewSalary($user))
        ->and($row['before']['administrator'])->toBe(Access::isAdministrator($user));
});

test('with no active role the first role held is the one that counts today', function () {
    $row = ($this->rowFor)(($this->account)(['manager', 'employee']));

    expect($row['active_role'])->toBe('employee')
        ->and($row['gained'])->toBe(['recruitment-requests.submit']);
});

test('an account with one role would not change', function () {
    $row = ($this->rowFor)(($this->account)(['manager'], 'manager'));

    expect($row['gained'])->toBe([])->and($row['lost'])->toBe([])->and($row['changes'])->toBe([]);
});

test('holding super-admin beside the active role would pass every check and hide pay', function () {
    $row = ($this->rowFor)(($this->account)(['HR-ALP', 'super-admin'], 'HR-ALP'));

    expect([$row['before']['administrator'], $row['after']['administrator']])->toBe([false, true])
        ->and($row['after']['permissions'])->toBe(app(AccessComparison::class)->recruitmentPermissions())
        ->and([$row['before']['cities'], $row['after']['cities']])->toBe(['ALP', 'all'])
        ->and([$row['before']['salary'], $row['after']['salary']])->toBe([true, false]);
});

test('a direct grant counts before and after', function () {
    $user = ($this->account)(['employee'], 'employee');
    $user->givePermissionTo('recruitment-applicants.view');

    $row = ($this->rowFor)($user);

    expect($row['before']['permissions'])->toContain('recruitment-applicants.view')
        ->and($row['gained'])->toBe([]);
});

test('the command lists who would change, writes the CSV and changes nothing', function () {
    $changing = ($this->account)(['employee', 'HR-ALP'], 'employee');
    $steady = ($this->account)(['manager'], 'manager');
    $path = storage_path('framework/testing/recruitment-access.csv');
    @mkdir(dirname($path), recursive: true);
    $grantsBefore = DB::table('model_has_roles')->count() + DB::table('role_has_permissions')->count();

    expect(Artisan::call('hub:recruitment-access', ['--csv' => $path]))->toBe(0);
    $output = Artisan::output();
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    @unlink($path);

    expect($output)->toContain('1 would change')->toContain("#{$changing->id} ")->not->toContain("#{$steady->id} ")->toContain('Nothing was changed.')
        ->and($lines)->toHaveCount(3)
        ->and(DB::table('model_has_roles')->count() + DB::table('role_has_permissions')->count())->toBe($grantsBefore)
        ->and($changing->fresh()->active_role_id)->toBe(Role::findByName('employee', 'web')->id);
});
