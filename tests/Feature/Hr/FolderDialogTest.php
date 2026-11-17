<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Spatie\Permission\Models\Permission;

/*
| The New folder dialog says whose folder it creates: in My folders it is the
| viewer's own, seen by nobody else until shared.
*/

test('the new folder dialog says the folder will be the viewer\'s own', function () {
    $user = User::factory()->create();
    foreach (['archives.view', 'archives.create'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    Livewire::actingAs($user->fresh())->test('hr::employees.archives')
        ->call('openFolderModal')
        ->assertSeeHtml('data-test="new-folder-whose"')
        ->assertSee(__('hr_employees.archives.new_folder_mine'))
        ->assertDontSee(__('hr_employees.archives.new_folder_shared'));
});
