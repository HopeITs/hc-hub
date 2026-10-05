<?php

namespace Tests\Feature\Security;

use App\Livewire\ProgramManagement\Center\CentersModule;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

/**
 * HR-HMS covers Homs and Homs Rural (config access-control.hr_role_cities).
 * Every other regional role still covers exactly its own city.
 */
class MultiCityHrScopeTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_hr_homs_is_scoped_to_homs_and_homs_rural(): void
    {
        $homs = $this->makeCity('HMS', '002');
        $rural = $this->makeCity('RHO', '015');
        $aleppo = $this->makeCity('ALP', '001');

        $hr = $this->makeUser($homs, 'HR-HMS', ['employees.view', 'employees.update', 'employees.create']);

        $this->assertEqualsCanonicalizing([$homs->id, $rural->id], $hr->activeCityIds());
        $this->assertTrue($hr->canAccessCity($rural->id));
        $this->assertFalse($hr->canAccessCity($aleppo->id));

        $this->actingAs($hr);

        $this->assertTrue(Gate::allows('update', $this->makeEmployee($rural, 'rural-emp')));
        $this->assertFalse(Gate::allows('update', $this->makeEmployee($aleppo, 'aleppo-emp')));
        $this->assertTrue(Gate::allows('create', [\App\Models\Employee::class, $rural->id]));
        $this->assertFalse(Gate::allows('create', [\App\Models\Employee::class, $aleppo->id]));
    }

    public function test_hr_homs_sees_both_cities_in_city_dropdowns(): void
    {
        $homs = $this->makeCity('HMS', '002');
        $rural = $this->makeCity('RHO', '015');
        $this->makeCity('ALP', '001');

        $hr = $this->makeUser($homs, 'HR-HMS', ['centers.view']);

        Livewire::actingAs($hr)
            ->test(CentersModule::class)
            ->assertViewHas('cities', fn ($cities) => $cities->pluck('id')->sort()->values()->all()
                === collect([$homs->id, $rural->id])->sort()->values()->all());
    }

    public function test_other_regional_roles_keep_a_single_city(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        $this->makeCity('RHO', '015');

        $hr = $this->makeUser($aleppo, 'HR-ALP');

        $this->assertSame([$aleppo->id], $hr->activeCityIds());
    }

    public function test_a_regional_role_with_no_matching_city_sees_nothing(): void
    {
        $syria = $this->makeCity('SYR', '000');

        $hr = $this->makeUser($syria, 'HR-XYZ');

        $this->assertSame([], $hr->activeCityIds());
        $this->assertFalse($hr->canAccessCity($syria->id));
    }

    public function test_homs_rural_maps_back_to_hr_homs(): void
    {
        $this->assertSame(['HR-HMS'], City::hrRolesForSlug('RHO'));
        $this->assertSame(['HR-HMS'], City::hrRolesForSlug('HMS'));
        $this->assertSame(['HR-ALP'], City::hrRolesForSlug('ALP'));
    }
}
