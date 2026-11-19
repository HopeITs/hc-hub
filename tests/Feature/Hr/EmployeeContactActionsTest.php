<?php

use App\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Contracts\ContractNonRenewalMail;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Modules\Hr\Support\Settings\HrAccess;

/*
| How colleagues are written to from a profile: the work email opens a new
| message with the person's direct manager on CC, and a direct manager whose
| volunteer's contract renews by itself gets "Don't renew", a new email to
| the city's HR with the sender's own manager on CC. Nothing is sent.
|
| Chart: Boss (top) <- Mai (manager) <- Rami.
*/

beforeEach(function () {
    HrAccess::flush();

    $this->city = City::factory()->create(['slug' => 'ALP']);

    $person = function (string $email, ?Position $reportsTo, array $contract = []): array {
        $employee = Employee::factory()->create(['city_id' => $this->city->id, 'username' => strtok($email, '@')]);
        $position = Position::factory()->create(['city_id' => $this->city->id, 'direct_manager_id' => $reportsTo?->id]);
        $contract = Contract::factory()->for($employee)->for($position)->create(['is_active' => true, ...$contract]);
        $user = User::factory()->create(['employee_id' => $employee->id, 'email' => $email])->fresh();

        return ['employee' => $employee, 'position' => $position, 'contract' => $contract, 'user' => $user];
    };

    $this->boss = $person('boss@hcsyria.org', null);
    $this->mai = $person('mai@hcsyria.org', $this->boss['position']);
    $this->rami = $person('rami@hcsyria.org', $this->mai['position'], ['auto_renew' => true, 'end_date' => now()->addDays(20)->startOfDay()]);

    $hrEmployee = Employee::factory()->create(['city_id' => $this->city->id]);
    $this->hr = User::factory()->create(['employee_id' => $hrEmployee->id, 'email' => 'hr.aleppo@hcsyria.org']);
    $this->hr->assignRole(Role::findOrCreate('HR-ALP', 'web'));
});

test('the mailto link copies the manager and never the recipient twice', function () {
    $nobody = Employee::factory()->create();

    expect($nobody->mailtoUrl('boss@hcsyria.org'))->toBeNull()
        ->and($this->rami['employee']->mailtoUrl())->toBe('mailto:rami%40hcsyria.org')
        ->and($this->rami['employee']->mailtoUrl('mai@hcsyria.org'))->toBe('mailto:rami%40hcsyria.org?cc=mai%40hcsyria.org')
        ->and($this->rami['employee']->mailtoUrl('RAMI@hcsyria.org'))->toBe('mailto:rami%40hcsyria.org');
});

test('direct manager emails are read for a whole list, and a vacant manager seat gives none', function () {
    $orphan = Employee::factory()->create();
    Contract::factory()->for($orphan)->create(['is_active' => true]);

    expect(Employee::directManagerEmails([$this->rami['employee'], $this->mai['employee'], $this->boss['employee'], $orphan]))
        ->toBe([
            $this->rami['employee']->id => 'mai@hcsyria.org',
            $this->mai['employee']->id => 'boss@hcsyria.org',
        ]);

    // Mai's contract ends: Rami's manager seat is vacant.
    $this->mai['contract']->update(['is_active' => false]);

    expect(Employee::directManagerEmails([$this->rami['employee']]))->toBe([]);
});

test('the profile links the work email with the direct manager on CC', function () {
    Livewire::actingAs($this->boss['user'])->test('hr::employees.show', ['employee' => $this->rami['employee']])
        ->assertSeeHtml('href="mailto:rami%40hcsyria.org?cc=mai%40hcsyria.org"');
});

test("don't renew writes to the city's HR, with the sender's own manager on CC", function () {
    $url = app(ContractNonRenewalMail::class)->url($this->rami['contract'], $this->mai['user']);

    expect($url)->toStartWith('mailto:hr.aleppo@hcsyria.org?cc=boss%40hcsyria.org&subject=')
        ->and(rawurldecode($url))->toContain('Contract no.: '.$this->rami['contract']->id)
        ->and(rawurldecode($url))->toContain($this->mai['employee']->name);
});

test("don't renew falls back to national HR when the city has none", function () {
    $this->hr->syncRoles([Role::findOrCreate('HR-'.City::NATIONAL_SLUG, 'web')]);

    expect(app(ContractNonRenewalMail::class)->url($this->rami['contract'], $this->mai['user']))
        ->toStartWith('mailto:hr.aleppo@hcsyria.org?');
});

test("only the direct manager is offered don't renew, and only while the contract renews by itself", function () {
    $mail = app(ContractNonRenewalMail::class);

    expect($mail->isOfferedTo($this->rami['contract'], $this->mai['user']))->toBeTrue()
        ->and($mail->isOfferedTo($this->rami['contract'], $this->boss['user']))->toBeFalse()
        ->and($mail->isOfferedTo($this->rami['contract'], $this->rami['user']))->toBeFalse();

    Livewire::actingAs($this->mai['user'])->test('hr::employees.show', ['employee' => $this->rami['employee']])
        ->assertSeeHtml('data-test="dont-renew"');
    Livewire::actingAs($this->boss['user'])->test('hr::employees.show', ['employee' => $this->rami['employee']])
        ->assertDontSeeHtml('data-test="dont-renew"');

    $this->rami['contract']->update(['auto_renew' => false]);

    expect($mail->isOfferedTo($this->rami['contract']->fresh(), $this->mai['user']))->toBeFalse();
});

test("the renewal reminder opens the volunteer's profile", function () {
    $this->travelTo(now()->startOfDay()->addHours(9));
    $this->rami['contract']->update(['end_date' => app(DeliveryLedger::class)->today()->addDays(30)->toDateString()]);
    config(['contracts.map_by_id' => [$this->rami['contract']->contract_type_id => 'annual']]);

    Notification::fake();

    Artisan::call('hr:send-contract-expiration-warnings');

    Notification::assertSentTo($this->mai['user'], JourneyNotice::class, fn (JourneyNotice $notice): bool => $notice->toArray($this->mai['user'])['url'] === route('hr.employees.show', ['employee' => $this->rami['employee']->username]));
});
