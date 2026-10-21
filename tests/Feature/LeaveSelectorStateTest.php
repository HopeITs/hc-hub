<?php

namespace Tests\Feature;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveTypeCases;
use App\Livewire\Employee\Leave\MyLeaveRequestModule;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A3-R: the leave type selector keeps its value across edit, reopen and
 * clear, and the server decides what the form shows from that value alone.
 */
class LeaveSelectorStateTest extends TestCase
{
    use RefreshDatabase;

    private const RADIO_DAY = 'radio-formtype-day';

    private User $user;

    private LeaveBalance $annual;

    private LeaveBalance $sick;

    private LeaveRequest $pending;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createStaffWithBalances();
    }

    public function test_edit_prefills_the_selected_balance_and_shows_the_annual_mode_choice(): void
    {
        Livewire::actingAs($this->user)
            ->test(MyLeaveRequestModule::class)
            ->call('edit', $this->pending->id)
            ->assertHasNoErrors()
            ->assertSet('form.id', $this->pending->id)
            ->assertSet('form.leave_balance_id', $this->annual->id)
            ->assertSet('showModal', true)
            ->assertSeeHtml(self::RADIO_DAY);
    }

    public function test_opening_a_new_request_after_an_edit_leaves_no_stale_edit_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(MyLeaveRequestModule::class)
            ->call('edit', $this->pending->id)
            ->assertSet('form.leave_balance_id', $this->annual->id)
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('form.leave_balance_id', null)
            ->assertSet('form.id', null)
            ->assertSet('form.type', 'day')
            ->assertSet('showModal', true)
            ->assertDontSeeHtml(self::RADIO_DAY);
    }

    public function test_a_string_id_from_the_browser_selects_and_clearing_deselects(): void
    {
        $this->assertSelectThenClear('Annual Leave');
    }

    public function test_selecting_and_clearing_behaves_the_same_in_arabic(): void
    {
        app()->setLocale('ar');

        // The Arabic option label proves the render really ran in Arabic.
        $this->assertSelectThenClear('إجازة سنوية');

        $this->assertSame('ar', app()->getLocale());
    }

    public function test_saving_after_the_selector_is_cleared_is_rejected(): void
    {
        $before = LeaveRequest::query()->count();

        Livewire::actingAs($this->user)
            ->test(MyLeaveRequestModule::class)
            ->call('create')
            ->set('form.leave_balance_id', (string) $this->annual->id)
            ->set('form.type', 'day')
            ->set('form.start_at', now()->addMonth()->toDateString())
            ->set('form.end_at', now()->addMonth()->addDay()->toDateString())
            ->set('form.reason', 'Cleared selector must not save.')
            ->set('form.leave_balance_id', null)
            ->call('processSave')
            ->assertHasErrors(['form.leave_balance_id' => 'required']);

        $this->assertSame($before, LeaveRequest::query()->count());
    }

    private function assertSelectThenClear(string $annualLabel): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(MyLeaveRequestModule::class)
            ->call('create')
            ->assertSee($annualLabel)
            ->set('form.leave_balance_id', (string) $this->annual->id)
            ->assertHasNoErrors()
            ->assertSeeHtml(self::RADIO_DAY);

        // A non-annual balance never offers the day/hour choice.
        $component
            ->set('form.leave_balance_id', (string) $this->sick->id)
            ->assertHasNoErrors()
            ->assertDontSeeHtml(self::RADIO_DAY);

        $component
            ->set('form.leave_balance_id', (string) $this->annual->id)
            ->assertSeeHtml(self::RADIO_DAY)
            ->set('form.leave_balance_id', null)
            ->assertSet('form.leave_balance_id', null)
            ->assertHasNoErrors()
            ->assertDontSeeHtml(self::RADIO_DAY);
    }

    /**
     * Same schema-level fixture approach as LeaveRequestWorkflowTest: the
     * EmployeeFactory still targets removed columns, so rows are inserted
     * against the current migrations. The annual type must carry the id
     * LeaveTypeCases::Annual, because the view keys the mode choice on it.
     */
    private function createStaffWithBalances(): void
    {
        $cityId = DB::table('cities')->insertGetId([
            'name_en' => 'Aleppo',
            'name_ar' => 'حلب',
            'slug' => 'ALP',
            'code' => '001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $programId = DB::table('programs')->insertGetId([
            'name' => 'Program',
            'code' => 'PRG',
            'slug' => 'program',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $centerId = DB::table('centers')->insertGetId([
            'name_en' => 'Center',
            'name_ar' => 'مركز',
            'weekends' => '0,6',
            'city_id' => $cityId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $departmentId = DB::table('departments')->insertGetId([
            'name' => 'Department',
            'code' => 'DEP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $jobLevelId = DB::table('job_levels')->insertGetId([
            'name_en' => 'Officer',
            'name_ar' => 'موظف',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $positionId = DB::table('positions')->insertGetId([
            'program_id' => $programId,
            'center_id' => $centerId,
            'city_id' => $cityId,
            'job_level_id' => $jobLevelId,
            'department_id' => $departmentId,
            'title_en' => 'Officer',
            'title_ar' => 'موظف',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employeeId = DB::table('employees')->insertGetId([
            'city_id' => $cityId,
            'first_name_en' => 'Test',
            'first_name_ar' => 'اختبار',
            'last_name_en' => 'Employee',
            'last_name_ar' => 'موظف',
            'username' => 'selector-employee',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contractTypeId = DB::table('contract_types')->insertGetId([
            'name_en' => 'Annual',
            'name_ar' => 'سنوي',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contractId = DB::table('contracts')->insertGetId([
            'employee_id' => $employeeId,
            'position_id' => $positionId,
            'contract_type_id' => $contractTypeId,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
            'currency' => 2,
            'weekly_hours' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('leave_types')->insert([
            [
                'id' => LeaveTypeCases::Annual,
                'name_en' => 'Annual Leave',
                'name_ar' => 'إجازة سنوية',
                'base_days' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => LeaveTypeCases::Annual + 1,
                'name_en' => 'Sick Leave',
                'name_ar' => 'إجازة مرضية',
                'base_days' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $annualId = DB::table('leave_balances')->insertGetId([
            'contract_id' => $contractId,
            'type_id' => LeaveTypeCases::Annual,
            'deserved' => 160,
            'used' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sickId = DB::table('leave_balances')->insertGetId([
            'contract_id' => $contractId,
            'type_id' => LeaveTypeCases::Annual + 1,
            'deserved' => 120,
            'used' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pendingId = DB::table('leave_requests')->insertGetId([
            'leave_balance_id' => $annualId,
            'type' => 'day',
            'start_at' => now()->addWeek()->startOfDay(),
            'end_at' => now()->addWeek()->addDay()->startOfDay(),
            'reason' => 'Pending annual leave.',
            'status' => LeaveRequestStatus::Pending,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = User::query()->create([
            'employee_id' => $employeeId,
            'email' => 'selector.employee@example.test',
            'password' => 'password',
        ]);
        $this->user->givePermissionTo(['leaves.create', 'leaves.update']);

        $this->annual = LeaveBalance::query()->findOrFail($annualId);
        $this->sick = LeaveBalance::query()->findOrFail($sickId);
        $this->pending = LeaveRequest::query()->findOrFail($pendingId);
    }
}
