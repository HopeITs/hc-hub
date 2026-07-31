<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_city_scoped_hr_cannot_update_an_employee_in_another_city(): void
    {
        $aleppo = $this->city('ALP', '001');
        $homs = $this->city('HMS', '002');
        $user = $this->userInCity($aleppo);
        $permission = Permission::findOrCreate('employees.update', 'web');
        $role = Role::findOrCreate('HR-ALP', 'web');
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $user->roles()->updateExistingPivot($role->id, ['is_active' => true]);
        $user->unsetRelation('roles');

        $localEmployee = $this->employee($aleppo, 'local');
        $remoteEmployee = $this->employee($homs, 'remote');

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('update', $localEmployee));
        $this->assertFalse(Gate::allows('update', $remoteEmployee));
    }

    public function test_private_employee_documents_require_owner_or_scoped_hr_access(): void
    {
        Storage::fake('local');
        $city = $this->city('TST', '999');
        $employee = $this->employee($city, 'document-owner');
        $owner = User::factory()->create(['employee_id' => $employee->id]);
        $media = $employee
            ->addMedia(UploadedFile::fake()->create('identity.pdf', 10, 'application/pdf'))
            ->toMediaCollection('attachments', 'local');

        $response = $this->actingAs($owner)
            ->get(route('employee-files.download', $media));

        $response->assertOk();
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $outsider = User::factory()->create();
        $this->actingAs($outsider)
            ->get(route('employee-files.download', $media))
            ->assertForbidden();
    }

    public function test_digital_id_uses_an_unguessable_token_and_hides_inactive_employees(): void
    {
        $city = $this->city('DIG', '998');
        $employee = $this->employee($city, 'digital-id');

        $this->get('/ID/'.$employee->username)->assertNotFound();
        $this->get(route('digital-id.show', $employee->digital_id_token))->assertOk();

        $employee->update(['is_active' => false]);
        $this->get(route('digital-id.show', $employee->digital_id_token))->assertNotFound();
    }

    private function city(string $slug, string $code): City
    {
        return City::query()->forceCreate([
            'name_en' => $slug,
            'name_ar' => $slug,
            'slug' => $slug,
            'code' => $code,
            'is_active' => true,
        ]);
    }

    private function employee(City $city, string $username): Employee
    {
        return Employee::query()->create([
            'city_id' => $city->id,
            'first_name_en' => 'Security',
            'first_name_ar' => 'أمان',
            'last_name_en' => 'Test',
            'last_name_ar' => 'اختبار',
            'username' => $username,
            'is_active' => true,
        ]);
    }

    private function userInCity(City $city): User
    {
        $employee = $this->employee($city, 'security-user-'.$city->id);

        return User::factory()->create(['employee_id' => $employee->id]);
    }
}
