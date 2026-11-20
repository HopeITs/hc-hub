<?php

use App\Actions\Access\GrantScopedAccess;
use App\Actions\Access\RevokeScopedAccess;
use App\Models\AccessGrant;
use App\Support\Access\AccessScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Tracking\Models\Project;

/*
| Roadmap 4.4: a role or a permission given on one scope for a time. The
| project policy reads it first. A grant counts only between its dates and
| never once revoked; with the switch off it gives nothing.
*/

beforeEach(function () {
    config()->set('access-control.access_grants.enabled', true);
    $this->project = Project::factory()->create();
    $this->member = userWithRole('Team Member');
    $this->grant = fn (array $state = []) => AccessGrant::factory()
        ->permission('projects.update')
        ->on('project', $this->project->id)
        ->create(['user_id' => $this->member->id, ...$state]);
});

test('without a grant a Team Member cannot edit the project', function () {
    expect($this->member->can('update', $this->project))->toBeFalse();
});

test('an open grant on the project lets its holder edit that project and no other', function () {
    ($this->grant)();
    $other = Project::factory()->create();

    expect($this->member->can('update', $this->project))->toBeTrue()
        ->and($this->member->can('reassign', $this->project))->toBeTrue()
        ->and($this->member->can('delete', $this->project))->toBeFalse()
        ->and($this->member->can('update', $other))->toBeFalse()
        ->and(userWithRole('Team Member')->can('update', $this->project))->toBeFalse();
});

test('a grant counts only between its dates', function (string $at, bool $allowed) {
    ($this->grant)(['starts_at' => '2026-12-01 00:00:00', 'ends_at' => '2026-12-31 00:00:00']);

    $this->travelTo($at);

    expect($this->member->can('update', $this->project))->toBe($allowed);
})->with([
    'the day before it starts' => ['2026-11-30 23:59:59', false],
    'the moment it starts' => ['2026-12-01 00:00:00', true],
    'in the middle' => ['2026-12-15 12:00:00', true],
    'the second before it ends' => ['2026-12-30 23:59:59', true],
    'the moment it ends' => ['2026-12-31 00:00:00', false],
    'long after it ended' => ['2027-06-01 00:00:00', false],
]);

test('an ended grant stops a real edit on the projects page', function () {
    ($this->grant)(['ends_at' => now()->addDay()]);
    $edit = fn () => Livewire::actingAs($this->member)->test('tracking::projects.index')->call('openEditModal', $this->project->id);

    $edit()->assertOk();

    $this->travel(2)->days();
    app(AccessScope::class)->forget();

    $edit()->assertForbidden();
});

test('a revoked grant gives nothing and its row stays', function () {
    $grant = ($this->grant)();
    $this->actingAs(userWithRole('Super Admin'));

    expect(app(RevokeScopedAccess::class)->handle($grant))->toBeTrue()
        ->and($this->member->can('update', $this->project))->toBeFalse()
        ->and(DB::table('access_grants')->where('id', $grant->id)->value('revoked_at'))->not->toBeNull()
        ->and(app(RevokeScopedAccess::class)->handle($grant->fresh()))->toBeFalse();
});

test('a grant on the program or the city covers the projects under it', function (string $scopeType, string $column) {
    AccessGrant::factory()->permission('projects.update')->on($scopeType, $this->project->{$column})->create(['user_id' => $this->member->id]);
    $elsewhere = Project::factory()->create();

    expect($this->member->can('update', $this->project))->toBeTrue()
        ->and($this->member->can('update', $elsewhere))->toBeFalse();
})->with([
    'program' => ['program', 'program_id'],
    'city' => ['city', 'city_id'],
]);

test('a granted role gives the permissions that role holds, on that scope only', function () {
    userWithRole('Managers');
    AccessGrant::factory()->role('Managers')->on('project', $this->project->id)->create(['user_id' => $this->member->id]);

    expect($this->member->can('update', $this->project))->toBeTrue()
        ->and($this->member->can('delete', $this->project))->toBeTrue()
        ->and($this->member->can('projects.update'))->toBeFalse()
        ->and($this->member->hasRole('Managers'))->toBeFalse();
});

test('with the switch off a grant gives nothing', function () {
    ($this->grant)();
    config()->set('access-control.access_grants.enabled', false);

    expect($this->member->can('update', $this->project))->toBeFalse();
});

test('whoever manages permissions grants access with an end date', function () {
    $this->actingAs($admin = userWithRole('Super Admin'));

    $grant = app(GrantScopedAccess::class)->handle($this->member, [
        'permission' => 'projects.update',
        'scope_type' => 'project',
        'scope_id' => $this->project->id,
        'ends_at' => now()->addWeek()->toDateTimeString(),
        'reason' => 'Covering the owner on leave',
    ]);

    expect($grant->granted_by)->toBe($admin->id)
        ->and($grant->ends_at)->not->toBeNull()
        ->and($this->member->can('update', $this->project))->toBeTrue();
});

test('someone who does not manage permissions cannot grant access', function () {
    $this->actingAs(userWithRole('Managers'));

    expect(fn () => app(GrantScopedAccess::class)->handle($this->member, [
        'permission' => 'projects.update', 'scope_type' => 'project', 'scope_id' => $this->project->id,
    ]))->toThrow(AuthorizationException::class);

    expect(AccessGrant::query()->count())->toBe(0);
});

test('a grant is refused when it is malformed', function (array $input, string $field) {
    $this->actingAs(userWithRole('Super Admin'));

    expect(fn () => app(GrantScopedAccess::class)->handle($this->member, [
        'permission' => 'projects.update', 'scope_type' => 'project', 'scope_id' => $this->project->id, ...$input,
    ]))->toThrow(fn (ValidationException $exception) => expect($exception->errors())->toHaveKey($field));
})->with([
    'an end before its start' => [['starts_at' => '2026-12-10 00:00:00', 'ends_at' => '2026-12-01 00:00:00'], 'ends_at'],
    'an unknown scope' => [['scope_type' => 'galaxy'], 'scope_type'],
    'an unknown permission' => [['permission' => 'projects.fly'], 'permission'],
    'a role and a permission together' => [['role' => 'Team Member'], 'role'],
]);

test('the table refuses a row with both a role and a permission, or neither', function (array $state) {
    expect(fn () => DB::table('access_grants')->insert([
        'user_id' => $this->member->id, 'scope_type' => 'project', 'scope_id' => $this->project->id, 'role_id' => null, 'permission_id' => null, ...$state,
    ]))->toThrow(QueryException::class);
})->with([
    'neither' => [[]],
    'both' => [fn () => [
        'role_id' => DB::table('roles')->value('id'),
        'permission_id' => DB::table('permissions')->value('id'),
    ]],
]);

test('the access_grants migration rolls back and comes back', function () {
    $migration = 'database/migrations/2026_11_20_100200_create_access_grants_table.php';

    Artisan::call('migrate:rollback', ['--path' => $migration]);
    expect(Schema::hasTable('access_grants'))->toBeFalse();

    Artisan::call('migrate', ['--path' => $migration]);
    expect(Schema::hasTable('access_grants'))->toBeTrue();
});
