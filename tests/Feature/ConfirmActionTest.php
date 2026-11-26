<?php

use App\Models\Note;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Spatie\Permission\Models\Permission;

test('a confirm action opens its own named dialog and never uses the browser prompt', function () {
    View::share('errors', new ViewErrorBag);

    $html = Blade::render('<x-ui.confirm-action title="Remove this?" confirm="removeThing(5)" confirm-label="Remove"><button type="button">Open</button></x-ui.confirm-action>');

    expect($html)->toContain('Open')
        ->and($html)->toContain('Remove this?')
        ->and($html)->toContain('wire:click="removeThing(5)"')
        ->and($html)->toContain('modal-shell-confirm')
        ->and($html)->toContain('confirm-'.substr(md5('removeThing(5)'), 0, 10))
        ->and($html)->toContain('.close()')
        ->and($html)->not->toContain('wire:confirm');
});

test('removing a note asks through the confirm dialog and the dialog action removes it', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('employees.view', 'web'), Permission::findOrCreate('employees.update', 'web'));
    $user = $user->fresh();

    $note = Note::factory()->create(['author_id' => $user->id]);

    Livewire::actingAs($user)->test('notes.panel')
        ->assertSeeHtml('data-test="remove-note"')
        ->assertSeeHtml('wire:click="remove('.$note->id.')"')
        ->assertDontSeeHtml('wire:confirm')
        ->call('remove', $note->id);

    expect(Note::query()->find($note->id))->toBeNull();
});
