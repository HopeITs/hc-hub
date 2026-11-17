<?php

use App\Enums\NoteVisibility;
use App\Models\Note;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    /** An HR viewer: may view and update any employee. */
    $this->hrViewer = function (): User {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('employees.view', 'web'), Permission::findOrCreate('employees.update', 'web'));

        return $user->fresh();
    };

    $this->employee = Employee::factory()->create();
    $this->admin = ($this->hrViewer)();
});

test('a note on a record is added private by default and shared when unticked', function () {
    Livewire::actingAs($this->admin)
        ->test('notes.panel', ['subjectType' => 'employee', 'subjectId' => $this->employee->id])
        ->set('body', 'Kept to myself')
        ->call('add')
        ->set('body', 'For everyone who can view')
        ->set('onlyMe', false)
        ->call('add')
        ->assertSee('For everyone who can view');

    expect(Note::query()->about($this->employee)->orderBy('id')->pluck('visibility')->all())
        ->toBe([NoteVisibility::Private, NoteVisibility::Context]);
});

test('an empty note is refused', function () {
    Livewire::actingAs($this->admin)->test('notes.panel')
        ->set('body', '   ')->call('add')->assertHasErrors('body');

    expect(Note::query()->count())->toBe(0);
});

test('someone who cannot view the record cannot open its panel', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('notes.panel', ['subjectType' => 'employee', 'subjectId' => $this->employee->id])
        ->assertForbidden();
});

test('a viewer sees context notes of others but not their private ones', function () {
    $other = ($this->hrViewer)();
    Note::factory()->about($this->employee)->create(['author_id' => $other->id, 'body' => 'Shared line']);
    Note::factory()->about($this->employee, NoteVisibility::Private)->create(['author_id' => $other->id, 'body' => 'Secret line']);

    Livewire::actingAs($this->admin)
        ->test('notes.panel', ['subjectType' => 'employee', 'subjectId' => $this->employee->id])
        ->assertSee('Shared line')
        ->assertDontSee('Secret line');
});

test('the author removes a note and nobody removes another private note', function () {
    $mine = Note::factory()->create(['author_id' => $this->admin->id]);
    $theirs = Note::factory()->create();

    Livewire::actingAs($this->admin)->test('notes.panel')
        ->call('remove', $mine->id)
        ->assertOk();
    expect(Note::query()->find($mine->id))->toBeNull();

    Livewire::actingAs($this->admin)->test('notes.panel')
        ->call('remove', $theirs->id)
        ->assertNotFound();
    expect(Note::query()->find($theirs->id))->not->toBeNull();
});

test('the desk card keeps the old panel id and lists only the viewer personal notes', function () {
    Note::factory()->create(['author_id' => $this->admin->id, 'body' => 'My own line']);
    Note::factory()->create(['body' => 'Someone else line']);

    Livewire::actingAs($this->admin)
        ->test('hr::desk.notes')
        ->assertSee('My own line')
        ->assertDontSee('Someone else line')
        ->call('add', 'Second line');

    expect(Note::query()->personalFor($this->admin->id)->count())->toBe(2);
});
