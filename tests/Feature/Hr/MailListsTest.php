<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\MailListMember;
use Modules\Hr\Support\MailLists\MailListImporter;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/*
| The organisation's mail lists, loaded from the sheet IT keeps, each with its
| members. Every member of staff reads and emails them; mail-lists.manage
| edits; one person holding mail-lists.confirm directly confirms additions
| made here once they are in the group at the mail provider.
*/

beforeEach(function () {
    Permission::findOrCreate('mail-lists.manage', 'web');
    Permission::findOrCreate('mail-lists.confirm', 'web');

    $this->importer = app(MailListImporter::class);
    $this->grid = [
        ['team@hcsyria.org', '', '', 'managers@hcsyria.org', '', ''],
        ['#', 'Emails', '', '#', 'Emails', ''],
        ['1', 'Rami@hcsyria.org', '', '1', 'sami@hcsyria.org', ''],
        ['2', 'lina@hcsyria.org', '', '2', 'not an email', ''],
        ['3', 'RAMI@hcsyria.org', '', 'it@hcsyria.org', '', ''],
        ['', '', '', '#', 'Emails', ''],
        ['', '', '', '1', 'omar@hcsyria.org', ''],
    ];
    $this->load = fn () => $this->importer->import($this->importer->parse($this->grid));

    $this->hrManager = function (): User {
        $user = User::factory()->role('HR-SYR')->create();
        $user->givePermissionTo('mail-lists.manage');

        return $user;
    };
});

test('the sheet is read one block of three columns per list, with lists stacked below', function () {
    $parsed = $this->importer->parse($this->grid);

    expect(array_column($parsed, 'address'))->toBe(['team@hcsyria.org', 'managers@hcsyria.org', 'it@hcsyria.org'])
        ->and($parsed[0]['name'])->toBe('Team')
        ->and($parsed[0]['emails'])->toBe(['rami@hcsyria.org', 'lina@hcsyria.org'])
        ->and($parsed[1]['emails'])->toBe(['sami@hcsyria.org'])
        ->and($parsed[2]['emails'])->toBe(['omar@hcsyria.org']);
});

test('the data file loads as members already in the group, and loading again replaces a list\'s members only', function () {
    $result = $this->importer->importFile(MailListImporter::defaultPath());

    expect($result['lists'])->toBeGreaterThan(20)
        ->and(MailListMember::query()->where('status', MailListMember::ACTIVE)->count())->toBe($result['members'])
        ->and(MailListMember::query()->pending()->count())->toBe(0);

    $this->importer->import([['address' => 'team@hcsyria.org', 'name' => 'Team', 'emails' => ['lina@hcsyria.org']]]);

    expect(MailList::query()->where('address', 'team@hcsyria.org')->sole()->members()->pluck('email')->all())->toBe(['lina@hcsyria.org'])
        ->and(MailList::query()->count())->toBe($result['lists']);
});

test('every member of staff sees and emails a list, but only a manager deletes one', function () {
    ($this->load)();
    $staff = User::factory()->create();

    Livewire::actingAs($staff)->test('hr::mail-lists.index')
        ->assertSee('managers@hcsyria.org')
        ->assertSeeHtml('href="mailto:team@hcsyria.org"')
        ->call('deleteList', MailList::query()->value('id'))
        ->assertForbidden();

    Livewire::actingAs(($this->hrManager)())->test('hr::mail-lists.index')
        ->call('deleteList', MailList::query()->where('address', 'team@hcsyria.org')->value('id'));

    expect(MailList::query()->count())->toBe(2);
});

test('a member added here is pending, and only the person holding the confirm permission clears it', function () {
    ($this->load)();
    $list = MailList::query()->where('address', 'team@hcsyria.org')->sole();
    $hr = ($this->hrManager)();
    $confirmer = User::factory()->create();
    $confirmer->givePermissionTo('mail-lists.confirm');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Livewire::actingAs($hr)->test('hr::mail-lists.index')
        ->call('openList', $list->id)
        ->set('newEmail', 'New.Hire@hcsyria.org')
        ->call('addMember')
        ->assertHasNoErrors()
        ->assertDontSeeHtml('data-mail-lists-pending');

    $member = $list->members()->where('email', 'new.hire@hcsyria.org')->sole();
    expect($member->isPending())->toBeTrue()
        ->and($member->added_by_user_id)->toBe($hr->id);

    Livewire::actingAs($hr)->test('hr::mail-lists.index')->call('confirmMember', $member->id)->assertForbidden();

    Livewire::actingAs($confirmer)->test('hr::mail-lists.index')
        ->assertSeeHtml('data-mail-lists-pending')
        ->assertSee('new.hire@hcsyria.org')
        ->call('confirmEmail', 'new.hire@hcsyria.org');

    $member->refresh();
    expect([$member->status, $member->confirmed_by_user_id])->toBe([MailListMember::ACTIVE, $confirmer->id])
        ->and($member->confirmed_at)->not->toBeNull();
});

test('a role holding the confirm permission does not make its members confirmers', function () {
    ($this->load)();
    $member = MailList::query()->firstOrFail()->members()->create(['email' => 'x@hcsyria.org', 'status' => MailListMember::PENDING]);
    $byRole = User::factory()->role('HR-SYR')->create();
    $byRole->roles()->first()->givePermissionTo('mail-lists.confirm');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Livewire::actingAs($byRole)->test('hr::mail-lists.index')->call('confirmMember', $member->id)->assertForbidden();
});

test('a new account on the organisation domain joins the all-staff list as pending', function () {
    $team = MailList::query()->create(['name' => 'Team', 'address' => MailList::ALL_STAFF_ADDRESS]);

    User::factory()->create(['email' => 'new.comer@hcsyria.org']);
    User::factory()->create(['email' => 'someone@gmail.com']);

    expect($team->members()->pluck('email')->all())->toBe(['new.comer@hcsyria.org'])
        ->and($team->members()->sole()->isPending())->toBeTrue();
});

test('search finds a list by its name or by a person on it, and a group narrows the page', function () {
    ($this->load)();
    $staff = User::factory()->create();

    Livewire::actingAs($staff)->test('hr::mail-lists.index')
        ->set('search', 'sami@')
        ->assertSee('managers@hcsyria.org')
        ->assertDontSee('team@hcsyria.org')
        ->set('search', '')
        ->set('category', 'functions')
        ->assertSee('it@hcsyria.org')
        ->assertDontSee('managers@hcsyria.org')
        ->set('category', 'nonsense')
        ->assertSee('managers@hcsyria.org');

    expect(MailList::categoryOf('hope.aleppo@hcsyria.org'))->toBe('cities')
        ->and(MailList::categoryOf('brand.new@hcsyria.org'))->toBe('other');
});

test('the members dialog hides addresses outside the organisation from staff, not from managers', function () {
    ($this->load)();
    $managers = MailList::query()->where('address', 'managers@hcsyria.org')->sole();
    $managers->members()->create(['email' => 'parent@gmail.com']);

    Livewire::actingAs(User::factory()->create())->test('hr::mail-lists.index')
        ->call('openList', $managers->id)
        ->assertSeeHtml('data-mail-list-panel')
        ->assertSee('sami@hcsyria.org')
        ->assertDontSee('parent@gmail.com');

    Livewire::actingAs(($this->hrManager)())->test('hr::mail-lists.index')
        ->call('openList', $managers->id)
        ->assertSee('parent@gmail.com');
});
