<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Enums\BadgeType;
use Modules\Hr\Models\Badge;
use Modules\Hr\Models\Employee;

/*
| An employee reads their own rewards and warnings on their profile even
| without badges.view: no pay, no private notes, no salary increases.
| Someone else without the permission sees no badges tab at all.
*/

beforeEach(function () {
    $this->employee = Employee::factory()->create();
    $this->owner = User::factory()->create(['employee_id' => $this->employee->id])->fresh();

    Badge::factory()->for($this->employee)->create([
        'type' => (string) BadgeType::Reward->value,
        'reason' => 'Carried the winter campaign',
        'notes' => 'Private note for HR only',
        'amount' => '250.00',
    ]);
    Badge::factory()->for($this->employee)->create([
        'type' => (string) BadgeType::Salary_Increase->value,
        'reason' => 'Annual raise',
        'current_salary' => '400.00',
        'new_salary' => '450.00',
    ]);
});

test('the owner sees their own rewards and warnings without pay or private notes', function () {
    $profile = Livewire::actingAs($this->owner)->withQueryParams(['tab' => 'badges'])->test('hr::employees.show');

    $profile->assertSet('canViewBadges', true)
        ->assertSet('ownBadgesOnly', true)
        ->assertSet('tab', 'badges')
        ->assertSee('Carried the winter campaign')
        ->assertDontSee('Private note for HR only')
        ->assertDontSee('250.00')
        ->assertDontSee('Annual raise')
        ->assertDontSeeHtml('data-test="add-badge"');

    $badges = $profile->instance()->badges();

    expect($badges)->toHaveCount(1)
        ->and(array_keys($badges->first()->getAttributes()))->toEqualCanonicalizing(['id', 'employee_id', 'type', 'subtype', 'date', 'reason']);
});

test('a colleague without badges.view does not see them', function () {
    $stranger = User::factory()->create(['employee_id' => Employee::factory()->create()->id])->fresh();

    $profile = Livewire::actingAs($stranger)->withQueryParams(['tab' => 'badges'])->test('hr::employees.show', ['employee' => $this->employee]);

    $profile->assertSet('canViewBadges', false)
        ->assertSet('tab', 'overview')
        ->assertDontSee('Carried the winter campaign');

    expect($profile->instance()->badges())->toBeEmpty()
        ->and($profile->instance()->visibleTabs())->not->toContain('badges');
});
