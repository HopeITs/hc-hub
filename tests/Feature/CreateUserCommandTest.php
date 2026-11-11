<?php

use App\Models\Role;
use Modules\Core\Models\User;

test('the command gives an existing account the given role', function () {
    Role::findOrCreate('MEAL', 'web');
    $employeeId = insertHrEmployee(firstNameEn: 'Amina', lastNameEn: 'Haddad');
    $userId = insertHrUser($employeeId, 'amina@example.org');

    $this->artisan('user:create', [
        '--email' => 'amina@example.org',
        '--role' => 'MEAL',
        '--no-interaction' => true,
    ])->assertSuccessful();

    $user = User::query()->findOrFail($userId);

    expect($user->roles->pluck('name')->all())->toBe(['MEAL'])
        ->and($user->employee_id)->toBe($employeeId)
        ->and(User::query()->count())->toBe(1);
});

test('an account gets the default Team Member role when none is given', function () {
    insertHrUser(insertHrEmployee(), 'dana@example.org');

    $this->artisan('user:create', [
        '--email' => 'dana@example.org',
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(User::query()->sole()->roles->pluck('name')->all())->toBe(['Team Member']);
});

test('an email no account uses becomes a new account with the given password and no employee', function () {
    $this->artisan('user:create', [
        '--email' => 'new@example.org',
        '--password' => 'Str0ng-Passw0rd-for-tests',
        '--no-interaction' => true,
    ])->assertSuccessful();

    $user = User::query()->sole();

    expect($user->email)->toBe('new@example.org')
        ->and($user->employee_id)->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->roles->pluck('name')->all())->toBe(['Team Member']);
});

test('the unverified flag leaves a new account\'s address unverified', function () {
    $this->artisan('user:create', [
        '--email' => 'new@example.org',
        '--password' => 'Str0ng-Passw0rd-for-tests',
        '--unverified' => true,
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(User::query()->sole()->email_verified_at)->toBeNull();
});

test('a new account needs a password', function () {
    $this->artisan('user:create', [
        '--email' => 'nobody@example.org',
        '--no-interaction' => true,
    ])->assertFailed();

    $this->assertDatabaseCount('users', 0);
});

test('an unknown role is rejected', function () {
    $userId = insertHrUser(insertHrEmployee(), 'dana@example.org');

    $this->artisan('user:create', [
        '--email' => 'dana@example.org',
        '--role' => 'not-a-real-role',
        '--no-interaction' => true,
    ])->assertFailed();

    expect(User::query()->findOrFail($userId)->roles)->toBeEmpty();
});

test('an account that already has access is rejected', function () {
    User::factory()->role('MEAL')->create(['email' => 'taken@example.org']);

    $this->artisan('user:create', [
        '--email' => 'taken@example.org',
        '--no-interaction' => true,
    ])->assertFailed();

    expect(User::query()->sole()->roles->pluck('name')->all())->toBe(['MEAL']);
});

test('a missing email is rejected', function () {
    $this->artisan('user:create', ['--no-interaction' => true])->assertFailed();

    $this->assertDatabaseCount('users', 0);
});
