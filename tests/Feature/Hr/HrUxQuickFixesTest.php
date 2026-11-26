<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Leave\LeaveFormat;
use Spatie\Permission\Models\Permission;

/*
| Usability fixes ported from the HR app: leave balances read in whole and half
| hours and fold away until used (My leaves), recruitment lists say how a hire
| starts, and Add employee points at accepted offers.
*/

test('balances round down to the half hour and never show minutes', function () {
    app()->setLocale('en');

    // 41 h 40 min is 41½ h (never more than a request accepts); 9 h 20 min is 9 h.
    expect(LeaveFormat::rounded(41 + 40 / 60, null))->toBe('41½ hours')
        ->and(LeaveFormat::rounded(9 + 20 / 60, null))->toBe('9 hours')
        ->and(LeaveFormat::rounded(0.5, null))->toBe('half an hour')
        ->and(LeaveFormat::rounded(0.0, 8.0))->toBe('0 days')
        ->and(LeaveFormat::rounded(8 * 6 + 1.5, 8.0))->toBe('6 days and 1½ hours')
        ->and(LeaveFormat::hours(41 + 40 / 60, null))->toContain('minutes');
});

test('my leaves folds the balances nobody has used', function () {
    app()->setLocale('en');
    LeaveType::query()->find(LeaveTypeCases::Annual) ?? LeaveType::factory()->create(['id' => LeaveTypeCases::Annual]);
    LeaveType::query()->find(LeaveTypeCases::FullSick) ?? LeaveType::factory()->create(['id' => LeaveTypeCases::FullSick]);
    ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    $employee = Employee::factory()->create();
    $contract = Contract::factory()->for($employee)->create([
        'position_id' => Position::factory()->create()->id, 'contract_type_id' => 1,
        'start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString(),
        'weekly_hours' => 40, 'is_active' => true,
    ]);
    $annual = LeaveBalance::factory()->for($contract)->create(['type_id' => LeaveTypeCases::Annual, 'deserved' => 160]);
    $sick = LeaveBalance::factory()->for($contract)->create(['type_id' => LeaveTypeCases::FullSick, 'deserved' => 48, 'used' => 0]);

    $user = User::factory()->create(['employee_id' => $employee->id]);
    $user->givePermissionTo(Permission::findOrCreate('leaves.view-own', 'web'));

    Livewire::actingAs($user->fresh())->test('hr::leave.index')
        ->assertSeeHtml('data-balance="'.$annual->id.'"')
        ->assertSeeHtml('wire:key="other-balance-'.$sick->id.'"')
        ->assertSeeHtml('data-test="toggle-other-balances"')
        ->assertSee('Show the other balance');

    $sick->update(['used' => 8]);

    Livewire::actingAs($user->fresh())->test('hr::leave.index')
        ->assertDontSeeHtml('wire:key="other-balance-'.$sick->id.'"')
        ->assertDontSeeHtml('data-test="toggle-other-balances"');
});

test('the recruitment lists say under the title how a hire starts', function () {
    app()->setLocale('en');

    foreach (['requests_intro', 'requests_intro_direct', 'campaigns_intro', 'applicants_intro', 'offers_intro'] as $key) {
        expect(__('hr_recruitment.hub.'.$key))->not->toBe('hr_recruitment.hub.'.$key);
    }

    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('recruitment-requests.view', 'web'));

    Livewire::actingAs($user->fresh())->test('hr::recruitment.requests.index')
        ->assertSee(__('hr_recruitment.hub.requests_intro'))
        ->assertDontSee(__('hr_recruitment.hub.requests_intro_direct'));
});

test('add employee offers nothing to someone who may not convert hires', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('employees.create', 'web'));

    Livewire::actingAs($user->fresh())->test('hr::employees.form')
        ->assertDontSeeHtml('data-offers-awaiting-conversion');
});

test('add employee suggests a username from the English name until HR types one', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('employees.create', 'web'));

    Livewire::actingAs($user->fresh())->test('hr::employees.form')
        ->set('form.first_name_en', 'Rami')
        ->assertSet('form.username', 'rami')
        ->set('form.last_name_en', 'Haddad')
        ->assertSet('form.username', 'rami.haddad')
        ->set('form.username', 'rh1')
        ->set('form.last_name_en', 'Haddadi')
        ->assertSet('form.username', 'rh1');
});
