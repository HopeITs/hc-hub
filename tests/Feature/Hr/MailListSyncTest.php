<?php

use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
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
