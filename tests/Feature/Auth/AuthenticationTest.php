<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Hr\Models\Employee;
use Spatie\Permission\Models\Role;

test('login screen can be rendered', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(__('security.email_or_username'));
});

test('users can authenticate with their email and password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('users can authenticate with their employee username', function () {
    $employee = Employee::factory()->create(['username' => 'rana.darwish']);
    $user = User::factory()->create(['employee_id' => $employee->id]);

    $this->post(route('login.store'), [
        'email' => 'rana.darwish',
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

test('the email is matched case-insensitively', function () {
    $user = User::factory()->create(['email' => 'rana@example.org']);

    $this->post(route('login.store'), [
        'email' => 'Rana@Example.org',
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

test('login always lands on the dashboard, never on the page visited before', function () {
    $user = User::factory()->create();

    $this->get(route('settings.password'));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));
});

test('logging in regenerates the session', function () {
    $user = User::factory()->create();

    $this->get(route('login'));
    $before = session()->getId();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    expect(session()->getId())->not->toBe($before);
});

test('logging in activates the first role and only that one', function () {
    $user = User::factory()->create();
    $first = Role::findOrCreate('HR-SYR', 'web');
    $second = Role::findOrCreate('super-admin', 'web');
    $user->assignRole([$first, $second]);
    DB::table('model_has_roles')->where('role_id', $second->id)->update(['is_active' => true]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    $active = DB::table('model_has_roles')
        ->where('model_id', $user->id)
        ->where('is_active', true)
        ->pluck('role_id')
        ->all();

    expect($active)->toBe([min($first->id, $second->id)])
        ->and(session('active_role_id'))->toBe(min($first->id, $second->id));
});

test('users whose employee is inactive cannot log in', function () {
    $user = User::factory()->create(['employee_id' => Employee::factory()->inactive()->create()->id]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('remember me issues a remember cookie', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => 'on',
    ]);

    $response->assertSessionHasNoErrors()->assertCookie(Auth::guard()->getRecallerName());
    $this->assertAuthenticatedAs($user);
});

test('without remember me no remember cookie is issued', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertCookieMissing(Auth::guard()->getRecallerName());

    $this->assertAuthenticatedAs($user);
});

test('users can not authenticate with a wrong password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
});

test('a wrong password shows the error under the password field', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $html = $this->get(route('login'))->getContent();

    $passwordStart = (int) strpos($html, 'id="password"');
    $emailMarkup = substr($html, (int) strpos($html, 'id="email"'), $passwordStart - (int) strpos($html, 'id="email"'));
    $passwordMarkup = substr($html, $passwordStart, (int) strpos($html, 'name="remember"') - $passwordStart);

    expect($passwordMarkup)->toContain(__('auth.failed'))
        ->and($emailMarkup)->not->toContain(__('auth.failed'));
});

test('an unknown identifier shows the error under the login field', function () {
    $this->post(route('login.store'), [
        'email' => 'nobody@example.org',
        'password' => 'whatever',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to the two factor challenge', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
});

test('users can logout and land on the localized home', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(LaravelLocalization::localizeUrl('/', 'en'));

    $this->assertGuest();
});

test('password reset is offered from the login page', function () {
    expect(Features::enabled(Features::resetPasswords()))->toBeTrue();

    $this->get(route('login'))->assertSee(route('password.request'), false);
});
