<?php

namespace Modules\Hr\Tests\Feature\Security;

use App\Models\Center;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Hr\Livewire\Employee\EmployeeStatistics;
use Modules\Hr\Livewire\ProgramManagement\Center\CentersModule;
use Modules\Hr\Livewire\ProgramManagement\DefaultDeliverable\DefaultDeliverablesTable;
use Modules\Hr\Models\ChecklistItem;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\DefaultDeliverable;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

/**
 * Regional roles must not be able to read, write, or *infer* another city's
 * data. The inference half matters as much as the direct half: a nationwide
 * total tells a regional user how many staff every other city has, without any
 * record ever crossing the boundary.
 */
class CityIsolationTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_a_regional_user_cannot_update_a_centre_in_another_city(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        $homs = $this->makeCity('HMS', '002');

        $hr = $this->makeUser($aleppo, 'HR-ALP', ['hr.centers.view', 'hr.centers.update', 'hr.centers.delete']);
        $remote = $this->makeCenter($homs->id, 'Homs Centre');
        $local = $this->makeCenter($aleppo->id, 'Aleppo Centre');

        $this->actingAs($hr);

        $this->assertTrue(Gate::allows('update', $local));
        $this->assertFalse(Gate::allows('update', $remote));
        $this->assertFalse(Gate::allows('delete', $remote));
        $this->assertFalse(Gate::allows('view', $remote));
    }

    public function test_opening_the_centre_edit_modal_for_another_city_is_refused(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        $homs = $this->makeCity('HMS', '002');

        $hr = $this->makeUser($aleppo, 'HR-ALP', ['hr.centers.view', 'hr.centers.update']);
        $remote = $this->makeCenter($homs->id, 'Homs Centre');

        // The edit button is never rendered for this row; the event is
        // dispatched directly.
        Livewire::actingAs($hr)
            ->test(CentersModule::class)
            ->call('edit', $remote->id)
            ->assertStatus(403);
    }

    public function test_a_regional_user_cannot_save_a_centre_into_another_city(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        $homs = $this->makeCity('HMS', '002');

        $hr = $this->makeUser($aleppo, 'HR-ALP', ['hr.centers.create']);

        Livewire::actingAs($hr)
            ->test(CentersModule::class)
            ->set('item', [
                'id' => null,
                'name_en' => 'Smuggled',
                'name_ar' => 'Smuggled',
                'city_id' => $homs->id,
                'address_en' => 'x',
                'address_ar' => 'x',
                'is_active' => true,
                'weekends' => ['friday'],
                'open_at' => '08:00',
                'close_at' => '16:00',
            ])
            ->call('save')
            ->assertHasErrors('item.city_id');

        $this->assertDatabaseMissing('centers', ['name_en' => 'Smuggled']);
    }

    public function test_the_centre_city_dropdown_only_offers_the_authorized_city(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        $this->makeCity('HMS', '002');

        $hr = $this->makeUser($aleppo, 'HR-ALP', ['hr.centers.view']);

        Livewire::actingAs($hr)
            ->test(CentersModule::class)
            ->assertViewHas('cities', fn ($cities) => $cities->count() === 1
                && (int) $cities->first()->id === $aleppo->id);
    }

    public function test_headquarters_still_sees_every_city(): void
    {
        $syria = $this->makeCity('SYR', '000');
        $this->makeCity('HMS', '002');

        $hq = $this->makeUser($syria, 'HR-SYR', ['hr.centers.view']);

        $this->assertNull($hq->activeCityIds());

        Livewire::actingAs($hq)
            ->test(CentersModule::class)
            ->assertViewHas('cities', fn ($cities) => $cities->count() === 2);
    }

    public function test_the_deliverables_table_excludes_other_cities(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        $homs = $this->makeCity('HMS', '002');

        $localEmployee = $this->makeEmployee($aleppo, 'local-deliverer');
        $remoteEmployee = $this->makeEmployee($homs, 'remote-deliverer');
        $item = ChecklistItem::query()->create(['name' => 'Laptop', 'order' => 1]);

        DefaultDeliverable::create([
            'employee_id' => $localEmployee->id,
            'city_id' => $aleppo->id,
            'item_id' => $item->id,
        ]);
        $remote = DefaultDeliverable::create([
            'employee_id' => $remoteEmployee->id,
            'city_id' => $homs->id,
            'item_id' => $item->id,
        ]);

        $hr = $this->makeUser($aleppo, 'HR-ALP', ['hr.checklists.manage']);

        $this->actingAs($hr);
        $this->assertFalse(Gate::allows('delete', $remote));

        // Deleting by id is refused even though the row is invisible in the UI.
        Livewire::actingAs($hr)
            ->test(DefaultDeliverablesTable::class)
            ->call('delete', ['id' => $remote->id])
            ->assertStatus(403);

        $this->assertDatabaseHas('default_deliverables', ['id' => $remote->id]);
    }

    /**
     * The inference case: no record crosses the boundary, but the aggregate
     * would have disclosed other cities' headcount.
     */
    public function test_contract_statistics_count_only_the_users_own_city(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        $homs = $this->makeCity('HMS', '002');

        [$department, $jobLevel, $program, $type] = $this->positionPrerequisites();

        foreach ([[$aleppo, 'alp', 2], [$homs, 'hms', 5]] as [$city, $prefix, $count]) {
            $position = $this->makePosition($city->id, $prefix, $department, $jobLevel, $program);

            for ($i = 0; $i < $count; $i++) {
                $employee = $this->makeEmployee($city, "{$prefix}-emp-{$i}");

                $this->makeContract($employee->id, $position->id, $type->id);
            }
        }

        $hr = $this->makeUser($aleppo, 'HR-ALP', ['hr.statistics.view']);

        $payload = Livewire::actingAs($hr)
            ->test(EmployeeStatistics::class)
            ->instance()
            ->getStatistics('yearly')
            ->getData(true);

        // 2 Aleppo contracts started this year, plus the HR user's own
        // employee record has none — Homs's 5 must not appear anywhere.
        $this->assertSame(2, array_sum($payload['new']));
    }

    public function test_headquarters_statistics_still_span_the_country(): void
    {
        $syria = $this->makeCity('SYR', '000');
        $homs = $this->makeCity('HMS', '002');

        [$department, $jobLevel, $program, $type] = $this->positionPrerequisites();

        $position = $this->makePosition($homs->id, 'hms', $department, $jobLevel, $program);

        $employee = $this->makeEmployee($homs, 'hms-emp');
        $this->makeContract($employee->id, $position->id, $type->id);

        $hq = $this->makeUser($syria, 'HR-SYR', ['hr.statistics.view']);

        $payload = Livewire::actingAs($hq)
            ->test(EmployeeStatistics::class)
            ->instance()
            ->getStatistics('yearly')
            ->getData(true);

        $this->assertSame(1, array_sum($payload['new']));
    }

    private function makeCenter(int $cityId, string $name): Center
    {
        return Center::query()->create([
            'name_en' => $name,
            'name_ar' => $name,
            'city_id' => $cityId,
            'is_active' => true,
            'weekends' => 'friday',
            'open_at' => '08:00:00',
            'close_at' => '16:00:00',
        ]);
    }

    /** Positions carry several non-nullable foreign keys; build them once. */
    private function positionPrerequisites(): array
    {
        return [
            Department::query()->forceCreate(['name' => 'IT', 'code' => 'IT']),
            JobLevel::query()->forceCreate(['name_en' => 'Officer', 'name_ar' => 'Officer']),
            Program::query()->forceCreate([
                'name' => 'Core',
                'slug' => 'core',
                'code' => 'CORE',
                'start_date' => now()->subYear(),
                'is_active' => true,
            ]),
            ContractType::query()->forceCreate(['name_en' => 'Annual Full', 'name_ar' => 'Annual Full']),
        ];
    }

    private function makePosition(int $cityId, string $prefix, $department, $jobLevel, $program): Position
    {
        return Position::query()->forceCreate([
            'title_en' => $prefix.'-position',
            'title_ar' => $prefix.'-position',
            'city_id' => $cityId,
            'department_id' => $department->id,
            'job_level_id' => $jobLevel->id,
            'program_id' => $program->id,
            'is_active' => true,
        ]);
    }

    private function makeContract(int $employeeId, int $positionId, int $typeId): Contract
    {
        return Contract::query()->forceCreate([
            'employee_id' => $employeeId,
            'position_id' => $positionId,
            'contract_type_id' => $typeId,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
            'currency' => 2,
            'weekly_hours' => 40,
            'is_active' => true,
        ]);
    }
}
