<?php

namespace Tests\Feature;

use App\Enums\BadgeType;
use App\Livewire\Badges\BadgesModule;
use App\Livewire\Badges\BadgesTable;
use App\Livewire\Employee\EmployeeEdit;
use App\Models\Badge;
use App\Models\City;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BadgeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_badges_route_rejects_every_non_hr_active_role(): void
    {
        $city = $this->createCity('ALP', '001');
        $user = $this->createUserWithActiveRole('employee', $city);

        $this->actingAs($user)
            ->get(route('badges.index'))
            ->assertForbidden();
    }

    public function test_direct_badges_view_permission_is_honored_for_a_non_hr_user(): void
    {
        $city = $this->createCity('ALP', '001');
        $user = $this->createUserWithActiveRole('employee', $city);
        $user->givePermissionTo('badges.view');

        $this->actingAs($user)
            ->get(route('badges.index'))
            ->assertOk();

        Livewire::actingAs($user)
            ->test(BadgesTable::class)
            ->assertOk();
    }

    public function test_inactive_hr_assignment_does_not_grant_badges_access(): void
    {
        $city = $this->createCity('ALP', '001');
        $user = $this->createUserWithActiveRole('employee', $city);
        $inactiveHr = Role::findOrCreate('HR-ALP', 'web');

        $user->assignRole($inactiveHr);
        $user->roles()->updateExistingPivot($inactiveHr->id, ['is_active' => false]);
        $user->unsetRelation('roles');

        $this->assertFalse($user->activeRoleIs('HR'));

        $this->actingAs($user)
            ->get(route('badges.index'))
            ->assertForbidden();
    }

    public function test_city_hr_can_open_the_badges_page_and_only_query_its_city(): void
    {
        $aleppo = $this->createCity('ALP', '001');
        $homs = $this->createCity('HMS', '002');
        $user = $this->createUserWithActiveRole('HR-ALP', $aleppo);
        $aleppoBadge = $this->createBadge($this->createEmployee($aleppo, 'aleppo.employee'));
        $homsBadge = $this->createBadge($this->createEmployee($homs, 'homs.employee'));

        $this->actingAs($user)
            ->get(route('badges.index'))
            ->assertOk();

        $visibleIds = Badge::query()
            ->visibleTo($user)
            ->pluck('id');

        $this->assertTrue($visibleIds->contains($aleppoBadge->id));
        $this->assertFalse($visibleIds->contains($homsBadge->id));
    }

    public function test_city_hr_cannot_open_details_for_a_badge_in_another_city(): void
    {
        $aleppo = $this->createCity('ALP', '001');
        $homs = $this->createCity('HMS', '002');
        $user = $this->createUserWithActiveRole('HR-ALP', $aleppo);
        $foreignBadge = $this->createBadge(
            $this->createEmployee($homs, 'foreign.details'),
            'foreign salary',
        );

        Livewire::actingAs($user)
            ->test(BadgesModule::class)
            ->call('openBadgeModal', $foreignBadge->id)
            ->assertNotFound();
    }

    public function test_city_hr_cannot_directly_delete_a_badge_in_another_city(): void
    {
        $aleppo = $this->createCity('ALP', '001');
        $homs = $this->createCity('HMS', '002');
        $user = $this->createUserWithActiveRole('HR-ALP', $aleppo);
        $foreignBadge = $this->createBadge(
            $this->createEmployee($homs, 'foreign.delete'),
        );

        Livewire::actingAs($user)
            ->test(BadgesTable::class)
            ->call('delete', ['id' => $foreignBadge->id])
            ->assertNotFound();

        $this->assertDatabaseHas('badges', ['id' => $foreignBadge->id]);
    }

    public function test_city_hr_can_delete_a_badge_in_its_city(): void
    {
        $aleppo = $this->createCity('ALP', '001');
        $user = $this->createUserWithActiveRole('HR-ALP', $aleppo);
        $badge = $this->createBadge($this->createEmployee($aleppo, 'local.delete'));

        Livewire::actingAs($user)
            ->test(BadgesTable::class)
            ->call('delete', ['id' => $badge->id])
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('badges', ['id' => $badge->id]);
    }

    public function test_employee_form_cannot_delete_a_badge_belonging_to_another_employee(): void
    {
        $aleppo = $this->createCity('ALP', '001');
        $user = $this->createUserWithActiveRole('HR-ALP', $aleppo);
        $localEmployee = $this->createEmployee($aleppo, 'local.form');
        $localBadge = $this->createBadge($localEmployee);
        $otherBadge = $this->createBadge(
            $this->createEmployee($aleppo, 'other.form'),
        );

        Livewire::actingAs($user)
            ->test(EmployeeEdit::class, ['employee' => $localEmployee])
            ->set('badgeForm.badges.0.id', $otherBadge->id)
            ->call('removeBadge', 0)
            ->assertNotFound();

        $this->assertDatabaseHas('badges', ['id' => $localBadge->id]);
        $this->assertDatabaseHas('badges', ['id' => $otherBadge->id]);
    }

    public function test_employee_form_rejects_badge_actions_for_another_city(): void
    {
        $aleppo = $this->createCity('ALP', '001');
        $homs = $this->createCity('HMS', '002');
        $user = $this->createUserWithActiveRole('HR-ALP', $aleppo);
        $foreignEmployee = $this->createEmployee($homs, 'foreign.form');

        Livewire::actingAs($user)
            ->test(EmployeeEdit::class, ['employee' => $foreignEmployee])
            ->assertSet('canManageBadges', false)
            ->call('addBadge')
            ->assertForbidden();
    }

    public function test_employee_form_validates_badge_type_before_creating_it(): void
    {
        $aleppo = $this->createCity('ALP', '001');
        $user = $this->createUserWithActiveRole('HR-ALP', $aleppo);
        $employee = $this->createEmployee($aleppo, 'validation.form');

        Livewire::actingAs($user)
            ->test(EmployeeEdit::class, ['employee' => $employee])
            ->call('addBadge')
            ->set('badgeForm.badges.0.type', 'malicious-type')
            ->call('saveBadges')
            ->assertHasErrors(['badgeForm.badges.0.type']);

        $this->assertDatabaseCount('badges', 0);
    }

    public function test_other_users_cannot_see_badge_reasons_on_employee_profiles(): void
    {
        $city = $this->createCity('ALP', '001');
        $viewer = $this->createUserWithActiveRole('employee', $city);
        $target = $this->createEmployee($city, 'private.badges');
        $this->createBadge($target, 'CONFIDENTIAL-BADGE-REASON');

        $this->actingAs($viewer)
            ->get(route('employees.show', $target))
            ->assertOk()
            ->assertDontSee('CONFIDENTIAL-BADGE-REASON');
    }

    private function createCity(string $slug, string $code): City
    {
        return City::query()->forceCreate([
            'name_en' => $slug,
            'name_ar' => $slug,
            'slug' => $slug,
            'code' => $code,
            'is_active' => true,
        ]);
    }

    private function createEmployee(City $city, string $username): Employee
    {
        return Employee::query()->create([
            'city_id' => $city->id,
            'first_name_en' => 'Test',
            'first_name_ar' => 'اختبار',
            'last_name_en' => 'Employee',
            'last_name_ar' => 'موظف',
            'username' => $username,
            'is_active' => true,
        ]);
    }

    private function createUserWithActiveRole(string $roleName, City $city): User
    {
        $employee = $this->createEmployee(
            $city,
            str_replace(['-', '_'], '.', strtolower($roleName)).'.'.uniqid(),
        );
        $user = User::query()->create([
            'employee_id' => $employee->id,
            'email' => uniqid('badge-', true).'@example.test',
            'password' => 'password',
        ]);
        $role = Role::findOrCreate($roleName, 'web');

        $user->assignRole($role);
        $user->roles()->updateExistingPivot($role->id, ['is_active' => true]);
        $user->unsetRelation('roles');

        return $user->fresh();
    }

    private function createBadge(
        Employee $employee,
        string $reason = 'Private badge reason',
    ): Badge {
        return $employee->badges()->create([
            'type' => BadgeType::Salary_Increase,
            'currency' => 2,
            'date' => now(),
            'reason' => $reason,
            'current_salary' => 1000,
            'new_salary' => 1200,
            'notes' => 'Private HR notes',
        ]);
    }
}
