<?php

use App\Support\Desk\Destinations;
use App\Support\Desk\Panels;
use App\Support\Desk\Shortcuts;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Spatie\Permission\Models\Permission;

/*
| The Desk's catalogues keep their answers per viewer under a key made of the
| viewer's permissions and roles (AccessFingerprint). Reading that key walks
| every permission, so it is read once per user model and again only when the
| viewer's access changes.
*/

test('a page access rule follows every change of access made on the same user model in one request', function () {
    $user = userWithRole('Team Member');
    $destinations = app(Destinations::class);

    expect($destinations->allows($user, 'donors.index'))->toBeFalse()
        ->and(array_column($destinations->for($user), 'route'))->not->toContain('donors.index');

    $user->givePermissionTo(Permission::findOrCreate('donors.view'));

    expect($destinations->allows($user, 'donors.index'))->toBeTrue()
        ->and(array_column($destinations->for($user), 'route'))->toContain('donors.index');

    $user->revokePermissionTo('donors.view');

    expect($destinations->allows($user, 'donors.index'))->toBeFalse()
        ->and(array_column($destinations->for($user), 'route'))->not->toContain('donors.index');

    $user->roles->first()->givePermissionTo('donors.view');

    expect($destinations->allows($user, 'donors.index'))->toBeTrue();

    $user->syncRoles([]);

    expect($destinations->allows($user, 'donors.index'))->toBeFalse()
        ->and(array_column($destinations->for($user), 'route'))->not->toContain('donors.index');
});

test('the buttons a viewer is offered follow a change of access in the same request', function () {
    $user = userWithRole('Team Member');
    $shortcuts = app(Shortcuts::class);

    expect($shortcuts->for($user))->not->toHaveKey('g-donors');

    $user->givePermissionTo(Permission::findOrCreate('donors.view'));

    expect($shortcuts->for($user))->toHaveKey('g-donors');
});

test('the destinations and buttons are worded in the language asked in, not the one first read', function () {
    $user = userWithRole('Financial');
    $labelOf = fn (): string => collect(app(Destinations::class)->for($user))->firstWhere('route', 'transfers.index')['label'];
    $buttonOf = fn (): string => app(Shortcuts::class)->for($user)['g-transfers']['label'];

    app()->setLocale('en');
    $english = [$labelOf(), $buttonOf()];

    app()->setLocale('ar');

    expect([$labelOf(), $buttonOf()])->toBe([__('common.transfers'), __('common.transfers')])
        ->and($english)->not->toBe([$labelOf(), $buttonOf()]);
});

test('the viewer access key is read once for a whole desk of questions, and again after their access changes', function () {
    $seeded = userWithRole('Financial');

    $user = new class extends User
    {
        public int $accessReads = 0;

        protected $table = 'users';

        protected string $guard_name = 'web';

        public function getMorphClass(): string
        {
            return (new User)->getMorphClass();
        }

        public function getAllPermissions(): Collection
        {
            $this->accessReads++;

            return parent::getAllPermissions();
        }
    };
    $user->setRawAttributes($seeded->getAttributes(), true);
    $user->exists = true;

    $destinations = app(Destinations::class);

    expect($destinations->for($user))->not->toBeEmpty()
        ->and($destinations->sidebarFor($user))->not->toBeEmpty()
        ->and($destinations->allows($user, 'transfers.index'))->toBeTrue()
        ->and(app(Shortcuts::class)->for($user))->toHaveKey('g-transfers')
        ->and(app(Panels::class)->availableTo($user))->not->toBeEmpty()
        ->and($user->accessReads)->toBe(1);

    $user->givePermissionTo(Permission::findOrCreate('donors.view'));

    expect($destinations->allows($user, 'donors.index'))->toBeTrue()
        ->and($destinations->for($user))->not->toBeEmpty()
        ->and($user->accessReads)->toBe(2);
});
