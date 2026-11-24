<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\ChecklistItem;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Folder;
use Modules\Hr\Models\Position;
use Spatie\Permission\Models\Permission;

/*
| The HR pickers that used to render every employee, login or position into
| the page now fetch a named, server-defined list (HrPickerScopes) from the
| picker endpoint: each list holds only its scoped rows, answers only a viewer
| who may open the form, and the save refuses an id outside it.
*/

/** A login holding these HR permissions. */
function pickerScopeUser(string ...$permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user->fresh();
}

/** An employee living in the city with a current contract in the department (activeNow needs an end date). */
function pickerScopeEmployee(int $cityId, int $departmentId, string $firstName): int
{
    $employeeId = insertHrEmployee($cityId, firstNameEn: $firstName, lastNameEn: 'Test');
    Contract::query()->whereKey(insertHrContract($employeeId, insertHrPosition($departmentId)))->update(['end_date' => now()->addYear()]);

    return $employeeId;
}

beforeEach(function () {
    $this->city = insertHrCity();
    insertHrCenter($this->city);
    $this->it = Department::factory()->create(['name' => 'IT', 'code' => 'IT'])->id;
    $this->media = Department::factory()->create(['name' => 'Media', 'code' => 'MD'])->id;
});

test('the default deliverers list holds only the city\'s service-department staff', function () {
    $deliverer = pickerScopeEmployee($this->city, $this->it, 'Rami');
    pickerScopeEmployee($this->city, $this->media, 'Nour');
    pickerScopeEmployee(insertHrCity('Homs', 'حمص'), $this->it, 'Hadi');

    $this->actingAs(pickerScopeUser('checklists.manage'))
        ->getJson(route('employees.picker-options', ['scope' => 'hr.default-deliverers', 'city' => $this->city]))
        ->assertOk()
        ->assertExactJson([['value' => (string) $deliverer, 'label' => 'Rami Test']]);
});

test('the archive users list holds the logins by id', function () {
    $owner = pickerScopeUser('archives.view', 'archives.share');
    $folder = Folder::query()->create(['name' => 'Contracts', 'user_id' => $owner->id]);
    $colleague = User::factory()->create(['email' => 'colleague@example.test']);

    $options = $this->actingAs($owner)
        ->getJson(route('employees.picker-options', ['scope' => 'hr.archive-users', 'folder' => $folder->id]))
        ->assertOk()
        ->json();

    expect(collect($options)->pluck('value')->sort()->values()->all())->toBe(collect([$owner->id, $colleague->id])->map(fn (int $id): string => (string) $id)->sort()->values()->all())
        ->and(collect($options)->firstWhere('value', (string) $colleague->id)['label'])->toContain('colleague@example.test');
});

test('the position managers list holds the active positions but the one edited', function () {
    $edited = Position::factory()->create(['is_active' => true]);
    $manager = Position::factory()->create(['is_active' => true]);
    $closed = Position::factory()->create(['is_active' => false]);

    $values = collect($this->actingAs(pickerScopeUser('positions.view', 'positions.update'))
        ->getJson(route('employees.picker-options', ['scope' => 'hr.position-managers', 'position' => $edited->id]))
        ->assertOk()
        ->json())->pluck('value');

    expect($values)->toContain((string) $manager->id)
        ->not->toContain((string) $edited->id)
        ->not->toContain((string) $closed->id);
});

test('a viewer without the page\'s permission is refused each list', function (string $scope, Closure $parameters) {
    $this->actingAs(pickerScopeUser('archives.view', 'positions.view'))
        ->getJson(route('employees.picker-options', ['scope' => $scope, ...$parameters()]))
        ->assertForbidden();
})->with([
    'default deliverers' => ['hr.default-deliverers', fn () => ['city' => test()->city]],
    'archive users' => ['hr.archive-users', fn () => ['folder' => Folder::query()->create(['name' => 'HR', 'user_id' => User::factory()->create()->id])->id]],
    'position managers' => ['hr.position-managers', fn () => ['position' => Position::factory()->create()->id]],
]);

test('an unknown scope is not found', function () {
    $this->actingAs(pickerScopeUser('checklists.manage'))
        ->getJson(route('employees.picker-options', ['scope' => 'hr.everyone']))
        ->assertNotFound();
});

test('a parameter the scope does not declare is rejected', function (string $scope) {
    $this->actingAs(pickerScopeUser('checklists.manage', 'archives.share', 'positions.create', 'positions.update'))
        ->getJson(route('employees.picker-options', ['scope' => $scope, 'department' => 1]))
        ->assertUnprocessable();
})->with(['hr.default-deliverers', 'hr.archive-users', 'hr.position-managers']);

test('saving a default deliverer outside the city\'s service departments is refused', function () {
    $outsider = pickerScopeEmployee($this->city, $this->media, 'Nour');
    $item = ChecklistItem::query()->create(['name' => 'Laptop', 'order' => 1]);

    Livewire::actingAs(pickerScopeUser('checklists.manage'))->test('hr::checklists.default-deliverables')
        ->call('openCreateModal')
        ->set('cityId', (string) $this->city)
        ->set('employeeId', (string) $outsider)
        ->set('itemId', (string) $item->id)
        ->call('save')
        ->assertHasErrors('employeeId');

    $this->assertDatabaseMissing('default_deliverables', ['employee_id' => $outsider]);
});

test('sharing a folder with an id that is not a login is refused', function () {
    $owner = pickerScopeUser('archives.view', 'archives.share');
    $folder = Folder::query()->create(['name' => 'Contracts', 'user_id' => $owner->id]);
    $missingUserId = User::query()->max('id') + 1000;

    Livewire::actingAs($owner)->test('hr::employees.archives')
        ->call('openShare', $folder->id)
        ->set('shareWith', [(string) $missingUserId])
        ->call('saveShare')
        ->assertHasErrors('shareWith');

    $this->assertDatabaseMissing('folder_user', ['folder_id' => $folder->id, 'user_id' => $missingUserId]);
});

test('saving a position with a closed position as manager is refused', function () {
    $position = Position::factory()->create(['direct_manager_id' => null]);
    $closed = Position::factory()->create(['is_active' => false]);

    Livewire::actingAs(pickerScopeUser('positions.view', 'positions.update'))->test('hr::positions.index')
        ->call('openPosition', $position->id)
        ->set('form.direct_manager_id', (string) $closed->id)
        ->call('savePosition')
        ->assertHasErrors('form.direct_manager_id');

    expect($position->fresh()->direct_manager_id)->toBeNull();
});
