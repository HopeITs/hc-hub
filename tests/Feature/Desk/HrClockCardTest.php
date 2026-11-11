<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Support\Desk\Cards\ClockCard;

/*
| The HR clock card: Damascus time, which the browser keeps turning
| (resources/js/desk-clock.js reads data-desk-clock), and the colleagues seen
| in the last five minutes (users.last_seen_at, stamped by RecordPresence).
*/

test('the clock card draws Damascus time for the browser to keep turning', function () {
    $this->travelTo('2026-11-11 07:05:00 UTC');

    Livewire::actingAs(User::factory()->create(['employee_id' => insertHrEmployee()]))
        ->test('hr::desk.card', ['card' => 'hr-clock'])
        ->assertSeeHtml('data-desk-clock >10:05</dd>');
});

test('the clock card lists a colleague as online once they have been seen', function () {
    $viewer = User::factory()->role('Super Admin')->create(['employee_id' => insertHrEmployee()]);
    $colleague = User::factory()->role('Team Member')->create(['employee_id' => insertHrEmployee(firstNameEn: 'Lina', lastNameEn: 'Saleh')]);

    expect((new ClockCard)->rows($viewer))->toBeEmpty();

    $this->actingAs($colleague)->get(route('dashboard'))->assertOk();

    expect((new ClockCard)->rows($viewer)->pluck('title')->all())->toBe(['Lina Saleh']);
});
