<?php

namespace Tests\Feature;

use App\Enums\ContractType as ContractTypeEnum;
use App\Livewire\Employee\EmployeeCreate;
use App\Models\Center;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class EmployeeCreateDraftContractTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_saving_personal_info_shows_the_draft_position_prompt_instead_of_redirecting(): void
    {
        $fixtures = $this->makeFixtures();

        Livewire::actingAs($fixtures['hr'])
            ->test(EmployeeCreate::class)
            ->set('infoForm.first_name_en', 'Test')->set('infoForm.first_name_ar', 'اختبار')
            ->set('infoForm.last_name_en', 'Person')->set('infoForm.last_name_ar', 'شخص')
            ->set('infoForm.father_name_en', 'Father')->set('infoForm.father_name_ar', 'أب')
            ->set('infoForm.mother_name_en', 'Mother')->set('infoForm.mother_name_ar', 'أم')
            ->set('infoForm.gender', 1)
            ->set('infoForm.marital_status', 1)
            ->set('infoForm.nationality', 1)
            ->set('infoForm.national_number', '12345678901')
            ->set('infoForm.city_id', $fixtures['city']->id)
            ->set('infoForm.username', 'draft.prompt.employee')
            ->set('infoForm.create_account', false)
            ->call('saveInformation')
            ->assertHasNoErrors()
            ->assertSet('showDraftPrompt', true)
            ->assertNoRedirect();

        $this->assertDatabaseHas('employees', ['username' => 'draft.prompt.employee']);
        $this->assertSame(0, Contract::count());
    }

    /**
     * saveInformation() is bound to a Save/Next button on more than one tab
     * (Profile and Contact both call it on the same underlying Form object),
     * but the draft-position prompt only lives in the Profile tab's view.
     * Regression coverage for a bug caught in manual browser verification:
     * saving from Contact left the user stuck on Contact with a success
     * toast and no visible next step at all.
     */
    public function test_saving_from_the_contact_tab_still_surfaces_the_draft_prompt(): void
    {
        $fixtures = $this->makeFixtures();

        Livewire::actingAs($fixtures['hr'])
            ->test(EmployeeCreate::class)
            ->set('activeTab', 'contact')
            ->set('infoForm.first_name_en', 'Test')->set('infoForm.first_name_ar', 'اختبار')
            ->set('infoForm.last_name_en', 'Person')->set('infoForm.last_name_ar', 'شخص')
            ->set('infoForm.father_name_en', 'Father')->set('infoForm.father_name_ar', 'أب')
            ->set('infoForm.mother_name_en', 'Mother')->set('infoForm.mother_name_ar', 'أم')
            ->set('infoForm.gender', 1)
            ->set('infoForm.marital_status', 1)
            ->set('infoForm.nationality', 1)
            ->set('infoForm.national_number', '12345678902')
            ->set('infoForm.city_id', $fixtures['city']->id)
            ->set('infoForm.username', 'draft.via.contact.tab')
            ->set('infoForm.create_account', false)
            ->call('saveInformation')
            ->assertHasNoErrors()
            ->assertSet('showDraftPrompt', true)
            ->assertSet('activeTab', 'information');
    }

    public function test_reserving_a_position_creates_a_draft_contract_and_redirects_to_the_edit_page(): void
    {
        $fixtures = $this->makeFixtures();

        $employee = Employee::query()->create([
            'city_id' => $fixtures['city']->id,
            'first_name_en' => 'Test', 'first_name_ar' => 'اختبار',
            'last_name_en' => 'Person', 'last_name_ar' => 'شخص',
            'username' => 'draft.reserve.employee',
            'is_active' => true,
        ]);

        Livewire::actingAs($fixtures['hr'])
            ->test(EmployeeCreate::class)
            ->set('infoForm.employee', $employee)
            ->set('draftForm.position_id', $fixtures['position']->id)
            ->call('createDraftAndContinue')
            ->assertRedirect(route('employees.edit', $employee->id));

        $contract = Contract::where('employee_id', $employee->id)->first();

        $this->assertNotNull($contract);
        $this->assertSame(ContractTypeEnum::Draft, (int) $contract->type);
        $this->assertSame($fixtures['position']->id, $contract->position_id);
        $this->assertNull($contract->start_date);
        $this->assertNull($contract->weekly_hours);
    }

    public function test_reserving_a_position_outside_the_employee_city_is_rejected(): void
    {
        $fixtures = $this->makeFixtures();
        $otherCity = $this->makeCity('HMS', '002');
        $otherPosition = Position::query()->forceCreate([
            'city_id' => $otherCity->id,
            'department_id' => $fixtures['department']->id,
            'job_level_id' => $fixtures['jobLevel']->id,
            'program_id' => $fixtures['program']->id,
            'title_en' => 'Remote Officer', 'title_ar' => 'مسؤول عن بعد',
            'max_emp' => 2, 'exist_emp' => 0, 'is_active' => true,
        ]);

        $employee = Employee::query()->create([
            'city_id' => $fixtures['city']->id,
            'first_name_en' => 'Test', 'first_name_ar' => 'اختبار',
            'last_name_en' => 'Person', 'last_name_ar' => 'شخص',
            'username' => 'draft.reject.employee',
            'is_active' => true,
        ]);

        Livewire::actingAs($fixtures['hr'])
            ->test(EmployeeCreate::class)
            ->set('infoForm.employee', $employee)
            ->set('draftForm.position_id', $otherPosition->id)
            ->call('createDraftAndContinue')
            ->assertHasErrors('draftForm.position_id');

        $this->assertSame(0, Contract::where('employee_id', $employee->id)->count());
    }

    public function test_skipping_the_draft_prompt_redirects_without_creating_a_contract(): void
    {
        $fixtures = $this->makeFixtures();

        $employee = Employee::query()->create([
            'city_id' => $fixtures['city']->id,
            'first_name_en' => 'Test', 'first_name_ar' => 'اختبار',
            'last_name_en' => 'Person', 'last_name_ar' => 'شخص',
            'username' => 'draft.skip.employee',
            'is_active' => true,
        ]);

        Livewire::actingAs($fixtures['hr'])
            ->test(EmployeeCreate::class)
            ->set('infoForm.employee', $employee)
            ->call('skipDraft')
            ->assertRedirect(route('employees.edit', $employee->id));

        $this->assertSame(0, Contract::where('employee_id', $employee->id)->count());
    }

    public function test_a_draft_does_not_block_on_a_position_already_at_capacity(): void
    {
        $fixtures = $this->makeFixtures();
        $fixtures['position']->update(['max_emp' => 1, 'exist_emp' => 1]);

        $employee = Employee::query()->create([
            'city_id' => $fixtures['city']->id,
            'first_name_en' => 'Test', 'first_name_ar' => 'اختبار',
            'last_name_en' => 'Person', 'last_name_ar' => 'شخص',
            'username' => 'draft.overcapacity.employee',
            'is_active' => true,
        ]);

        Livewire::actingAs($fixtures['hr'])
            ->test(EmployeeCreate::class)
            ->set('infoForm.employee', $employee)
            ->set('draftForm.position_id', $fixtures['position']->id)
            ->call('createDraftAndContinue')
            ->assertHasNoErrors();

        $this->assertSame(1, Contract::where('employee_id', $employee->id)->count());
    }

    private function makeFixtures(): array
    {
        $city = $this->makeCity('ALP', '001');

        $center = Center::query()->create([
            'name_en' => 'Hope Center',
            'name_ar' => 'مركز الرجاء',
            'city_id' => $city->id,
            'is_active' => true,
            'weekends' => 'friday',
            'open_at' => '08:00:00',
            'close_at' => '16:00:00',
        ]);

        $department = Department::query()->forceCreate(['name' => 'Programs', 'code' => 'PRG']);
        $jobLevel = JobLevel::query()->forceCreate(['name_en' => 'Officer', 'name_ar' => 'مسؤول', 'grade' => 2]);
        $program = Program::query()->forceCreate(['name' => 'Core Program', 'slug' => 'core-program', 'code' => 'CORE', 'is_active' => true]);

        $position = Position::query()->forceCreate([
            'center_id' => $center->id,
            'city_id' => $city->id,
            'department_id' => $department->id,
            'job_level_id' => $jobLevel->id,
            'program_id' => $program->id,
            'title_en' => 'Program Officer',
            'title_ar' => 'مسؤول برنامج',
            'max_emp' => 5,
            'exist_emp' => 0,
            'is_active' => true,
        ]);

        ContractType::query()->forceCreate(['id' => 1, 'name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع']);

        $hr = $this->makeUser($city, 'HR-ALP', ['employees.create', 'employees.update']);

        return compact('city', 'center', 'department', 'jobLevel', 'program', 'position', 'hr');
    }
}
