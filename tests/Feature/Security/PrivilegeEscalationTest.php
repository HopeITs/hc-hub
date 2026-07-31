<?php

namespace Tests\Feature\Security;

use App\Livewire\AccessManagement\Index;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

/**
 * Delegated account administration must be able to narrow authority, never
 * widen it.
 *
 * HR-SYR appears in access-control.administrator_roles, so hasActivePermission()
 * grants it access-management.manage-employees automatically. Before these
 * checks that was enough to promote any account in the system to super-admin:
 * editing your own roles was blocked, but promoting a second account you
 * control reaches the same place one step removed.
 */
class PrivilegeEscalationTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_headquarters_hr_cannot_promote_another_user_to_super_admin(): void
    {
        $hq = $this->makeUser($this->makeCity('SYR', '000'), 'HR-SYR');
        $victim = $this->victim();
        $superAdmin = Role::findOrCreate('super-admin', 'web');

        Livewire::actingAs($hq)
            ->test(Index::class)
            ->call('selectUser', $victim->id)
            ->set('employeeRoleIds', [$superAdmin->id])
            ->set('employeeActiveRoleId', $superAdmin->id)
            ->call('saveEmployeeAccess')
            ->assertHasErrors('employeeRoleIds');

        $victim->refresh()->unsetRelation('roles');

        $this->assertFalse($victim->hasRole('super-admin'));
        $this->assertFalse($victim->isActiveAdministrator());
    }

    public function test_headquarters_hr_cannot_promote_another_user_to_its_own_administrator_role(): void
    {
        $hq = $this->makeUser($this->makeCity('SYR', '000'), 'HR-SYR');
        $victim = $this->victim();
        $hqRole = Role::findOrCreate('HR-SYR', 'web');

        // HR-SYR is itself an administrator role; sideways cloning is the same
        // escalation with a different name.
        Livewire::actingAs($hq)
            ->test(Index::class)
            ->call('selectUser', $victim->id)
            ->set('employeeRoleIds', [$hqRole->id])
            ->set('employeeActiveRoleId', $hqRole->id)
            ->call('saveEmployeeAccess')
            ->assertHasErrors('employeeRoleIds');

        $this->assertFalse($victim->refresh()->hasRole('HR-SYR'));
    }

    public function test_an_active_super_admin_can_still_assign_administrator_roles(): void
    {
        $admin = $this->makeUser($this->makeCity('SYR', '000'), 'super-admin');
        $victim = $this->victim();
        $hqRole = Role::findOrCreate('HR-SYR', 'web');

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('selectUser', $victim->id)
            ->set('employeeRoleIds', [$hqRole->id])
            ->set('employeeActiveRoleId', $hqRole->id)
            ->call('saveEmployeeAccess')
            ->assertHasNoErrors();

        $this->assertTrue($victim->refresh()->hasRole('HR-SYR'));
    }

    /**
     * The second escalation path: build an unprivileged role that happens to
     * hold every permission, then assign that instead.
     */
    public function test_a_delegated_administrator_cannot_grant_permissions_it_does_not_hold(): void
    {
        // Deliberately a *regional* delegate, not HR-SYR. HR-SYR sits in
        // administrator_roles, so hasActivePermission() reports it as holding
        // every permission — granting them onward is lateral delegation of
        // authority it already has, not escalation. A regional role given
        // manage-permissions is the case this rule exists for.
        $regional = $this->makeUser($this->makeCity('ALP', '001'), 'HR-ALP', [
            'access-management.view',
            'access-management.manage-permissions',
        ]);

        $customRole = Role::findOrCreate('regional-helper', 'web');
        $sensitive = Permission::findOrCreate('access-management.manage-roles', 'web');

        Livewire::actingAs($regional)
            ->test(Index::class)
            ->call('selectRole', $customRole->id)
            ->set('rolePermissionIds', [$sensitive->id])
            ->call('saveRolePermissions')
            ->assertHasErrors('rolePermissionIds');

        $this->assertFalse($customRole->fresh()->hasPermissionTo($sensitive));
    }

    public function test_a_delegated_administrator_cannot_grant_unheld_direct_permissions(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        // A regional role explicitly granted the ability to manage accounts.
        $regional = $this->makeUser($aleppo, 'HR-ALP', [
            'access-management.view',
            'access-management.manage-employees',
        ]);

        $employee = $this->makeEmployee($aleppo, 'colleague');
        $victim = User::factory()->create(['employee_id' => $employee->id]);
        $role = Role::findOrCreate('employee', 'web');
        $victim->assignRole($role);
        $victim->roles()->updateExistingPivot($role->id, ['is_active' => true]);

        $sensitive = Permission::findOrCreate('employees.delete', 'web');

        Livewire::actingAs($regional)
            ->test(Index::class)
            ->call('selectUser', $victim->id)
            ->set('employeeRoleIds', [$role->id])
            ->set('employeeActiveRoleId', $role->id)
            ->set('employeePermissionIds', [$sensitive->id])
            ->call('saveEmployeeAccess')
            ->assertHasErrors('employeePermissionIds');

        $this->assertFalse($victim->fresh()->hasDirectPermission('employees.delete'));
    }

    public function test_a_regional_administrator_cannot_reach_accounts_in_another_city(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        $regional = $this->makeUser($aleppo, 'HR-ALP', [
            'access-management.view',
            'access-management.manage-employees',
        ]);

        $outsider = $this->victim();

        // The account is resolved through the city-scoped query, so an id from
        // another city is simply not found.
        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($regional)
            ->test(Index::class)
            ->call('selectUser', $outsider->id);
    }

    private function victim(string $city = 'HMS'): User
    {
        $employee = $this->makeEmployee($this->makeCity($city, '002'), 'patsy');
        $user = User::factory()->create(['employee_id' => $employee->id]);

        $role = Role::findOrCreate('employee', 'web');
        $user->assignRole($role);
        $user->roles()->updateExistingPivot($role->id, ['is_active' => true]);

        return $user->fresh();
    }
}
