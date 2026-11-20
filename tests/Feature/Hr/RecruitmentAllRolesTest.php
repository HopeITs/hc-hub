<?php

use App\Models\Permission;
use App\Models\Role;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Recruitment\AccessComparison;

/*
| Roadmap 4.5 (D5, approved 2026-11-20): recruitment counts every role a
| person holds, like the rest of the hub. config recruitment_roles = 'active'
| brings back the old rule (only the role acted under).
*/

beforeEach(function () {
    foreach (['recruitment-requests.view', 'recruitment-requests.submit', 'recruitment-applicants.view', 'recruitment-applicants.export'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    Role::findOrCreate('employee', 'web')->givePermissionTo('recruitment-requests.view');
    Role::findOrCreate('manager', 'web')->givePermissionTo('recruitment-requests.view', 'recruitment-requests.submit');
    Role::findOrCreate('general-manager', 'web')->givePermissionTo('recruitment-requests.view');
    Role::findOrCreate('HR-ALP', 'web')->givePermissionTo('recruitment-applicants.view');
    Role::findOrCreate('HR-HMS', 'web')->givePermissionTo('recruitment-applicants.view');
    Role::findOrCreate('HR-SYR', 'web');
    Role::findOrCreate('super-admin', 'web');
    Role::findOrCreate('Team Member', 'web');

    $this->aleppo = City::factory()->create(['slug' => 'ALP'])->id;
    $this->homs = City::factory()->create(['slug' => 'HMS'])->id;
    $this->damascus = City::factory()->create(['slug' => 'DMS'])->id;

    $this->account = function (array $roles, string $actingAs): User {
        $user = User::factory()->create();
        $user->syncRoles($roles);
        $user->forceFill(['active_role_id' => Role::findByName($actingAs, 'web')->id])->save();
        Access::flush();

        return $user;
    };
});

test('each role alone answers as it always did', function (string $role, bool $submits, bool $seesApplicants, bool $isHr, bool $readsPay, bool $passesEverything) {
    $user = ($this->account)([$role], $role);

    expect(Access::hasActivePermission($user, 'recruitment-requests.submit'))->toBe($submits)
        ->and(Access::hasActivePermission($user, 'recruitment-applicants.view'))->toBe($seesApplicants)
        ->and(Access::isHr($user))->toBe($isHr)
        ->and(Access::canViewSalary($user))->toBe($readsPay)
        ->and(Access::isAdministrator($user))->toBe($passesEverything);
})->with([
    'employee' => ['employee', false, false, false, false, false],
    'manager' => ['manager', true, false, false, false, false],
    'general-manager' => ['general-manager', false, false, false, false, false],
    'HR-ALP' => ['HR-ALP', false, true, true, true, false],
    'HR-SYR' => ['HR-SYR', true, true, true, true, true],
    'super-admin' => ['super-admin', true, true, false, false, true],
    'Team Member' => ['Team Member', false, false, false, false, false],
]);

test('a manager acting as Team Member submits recruitment requests', function () {
    $user = ($this->account)(['Team Member', 'manager'], 'Team Member');

    expect(Access::hasActivePermission($user, 'recruitment-requests.submit'))->toBeTrue()
        ->and(Access::activeRoleIs($user, 'manager'))->toBeTrue()
        ->and(Access::isHr($user))->toBeFalse();
});

test('HR acting as Team Member is HR, in its governorate, and reads pay', function () {
    $user = ($this->account)(['Team Member', 'HR-ALP'], 'Team Member');

    expect(Access::isHr($user))->toBeTrue()
        ->and(Access::hasActivePermission($user, 'recruitment-applicants.view'))->toBeTrue()
        ->and(Access::activeCityIds($user))->toBe([$this->aleppo])
        ->and(Access::activeCityId($user))->toBe($this->aleppo)
        ->and(Access::canAccessCity($user, $this->aleppo))->toBeTrue()
        ->and(Access::canAccessCity($user, $this->damascus))->toBeFalse()
        ->and(Access::canViewSalary($user))->toBeTrue();
});

test('two regional HR roles cover both governorates and no other', function () {
    $user = ($this->account)(['HR-ALP', 'HR-HMS'], 'HR-ALP');

    expect(Access::activeCityIds($user))->toEqualCanonicalizing([$this->aleppo, $this->homs])
        ->and(Access::canAccessCity($user, $this->damascus))->toBeFalse();
});

test('a national or administrator role beside a regional one lifts the governorate limit', function (string $other) {
    $user = ($this->account)(['HR-ALP', $other], 'HR-ALP');

    expect(Access::activeCityIds($user))->toBeNull()
        ->and(Access::canAccessCity($user, $this->damascus))->toBeTrue()
        ->and(Access::isAdministrator($user))->toBeTrue();
})->with(['HR-SYR', 'super-admin']);

test('a role that is blind to pay hides it whatever else the person holds', function () {
    $user = ($this->account)(['HR-ALP', 'super-admin'], 'HR-ALP');

    expect(Access::canViewSalary($user))->toBeFalse();
});

test('export stays an explicit grant: passing every check does not give it', function () {
    $admin = ($this->account)(['super-admin'], 'super-admin');
    $granted = ($this->account)(['Team Member', 'HR-ALP'], 'Team Member');
    Role::findByName('HR-ALP', 'web')->givePermissionTo('recruitment-applicants.export');
    Access::flush();

    expect(Access::hasExplicitPermission($admin, 'recruitment-applicants.export'))->toBeFalse()
        ->and(Access::hasExplicitPermission($granted, 'recruitment-applicants.export'))->toBeTrue();
});

test('someone with no recruitment role gains nothing', function () {
    $user = ($this->account)(['Team Member'], 'Team Member');

    expect(Access::hasActivePermission($user, 'recruitment-requests.view'))->toBeFalse()
        ->and(Access::isHr($user))->toBeFalse()
        ->and(Access::activeCityIds($user))->toBeNull();
});

test('the active switch brings back the old rule', function () {
    config()->set('access-control.recruitment_roles', 'active');
    $user = ($this->account)(['Team Member', 'HR-ALP', 'manager'], 'Team Member');

    expect(Access::hasActivePermission($user, 'recruitment-requests.submit'))->toBeFalse()
        ->and(Access::isHr($user))->toBeFalse()
        ->and(Access::canViewSalary($user))->toBeFalse()
        ->and(Access::activeCityIds($user))->toBeNull();
});

test('what recruitment answers now is the after column the owner approved', function () {
    $user = ($this->account)(['employee', 'manager', 'HR-ALP'], 'employee');
    $row = collect(app(AccessComparison::class)->rows())->firstWhere('user_id', $user->id);

    foreach (app(AccessComparison::class)->recruitmentPermissions() as $permission) {
        expect(Access::hasActivePermission($user, $permission))->toBe(in_array($permission, $row['after']['permissions'], true));
    }

    expect(Access::canViewSalary($user))->toBe($row['after']['salary'])
        ->and(Access::isAdministrator($user))->toBe($row['after']['administrator'])
        ->and(Access::isHr($user))->toBe($row['after']['hr'])
        ->and(Access::activeCityIds($user))->toBe([$this->aleppo])
        ->and($row['after']['cities'])->toBe('ALP');
});
