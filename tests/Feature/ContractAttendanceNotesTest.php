<?php

namespace Tests\Feature;

use App\Livewire\Forms\ContractForm;
use App\Models\City;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Tests\TestCase;

class ContractAttendanceNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_notes_are_created_loaded_and_updated_with_the_contract(): void
    {
        [$employee, $position, $contractType] = $this->createContractDependencies();

        $form = $this->makeForm();
        $form->employee_id = $employee->id;
        $form->position_id = $position->id;
        $form->contract_type_id = $contractType->id;
        $form->start_date = '2026-08-01';
        $form->end_date = '2026-12-31';
        $form->weekly_hours = 40;
        $form->work_days_en = 'Monday to Friday';
        $form->work_days_ar = 'من الاثنين إلى الجمعة';
        $form->attending_notes_en = '  Flexible arrival on field-visit days.  ';
        $form->attending_notes_ar = '  يسمح بمرونة الحضور في أيام الزيارات الميدانية.  ';
        $form->currency = 2;
        $form->salary = 1000;
        $form->salary_per = 1;

        $contract = $form->save()->refresh();

        $this->assertSame('Flexible arrival on field-visit days.', $contract->attending_notes_en);
        $this->assertSame('يسمح بمرونة الحضور في أيام الزيارات الميدانية.', $contract->attending_notes_ar);

        $editForm = $this->makeForm();
        $editForm->setContract($contract);

        $this->assertSame($contract->attending_notes_en, $editForm->attending_notes_en);
        $this->assertSame($contract->attending_notes_ar, $editForm->attending_notes_ar);

        $editForm->attending_notes_en = 'Check in at reception before 9:00 AM.';
        $editForm->attending_notes_ar = 'تسجيل الحضور في الاستقبال قبل الساعة التاسعة.';
        $editForm->saveTerms();

        $contract->refresh();

        $this->assertSame('Check in at reception before 9:00 AM.', $contract->attending_notes_en);
        $this->assertSame('تسجيل الحضور في الاستقبال قبل الساعة التاسعة.', $contract->attending_notes_ar);
    }

    public function test_attendance_notes_cannot_exceed_the_database_column_length(): void
    {
        app()->setLocale('ar');

        $form = $this->makeForm();
        $form->start_date = '2026-08-01';
        $form->end_date = '2026-12-31';
        $form->weekly_hours = 40;
        $form->attending_notes_en = str_repeat('x', 256);

        try {
            $form->validateTerms();
            $this->fail('The attendance notes length validation did not run.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('form.attending_notes_en', $exception->errors());
            $this->assertStringContainsString(
                'ملاحظات الحضور (بالإنجليزية)',
                $exception->errors()['form.attending_notes_en'][0],
            );
        }
    }

    private function makeForm(): ContractForm
    {
        $component = new class extends Component
        {
            public function render(): string
            {
                return '<div></div>';
            }
        };

        return new ContractForm($component, 'form');
    }

    private function createContractDependencies(): array
    {
        $city = City::query()->forceCreate([
            'name_en' => 'Aleppo',
            'name_ar' => 'حلب',
            'slug' => 'ALP',
            'code' => '001',
        ]);

        $program = Program::create([
            'name' => 'People Program',
            'code' => 'PEO',
            'slug' => 'people-program',
        ]);

        $department = Department::create([
            'name' => 'Human Resources',
            'code' => 'HR',
        ]);

        $jobLevel = JobLevel::create([
            'name_en' => 'Officer',
            'name_ar' => 'مسؤول',
            'grade' => 1,
        ]);

        $position = Position::create([
            'program_id' => $program->id,
            'city_id' => $city->id,
            'job_level_id' => $jobLevel->id,
            'department_id' => $department->id,
            'title_en' => 'HR Officer',
            'title_ar' => 'مسؤول موارد بشرية',
            'max_emp' => 2,
            'exist_emp' => 0,
        ]);

        $employee = Employee::create([
            'city_id' => $city->id,
            'first_name_en' => 'Maya',
            'first_name_ar' => 'مايا',
            'last_name_en' => 'Hope',
            'last_name_ar' => 'هوب',
            'username' => 'maya.hope',
        ]);

        $contractType = ContractType::create([
            'name_en' => 'Annual',
            'name_ar' => 'سنوي',
        ]);

        return [$employee, $position, $contractType];
    }
}
