<?php

use App\Enums\NoteVisibility;
use App\Models\Note;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Models\Donor;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Spatie\Permission\Models\Permission;

/*
| Notes on every record page (4.14): the one Notes panel sits on the project,
| proposal, program, donor and contract pages as it does on the
| employee page. NotePolicy decides the same way everywhere: whoever may view
| the record adds notes and reads the shared ones, a private note stays its
| author's, and someone who may not view the record gets no panel.
*/

/**
 * Someone holding one module's view right (and its update right when asked).
 * Contracts are also held to HR's reach, so their viewer carries an HR role.
 */
function recordViewer(string $module, bool $editor = false): User
{
    $user = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);
    $user->givePermissionTo(Permission::findOrCreate($module.'.view', 'web'));

    if ($editor) {
        $user->givePermissionTo(Permission::findOrCreate($module.'.update', 'web'));
    }

    if ($module === 'contracts') {
        $user->assignRole(Role::findOrCreate('HR-SYR', 'web'));
    }

    return $user->fresh();
}

dataset('record contexts', [
    'a project' => ['project', 'projects', fn (): Model => Project::factory()->create()],
    'a proposal' => ['proposal', 'proposals', fn (): Model => Proposal::factory()->create()],
    'a program' => ['program', 'programs', fn (): Model => Program::factory()->create()],
    'a donor' => ['donor', 'donors', fn (): Model => Donor::factory()->create()],
    'a contract' => ['contract', 'contracts', fn (): Model => Contract::factory()->for(Employee::factory())->create(['is_active' => true])],
]);

test('whoever may view the record adds a note on it, private unless they share it', function (string $type, string $module, Closure $record) {
    $subject = $record();

    Livewire::actingAs(recordViewer($module))
        ->test('notes.panel', ['subjectType' => $type, 'subjectId' => $subject->getKey()])
        ->assertOk()
        ->set('body', 'Kept to myself')
        ->call('add')
        ->set('body', 'For everyone who can view')
        ->set('onlyMe', false)
        ->call('add')
        ->assertHasNoErrors();

    expect(Note::query()->about($subject)->orderBy('id')->pluck('visibility')->all())
        ->toBe([NoteVisibility::Private, NoteVisibility::Context]);
})->with('record contexts');

test('another viewer of the record reads its shared notes and never a private one', function (string $type, string $module, Closure $record) {
    $subject = $record();
    $author = recordViewer($module);
    Note::factory()->about($subject)->create(['author_id' => $author->id, 'body' => 'Shared line']);
    Note::factory()->about($subject, NoteVisibility::Private)->create(['author_id' => $author->id, 'body' => 'Secret line']);

    Livewire::actingAs(recordViewer($module))
        ->test('notes.panel', ['subjectType' => $type, 'subjectId' => $subject->getKey()])
        ->assertSee('Shared line')
        ->assertDontSee('Secret line');

    Livewire::actingAs($author)
        ->test('notes.panel', ['subjectType' => $type, 'subjectId' => $subject->getKey()])
        ->assertSee(['Shared line', 'Secret line']);
})->with('record contexts');

test('someone who may not view the record gets no panel and reads none of its notes', function (string $type, string $module, Closure $record) {
    $subject = $record();
    $shared = Note::factory()->about($subject)->create(['author_id' => recordViewer($module)->id]);
    $outsider = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);

    Livewire::actingAs($outsider)
        ->test('notes.panel', ['subjectType' => $type, 'subjectId' => $subject->getKey()])
        ->assertForbidden();

    expect($outsider->can('view', $shared))->toBeFalse()
        ->and($outsider->can('create', [Note::class, $subject]))->toBeFalse();
})->with('record contexts');

test('a shared note is removed by its author or by someone who may edit the record, not by another viewer', function (string $type, string $module, Closure $record) {
    $subject = $record();
    $note = Note::factory()->about($subject)->create(['author_id' => recordViewer($module)->id]);

    Livewire::actingAs(recordViewer($module))
        ->test('notes.panel', ['subjectType' => $type, 'subjectId' => $subject->getKey()])
        ->call('remove', $note->id)
        ->assertForbidden();
    expect(Note::query()->find($note->id))->not->toBeNull();

    Livewire::actingAs(recordViewer($module, editor: true))
        ->test('notes.panel', ['subjectType' => $type, 'subjectId' => $subject->getKey()])
        ->call('remove', $note->id)
        ->assertOk();
    expect(Note::query()->find($note->id))->toBeNull();
})->with('record contexts');

test('the record page carries the Notes panel of its own record', function (string $type, string $route, Closure $record) {
    $subject = $record();
    $admin = userWithRole('Super Admin', insertHrEmployee());

    $this->actingAs($admin)->get(route($route, $subject instanceof Employee ? ['employee' => $subject->username] : $subject))
        ->assertOk()
        ->assertSee('data-record-notes="'.$type.'"', false)
        ->assertSee('data-test="notes-panel"', false);
})->with([
    'a project' => ['project', 'projects.show', fn (): Model => Project::factory()->create()],
    'a proposal' => ['proposal', 'proposals.show', fn (): Model => Proposal::factory()->create()],
    'a program' => ['program', 'programs.show', fn (): Model => Program::factory()->create()],
    'a donor' => ['donor', 'donors.show', fn (): Model => Donor::factory()->create()],
    'a contract' => ['contract', 'hr.contracts.show', fn (): Model => Contract::factory()->for(Employee::factory())->create(['is_active' => true])],
    'an employee' => ['employee', 'hr.employees.show', fn (): Model => Employee::factory()->create(['username' => 'lina.saleh'])],
]);

test('a record page shows no Notes panel to someone who may not view the record', function () {
    $project = Project::factory()->create();
    $outsider = User::factory()->create();

    $this->actingAs($outsider);

    expect(trim((string) Blade::render('<x-notes.record :subject="$subject" />', ['subject' => $project])))->toBe('');
});
