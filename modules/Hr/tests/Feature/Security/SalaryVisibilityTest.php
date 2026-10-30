<?php

namespace Modules\Hr\Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Hr\Livewire\Contract\ContractCreate;
use Modules\Hr\Livewire\Contract\ContractEdit;
use Modules\Hr\Livewire\Contract\ContractRenewModal;
use Modules\Hr\Livewire\Employee\EmployeeProfile;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Services\LeaveBalanceService;
use Tests\Concerns\CreatesContractFixtures;
use Tests\TestCase;

/**
 * super-admin is the developers' role. It is granted every permission in the
 * system automatically, which is precisely why pay figures have to be refused
 * to it by name rather than by permission.
 */
class SalaryVisibilityTest extends TestCase
{
    use CreatesContractFixtures, RefreshDatabase;

    public function test_hr_may_see_pay_figures_and_super_admin_may_not(): void
    {
        $city = $this->makeCity('ALP', '001');

        $this->assertTrue($this->makeUser($city, 'HR-ALP')->canViewSalary());
        $this->assertFalse($this->makeUser($city, 'super-admin')->canViewSalary());
        $this->assertFalse($this->makeUser($city, 'general-manager')->canViewSalary());
        $this->assertFalse($this->makeUser($city, 'manager')->canViewSalary());
        $this->assertFalse($this->makeUser($city, 'employee')->canViewSalary());
    }

    /**
     * The rule follows the *active* role, like every other scoping rule here:
     * someone who also holds super-admin but is signed in as HR-ALP is doing HR
     * work and needs the figures.
     */
    public function test_an_inactive_super_admin_role_does_not_blind_an_active_hr_role(): void
    {
        $city = $this->makeCity('ALP', '001');
        $user = $this->assignInactiveRole($this->makeUser($city, 'HR-ALP'), 'super-admin');

        $this->assertTrue($user->canViewSalary());
    }

    public function test_the_employee_profile_withholds_the_salary_from_super_admin(): void
    {
        $city = $this->makeCity('ALP', '001');
        $employee = $this->makeEmployee($city, 'profile-subject');
        $this->makeContractFor($employee, ['salary' => 1234]);

        Livewire::actingAs($this->makeUser($city, 'HR-ALP'))
            ->test(EmployeeProfile::class, ['employee' => $employee])
            ->assertSet('canViewFinancial', true)
            ->assertSee('1,234');

        Livewire::actingAs($this->makeUser($city, 'super-admin'))
            ->test(EmployeeProfile::class, ['employee' => $employee])
            ->assertSet('canViewFinancial', false)
            ->assertDontSee('1,234');
    }

    public function test_super_admin_is_refused_the_contract_pdf_export(): void
    {
        $city = $this->makeCity('ALP', '001');
        $contract = $this->makeContractFor($this->makeEmployee($city, 'export-subject'));

        $this->assertTrue(
            Gate::forUser($this->makeUser($city, 'HR-ALP', ['hr.contracts.export']))->allows('export', $contract),
        );

        // Holds every permission there is, and is still refused: the PDF is the
        // contract, stipend clause included.
        $this->assertFalse(
            Gate::forUser($this->makeUser($city, 'super-admin'))->allows('export', $contract),
        );
    }

    public function test_a_super_admin_edit_leaves_the_stored_pay_clause_untouched(): void
    {
        $city = $this->makeCity('ALP', '001');
        $employee = $this->makeEmployee($city, 'edit-subject');
        $contract = $this->makeContractFor($employee, ['salary' => 1500, 'currency' => 2, 'pay_mode' => 1]);

        $this->mock(LeaveBalanceService::class)->shouldReceive('create')->zeroOrMoreTimes();

        Livewire::actingAs($this->makeUser($city, 'super-admin'))
            ->test(ContractEdit::class, ['contract' => $contract])
            // The figure is never handed to the browser in the first place.
            ->assertSet('form.salary', null)
            ->assertSee(__('general.salary-hidden-title'))
            ->call('submit', $this->payloadFor($contract, ['weeklyHours' => 35]))
            ->assertHasNoErrors();

        $contract->refresh();

        $this->assertSame('1500.00', $contract->salary);
        $this->assertSame(35, $contract->weekly_hours);
        $this->assertSame(2, $contract->currency);
    }

    public function test_a_super_admin_cannot_blank_the_pay_tiers_of_a_flexible_contract(): void
    {
        $city = $this->makeCity('ALP', '001');
        $employee = $this->makeEmployee($city, 'tiers-subject');
        $contract = $this->makeContractFor($employee, ['salary' => null, 'pay_mode' => 2]);
        $contract->payTiers()->create([
            'condition_en' => '60h/month',
            'condition_ar' => 'ستون ساعة شهريا',
            'amount' => 200,
            'sort_order' => 0,
        ]);

        $this->mock(LeaveBalanceService::class)->shouldReceive('create')->zeroOrMoreTimes();

        Livewire::actingAs($this->makeUser($city, 'super-admin'))
            ->test(ContractEdit::class, ['contract' => $contract])
            ->call('submit', $this->payloadFor($contract, ['payMode' => 2, 'payTiers' => []]))
            ->assertHasNoErrors();

        $tiers = $contract->fresh()->payTiers;

        $this->assertCount(1, $tiers);
        $this->assertSame('200.00', $tiers->first()->amount);
    }

    /**
     * "Renew as is" creates a *new* contract, so there is nothing to leave
     * alone — the source's clause is read server-side and copied onto the
     * successor instead, without it ever reaching the browser.
     */
    public function test_a_super_admin_renewal_carries_the_source_pay_clause_forward(): void
    {
        $city = $this->makeCity('ALP', '001');
        $employee = $this->makeEmployee($city, 'renewal-subject');
        $source = $this->makeContractFor($employee, [
            'salary' => 2400,
            'currency' => 2,
            'salary_per' => 1,
            'start_date' => now()->subMonths(3)->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ]);

        $this->mock(LeaveBalanceService::class)->shouldReceive('create')->zeroOrMoreTimes();

        Livewire::actingAs($this->makeUser($city, 'super-admin'))
            ->test(ContractRenewModal::class)
            ->call('open', $source->id)
            ->call('renewAsIs')
            ->assertHasNoErrors();

        $renewal = Contract::query()->where('employee_id', $employee->id)->latest('id')->first();

        $this->assertNotSame($source->id, $renewal->id);
        $this->assertSame('2400.00', $renewal->salary);
        $this->assertSame(2, $renewal->currency);
    }

    /**
     * The one case masking cannot cover: a brand-new contract has no stored
     * figure to carry forward, so a role that cannot type one is told to hand
     * the job back to HR rather than quietly saving a contract with no pay.
     */
    public function test_a_super_admin_cannot_create_a_contract_from_scratch(): void
    {
        $city = $this->makeCity('ALP', '001');
        $employee = $this->makeEmployee($city, 'create-subject');
        $position = $this->makePosition($city);
        $contractType = ContractType::query()->firstOrCreate(
            ['name_en' => 'Volunteer Contract'],
            ['name_ar' => 'Aqd'],
        );

        Livewire::actingAs($this->makeUser($city, 'super-admin'))
            ->test(ContractCreate::class)
            ->call('submit', [
                'cityId' => (string) $city->id,
                'employeeId' => (string) $employee->id,
                'programId' => (string) $position->program_id,
                'centerId' => (string) $position->center_id,
                'positionId' => (string) $position->id,
                'contractTypeId' => (string) $contractType->id,
                'startDate' => '2026-01-01',
                'endDate' => '2026-12-31',
                'workHoursFrom' => '09:00',
                'workHoursTo' => '17:00',
                'weeklyHours' => 40,
                'salary' => 500,
                'currency' => 1,
                'salaryPer' => 1,
            ])
            ->assertHasErrors('form.salary');

        $this->assertDatabaseCount('contracts', 0);
    }

    /** The composer payload an edit submits, seeded from the contract itself. */
    private function payloadFor(Contract $contract, array $overrides = []): array
    {
        $contract->loadMissing('position');

        return array_merge([
            'cityId' => (string) $contract->position->city_id,
            'employeeId' => (string) $contract->employee_id,
            'programId' => (string) $contract->position->program_id,
            'centerId' => (string) $contract->position->center_id,
            'positionId' => (string) $contract->position_id,
            'contractTypeId' => (string) $contract->contract_type_id,
            'startDate' => $contract->start_date->toDateString(),
            'endDate' => $contract->end_date->toDateString(),
            'workHoursFrom' => '09:00',
            'workHoursTo' => '17:00',
            'weeklyHours' => 40,
            // What the browser actually sends for a role that was shown no
            // figures: empty boxes.
            'salary' => null,
            'currency' => 1,
            'salaryPer' => 1,
            'payMode' => 1,
            'payTiers' => [],
        ], $overrides);
    }
}
