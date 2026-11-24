<?php

use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;

/*
| "Email a list": every list link opens a new message To the list with the
| viewer's direct manager on Cc, and the team chart offers a menu of lists
| (the ones for the filtered city first) instead of one fixed address.
*/

beforeEach(function () {
    HrAccess::flush();

    $this->city = City::factory()->create();
    $this->list = MailList::query()->create(['name' => 'Team', 'address' => 'team@hcsyria.org', 'position' => 1, 'rule_type' => 'all_staff']);

    $this->staffOn = function (string $key, Position $position): User {
        $employee = Employee::factory()->create(['city_id' => $this->city->id, 'username' => $key]);
        Contract::factory()->for($employee)->for($position)->create(['is_active' => true]);

        return User::factory()->create(['employee_id' => $employee->id, 'email' => $key.'@hcsyria.org'])->fresh();
    };
});

test('a list opens a message to the list with the viewers manager on Cc', function () {
    $bossPosition = Position::factory()->create(['city_id' => $this->city->id]);
    ($this->staffOn)('boss', $bossPosition);
    $me = ($this->staffOn)('me', Position::factory()->create(['city_id' => $this->city->id, 'direct_manager_id' => $bossPosition->id]));
    $lone = ($this->staffOn)('lone', Position::factory()->create(['city_id' => $this->city->id]));

    $this->actingAs($me);
    expect($this->list->mailto())->toBe('mailto:team@hcsyria.org?cc=boss@hcsyria.org');

    $this->actingAs($lone);
    expect($this->list->mailto())->toBe('mailto:team@hcsyria.org');
});

test('the suggestions for a place come first, and every list is grouped for the menu', function () {
    $aleppo = MailList::query()->create(['name' => 'Aleppo', 'address' => 'hope.aleppo@hcsyria.org', 'position' => 2, 'rule_type' => 'custom', 'rule_city_id' => $this->city->id]);

    expect(MailList::suggestionsFor($this->city->id, null, null)->pluck('id')->all())->toBe([$aleppo->id])
        ->and(MailList::suggestionsFor(null, null, null))->toBeEmpty()
        ->and(MailList::menuGroups()->keys()->all())->toBe(['organisation', 'cities']);
});

test('the team chart offers a menu of lists, the filtered city first', function () {
    MailList::query()->create(['name' => 'Aleppo', 'address' => 'hope.aleppo@hcsyria.org', 'position' => 2, 'rule_type' => 'custom', 'rule_city_id' => $this->city->id]);

    $viewer = User::factory()->create();
    $viewer->givePermissionTo(Permission::findOrCreate('positions.view', 'web'));
    $viewer = $viewer->fresh();

    Livewire::actingAs($viewer)->test('hr::positions.team')
        ->assertSeeHtml('data-mail-list-menu')
        ->assertSeeHtml('href="mailto:team@hcsyria.org"')
        ->assertSeeHtml('href="mailto:hope.aleppo@hcsyria.org"')
        ->assertDontSeeHtml('data-mail-list-suggested')
        ->set('city', (string) $this->city->id)
        ->assertSeeHtml('data-mail-list-suggested');
});
