<?php

use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Models\AttendanceImport;
use Modules\Hr\Models\AttendancePunch;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\MailListMember;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Attendance\Grouping;
use Modules\Hr\Support\MailLists\MailListSync;
use Spatie\Permission\Models\Permission;

/*
| A mail list can carry a rule (all staff, the board, or a city / project /
| department / managers). The Check page compares it with HR data: who is
| missing, who does not match, who has left. Missing people are only queued
| as pending; nothing changes at the mail provider.
*/

beforeEach(function () {
    $this->travelTo('2026-11-17 10:00:00');
    Permission::findOrCreate('mail-lists.manage', 'web');
    foreach (['attendance.view-own', 'attendance.view', 'attendance.manage'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->aleppo = City::factory()->create();
    $this->homs = City::factory()->create();

    /** Someone on a contract running today, with a work account. */
    $this->staff = function (City $city, string $email, bool $active = true): Employee {
        $employee = Employee::factory()->create(['city_id' => $city->id, 'is_active' => $active]);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'position_id' => Position::factory()->create(['city_id' => $city->id])->id,
            'weekly_hours' => 40, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        User::factory()->create(['employee_id' => $employee->id, 'email' => $email]);

        return $employee;
    };

    $this->hrManager = function (): User {
        $user = User::factory()->role('HR-SYR')->create();
        $user->givePermissionTo('mail-lists.manage');

        return $user->refresh();
    };
});

test('a city list reports who is missing, who does not match and who has left', function () {
    ($this->staff)($this->aleppo, 'rami@hcsyria.org');
    ($this->staff)($this->aleppo, 'lina@hcsyria.org');
    ($this->staff)($this->homs, 'sami@hcsyria.org');
    ($this->staff)($this->aleppo, 'gone@hcsyria.org', active: false);

    $list = MailList::factory()->create(['rule_type' => MailList::RULE_CUSTOM, 'rule_city_id' => $this->aleppo->id]);
    foreach (['rami@hcsyria.org', 'sami@hcsyria.org', 'gone@hcsyria.org', 'friend@example.com'] as $email) {
        $list->members()->create(['email' => $email, 'status' => MailListMember::ACTIVE]);
    }

    $result = app(MailListSync::class)->compare($list);

    expect($result['missing']->pluck('email')->all())->toBe(['lina@hcsyria.org'])
        ->and($result['not_matching']->pluck('email')->all())->toBe(['sami@hcsyria.org'])
        ->and($result['left']->pluck('email')->all())->toBe(['gone@hcsyria.org'])
        ->and($result['in_sync'])->toBeFalse();

    expect(app(MailListSync::class)->queueMissing($list))->toBe(1)
        ->and($list->members()->pending()->pluck('email')->all())->toBe(['lina@hcsyria.org'])
        ->and(app(MailListSync::class)->queueOne($list, 'sami@hcsyria.org'))->toBeFalse();
});

test('the board expects nobody, and a list without a rule is not checked', function () {
    ($this->staff)($this->aleppo, 'rami@hcsyria.org');
    $board = MailList::factory()->create(['rule_type' => MailList::RULE_BOARD]);
    $board->members()->create(['email' => 'rami@hcsyria.org', 'status' => MailListMember::ACTIVE]);

    expect(app(MailListSync::class)->compare($board)['in_sync'])->toBeTrue()
        ->and(MailList::factory()->create()->hasRule())->toBeFalse();
});

test('hr sets a rule on the check page, an empty custom rule is refused, and the missing are queued', function () {
    ($this->staff)($this->aleppo, 'rami@hcsyria.org');
    $list = MailList::factory()->create();
    $hr = ($this->hrManager)();

    $page = Livewire::actingAs($hr)->test('hr::mail-lists.check')
        ->call('editRule', $list->id)
        ->assertSet('showRuleModal', true)
        ->set('ruleType', MailList::RULE_CUSTOM)
        ->call('saveRule')
        ->assertHasErrors('ruleType')
        ->set('ruleCity', (string) $this->aleppo->id)
        ->call('saveRule')
        ->assertHasNoErrors()
        ->assertSet('showRuleModal', false)
        ->assertSee('rami@hcsyria.org');

    expect($list->fresh()->rule_city_id)->toBe($this->aleppo->id);

    $page->call('queueMissing', $list->id);

    expect($list->members()->pending()->pluck('email')->all())->toBe(['rami@hcsyria.org']);
});

test('only mail-lists.manage opens the check page', function () {
    $this->actingAs(User::factory()->create()->refresh())->get(route('hr.mail-lists.check'))->assertForbidden();
    $this->actingAs(($this->hrManager)())->get(route('hr.mail-lists.check'))->assertOk();
});

test('the attendance page offers to email the list that stands for a city and a department', function () {
    $employee = ($this->staff)($this->aleppo, 'rami@hcsyria.org');
    // The page lists only people the uploaded reports hold punches for in the month.
    AttendancePunch::query()->create([
        'import_id' => AttendanceImport::query()->firstOrCreate(['file_name' => 'fixture.xls'])->id,
        'device_no' => (string) $employee->id,
        'employee_id' => $employee->id,
        'punched_at' => now()->startOfMonth()->addHours(8),
    ]);
    $departmentId = $employee->allContracts()->first()->position->department_id;
    MailList::factory()->create(['address' => 'hope.aleppo@hcsyria.org', 'rule_type' => MailList::RULE_CUSTOM, 'rule_city_id' => $this->aleppo->id]);
    MailList::factory()->create(['address' => 'it@hcsyria.org', 'rule_type' => MailList::RULE_CUSTOM, 'rule_department_id' => $departmentId]);
    $hr = User::factory()->role('HR-'.strtoupper($this->aleppo->slug))->create();
    $hr->givePermissionTo(['attendance.view-own', 'attendance.view', 'attendance.manage']);

    Livewire::actingAs($hr->refresh())->test('hr::attendance.timesheets')
        ->set('month', CarbonImmutable::now()->format('Y-m'))
        ->set('groupBy', Grouping::DEPARTMENT)
        ->assertSeeHtml('href="mailto:hope.aleppo@hcsyria.org"')
        ->assertSeeHtml('mailto:it@hcsyria.org');
});

test('a new account is queued on every list its rule matches, once', function () {
    $team = MailList::factory()->create(['address' => 'team@hcsyria.org', 'rule_type' => MailList::RULE_ALL_STAFF]);
    $aleppo = MailList::factory()->create(['rule_type' => MailList::RULE_CUSTOM, 'rule_city_id' => $this->aleppo->id]);
    $homs = MailList::factory()->create(['rule_type' => MailList::RULE_CUSTOM, 'rule_city_id' => $this->homs->id]);

    $employee = ($this->staff)($this->aleppo, 'anna@hcsyria.org');

    expect($team->members()->pluck('email')->all())->toBe(['anna@hcsyria.org'])
        ->and($aleppo->members()->first()->status)->toBe(MailListMember::PENDING)
        ->and($homs->members()->count())->toBe(0)
        ->and(app(MailListSync::class)->queueForPerson($employee))->toBe(0);
});

test('someone who leaves is flagged for removal and comes back when reactivated', function () {
    $employee = ($this->staff)($this->aleppo, 'anna@hcsyria.org');
    $team = MailList::factory()->create(['rule_type' => MailList::RULE_ALL_STAFF]);
    $queued = MailList::factory()->create();
    $team->members()->create(['email' => 'anna@hcsyria.org', 'status' => MailListMember::ACTIVE]);
    $queued->members()->create(['email' => 'anna@hcsyria.org', 'status' => MailListMember::PENDING]);

    $employee->update(['is_active' => false]);

    // In the group at the control panel: flagged. Only queued here: dropped.
    expect($team->members()->first()->status)->toBe(MailListMember::LEAVING)
        ->and($queued->members()->count())->toBe(0);

    $employee->update(['is_active' => true]);

    expect($team->members()->first()->status)->toBe(MailListMember::ACTIVE);
});

test('hr queues a removal from the check page and only the confirmer clears it', function () {
    ($this->staff)($this->aleppo, 'carla@hcsyria.org', active: false);
    $team = MailList::factory()->create(['rule_type' => MailList::RULE_ALL_STAFF]);
    $team->members()->create(['email' => 'carla@hcsyria.org', 'status' => MailListMember::ACTIVE]);
    $member = $team->members()->first();

    $holder = User::factory()->role('HR-SYR')->create();
    $holder->givePermissionTo(Permission::findOrCreate('mail-lists.confirm', 'web'));
    $holder = $holder->refresh();

    Livewire::actingAs(($this->hrManager)())->test('hr::mail-lists.check')->call('queueRemoval', $member->id);
    expect($member->fresh()->status)->toBe(MailListMember::LEAVING);

    // HR cannot confirm it; the holder of the control panel can, and sees the queue.
    Livewire::actingAs(($this->hrManager)())->test('hr::mail-lists.index')->call('confirmRemoval', $member->id)->assertForbidden();
    Livewire::actingAs($holder)->test('hr::mail-lists.index')
        ->assertSeeHtml('data-mail-lists-leaving')
        ->call('confirmRemovalEmail', 'carla@hcsyria.org');

    expect($team->members()->count())->toBe(0);
});

test('the daily command queues people whose contract just started and nobody twice', function () {
    $team = MailList::factory()->create(['address' => 'team@hcsyria.org', 'rule_type' => MailList::RULE_ALL_STAFF]);

    $starter = function (string $email, string $start): void {
        $employee = Employee::factory()->create(['city_id' => $this->aleppo->id]);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'position_id' => Position::factory()->create(['city_id' => $this->aleppo->id])->id,
            'weekly_hours' => 40, 'start_date' => $start, 'end_date' => '2027-12-31', 'is_active' => true,
        ]);
        User::factory()->create(['employee_id' => $employee->id, 'email' => $email]);
    };

    $starter('recent@hcsyria.org', '2026-11-16');
    $starter('old@hcsyria.org', '2025-11-17');
    // The accounts were made before the list had this rule, so nothing was queued then.
    $team->members()->delete();

    $this->artisan('mail-lists:queue-starters')->assertSuccessful();
    $this->artisan('mail-lists:queue-starters')->assertSuccessful();

    expect($team->members()->pluck('email')->all())->toBe(['recent@hcsyria.org'])
        ->and($team->members()->first()->status)->toBe(MailListMember::PENDING);
});

test('a center and a project page offer to email the list that stands for them', function () {
    $center = Center::factory()->create(['city_id' => $this->aleppo->id, 'is_active' => true]);
    $program = Program::factory()->unassigned()->create();
    MailList::factory()->create(['address' => 'hope.aleppo@hcsyria.org', 'rule_type' => MailList::RULE_CUSTOM, 'rule_city_id' => $this->aleppo->id]);
    MailList::factory()->create(['address' => 'project@hcsyria.org', 'rule_type' => MailList::RULE_CUSTOM, 'rule_program_id' => $program->id]);
    $admin = User::factory()->create();
    foreach (['programs.view', 'centers.view', 'employees.view'] as $permission) {
        $admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    Livewire::actingAs($admin->refresh())->test('hr::settings.center', ['center' => $center->id])
        ->assertSeeHtml('href="mailto:hope.aleppo@hcsyria.org"');
    Livewire::actingAs($admin)->test('hr::settings.program', ['program' => $program])
        ->assertSeeHtml('href="mailto:project@hcsyria.org"');
});
