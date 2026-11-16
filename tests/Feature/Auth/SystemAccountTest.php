<?php

use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;

/*
| users.is_system marks an account with no person behind it (D2). Only such an
| account signs in without an active employee; any other account with no
| employee, or with an inactive or missing one, is refused (fail closed).
*/

test('a system account never loses access, even with an inactive employee', function () {
    $system = User::factory()->withoutEmployee()->create();
    $linked = User::factory()->create(['is_system' => true, 'employee_id' => Employee::factory()->inactive()->create()->id]);

    expect($system->is_system)->toBeTrue()
        ->and($system->hasLostAccess())->toBeFalse()
        ->and($linked->hasLostAccess())->toBeFalse();
});

test('an account that is not a system account and has no employee loses access', function () {
    $user = User::factory()->create(['employee_id' => null]);

    expect($user->fresh()->is_system)->toBeFalse()
        ->and($user->hasLostAccess())->toBeTrue();
});

test('an account whose employee is inactive or missing loses access, an active one keeps it', function () {
    $active = User::factory()->create();
    $inactive = User::factory()->create(['employee_id' => Employee::factory()->inactive()->create()->id]);

    expect($active->hasLostAccess())->toBeFalse()
        ->and($inactive->hasLostAccess())->toBeTrue()
        ->and((new User)->forceFill(['employee_id' => 999999999])->hasLostAccess())->toBeTrue();
});

test('the access scopes split accounts the way hasLostAccess does', function () {
    $system = User::factory()->withoutEmployee()->create();
    $active = User::factory()->create();
    $orphan = User::factory()->create(['employee_id' => null]);
    $inactive = User::factory()->create(['employee_id' => Employee::factory()->inactive()->create()->id]);

    expect(User::query()->withActiveAccount()->pluck('id')->sort()->values()->all())->toBe(collect([$system->id, $active->id])->sort()->values()->all())
        ->and(User::query()->withoutActiveAccount()->pluck('id')->sort()->values()->all())->toBe(collect([$orphan->id, $inactive->id])->sort()->values()->all());
});

test('a system account signs in and a non-system account with no employee is refused', function () {
    User::factory()->withoutEmployee()->create(['email' => 'robot@example.org']);
    User::factory()->create(['employee_id' => null, 'email' => 'orphan@example.org']);

    $this->post(route('login.store'), ['email' => 'orphan@example.org', 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post(route('login.store'), ['email' => 'robot@example.org', 'password' => 'password'])
        ->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});

test('a signed-in account that is not a system account and has no employee is signed out', function () {
    $user = User::factory()->create(['employee_id' => null]);

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});
