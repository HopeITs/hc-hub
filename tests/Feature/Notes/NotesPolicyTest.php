<?php

use App\Enums\NoteVisibility;
use App\Models\Note;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Spatie\Permission\Models\Permission;

/*
| NotePolicy: a private note is its author's alone, a context note follows
| the right to view its subject (any module), and a personal note has no
| subject at all.
*/

beforeEach(function () {
    /** An HR viewer: may view and update any employee. */
    $this->hrViewer = function (): User {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('employees.view', 'web'), Permission::findOrCreate('employees.update', 'web'));

        return $user->fresh();
    };

    $this->employee = Employee::factory()->create();
    $this->author = ($this->hrViewer)();
    $this->stranger = User::factory()->create();
    $this->self = User::factory()->create(['employee_id' => $this->employee->id]);
});

test('a private note is read only by its author, even by someone who can view the subject', function () {
    $note = Note::factory()->about($this->employee, NoteVisibility::Private)->create(['author_id' => $this->author->id]);
    $otherAdmin = ($this->hrViewer)();

    expect($this->author->can('view', $note))->toBeTrue()
        ->and($otherAdmin->can('view', $note))->toBeFalse()
        ->and($this->self->can('view', $note))->toBeFalse();
});

test('a context note follows the right to view the employee, across modules', function () {
    $note = Note::factory()->about($this->employee)->create(['author_id' => $this->author->id]);
    $trackingUser = User::factory()->role('Financial')->create();

    expect($this->author->can('view', $this->employee))->toBeTrue()
        ->and($this->author->can('view', $note))->toBeTrue()
        ->and($this->self->can('view', $note))->toBeTrue()
        ->and($trackingUser->can('view', $this->employee))->toBeFalse()
        ->and($trackingUser->can('view', $note))->toBeFalse()
        ->and($this->stranger->can('view', $note))->toBeFalse();
});

test('adding a note needs the right to view its subject, a personal one needs nothing', function () {
    expect($this->stranger->can('create', [Note::class, $this->employee]))->toBeFalse()
        ->and($this->self->can('create', [Note::class, $this->employee]))->toBeTrue()
        ->and($this->author->can('create', [Note::class, $this->employee]))->toBeTrue()
        ->and($this->stranger->can('create', Note::class))->toBeTrue();
});

test('only the author removes a private or personal note', function () {
    $personal = Note::factory()->create(['author_id' => $this->stranger->id]);
    $private = Note::factory()->about($this->employee, NoteVisibility::Private)->create(['author_id' => $this->stranger->id]);

    expect($this->stranger->can('delete', $personal))->toBeTrue()
        ->and($this->author->can('delete', $personal))->toBeFalse()
        ->and($this->stranger->can('delete', $private))->toBeTrue()
        ->and($this->author->can('delete', $private))->toBeFalse();
});

test('a context note is removed by its author or by whoever can update the subject', function () {
    $note = Note::factory()->about($this->employee)->create(['author_id' => $this->stranger->id]);

    expect($this->stranger->can('delete', $note))->toBeTrue()
        ->and($this->author->can('update', $this->employee))->toBeTrue()
        ->and($this->author->can('delete', $note))->toBeTrue()
        ->and($this->self->can('delete', $note))->toBeFalse();
});

test('a personal note is never visible to another user', function () {
    $note = Note::factory()->create(['author_id' => $this->stranger->id]);

    expect($this->stranger->can('view', $note))->toBeTrue()
        ->and($this->author->can('view', $note))->toBeFalse();
});
