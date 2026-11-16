<?php

use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Employees\ArchiveEmployee;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Tracking\Models\TaskAssignee;
use Spatie\Permission\Models\Permission;

/*
| An employee who leaves is archived instead of deleted (D2): the record and
| everything pointing at it stay, the employee becomes inactive and so their
| login loses access. Archiving takes the same right as deleting.
*/

/**
 * A login allowed to view and delete employees.
 */
function employeeArchiver(): User
{
    return User::factory()->create()
        ->givePermissionTo(Permission::findOrCreate('employees.view'), Permission::findOrCreate('employees.delete'))
        ->refresh();
}

test('archiving keeps the record, its contracts and its history, and marks who archived it and when', function () {
    $archiver = employeeArchiver();
    $this->actingAs($archiver);
    $this->freezeSecond();
    $employee = Employee::factory()->create();
    $contract = Contract::factory()->for($employee)->create();
    $leave = LeaveRequest::factory()->for(LeaveBalance::factory()->for($contract))->create();
    $assignment = TaskAssignee::factory()->create(['employee_id' => $employee->id]);

    expect(app(ArchiveEmployee::class)->handle($employee->fresh()))->toBeTrue();

    $employee = $employee->fresh();

    expect($employee->archived_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($employee->archived_by)->toBe($archiver->id)
        ->and($employee->is_active)->toBeFalse()
        ->and($employee->isArchived())->toBeTrue()
        ->and(Employee::query()->archived()->pluck('id')->all())->toBe([$employee->id])
        ->and(Contract::query()->whereKey($contract->id)->exists())->toBeTrue()
        ->and(LeaveRequest::query()->whereKey($leave->id)->exists())->toBeTrue()
        ->and(TaskAssignee::query()->whereKey($assignment->id)->exists())->toBeTrue();
});

test('the login of an archived employee loses access and stays linked', function () {
    $this->actingAs(employeeArchiver());
    $login = User::factory()->create();

    app(ArchiveEmployee::class)->handle($login->employee->fresh());

    expect($login->fresh()->hasLostAccess())->toBeTrue()
        ->and($login->fresh()->employee_id)->toBe($login->employee_id);
});

test('archiving an archived employee changes nothing', function () {
    $this->actingAs(employeeArchiver());
    $employee = Employee::factory()->create();
    app(ArchiveEmployee::class)->handle($employee->fresh());
    $archivedAt = $employee->fresh()->archived_at;

    $this->travel(1)->day();

    expect(app(ArchiveEmployee::class)->handle($employee->fresh()))->toBeFalse()
        ->and($employee->fresh()->archived_at?->toDateTimeString())->toBe($archivedAt?->toDateTimeString());
});

test('a login without the right to delete employees cannot archive one', function () {
    $this->actingAs(User::factory()->create()->givePermissionTo(Permission::findOrCreate('employees.update'))->refresh());
    $employee = Employee::factory()->create();

    expect(fn () => app(ArchiveEmployee::class)->handle($employee->fresh()))->toThrow(AuthorizationException::class)
        ->and($employee->fresh()->archived_at)->toBeNull()
        ->and($employee->fresh()->is_active)->toBeTrue();
});

test('the employee list archives through its confirmation dialog', function () {
    $archiver = employeeArchiver();
    $employee = Employee::factory()->create();
    Contract::factory()->for($employee)->create();

    Livewire::actingAs($archiver)->test('hr::employees.index')
        ->assertSeeHtml('data-test="archive-employee-'.$employee->id.'"')
        ->call('confirm', 'archive', $employee->id)
        ->assertSet('confirming', 'archive')
        ->call('runConfirmed');

    expect($employee->fresh()->isArchived())->toBeTrue()
        ->and(Employee::query()->whereKey($employee->id)->exists())->toBeTrue();
});

test('the employee list refuses to archive for a login that may not delete', function () {
    $viewer = User::factory()->create()->givePermissionTo(Permission::findOrCreate('employees.view'))->refresh();
    $employee = Employee::factory()->create();

    Livewire::actingAs($viewer)->test('hr::employees.index')
        ->assertDontSeeHtml('data-test="archive-employee-'.$employee->id.'"')
        ->call('confirm', 'archive', $employee->id)
        ->assertForbidden();

    expect($employee->fresh()->archived_at)->toBeNull();
});
