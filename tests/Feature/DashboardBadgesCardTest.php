<?php

namespace Tests\Feature;

use App\Enums\BadgeType;
use App\Livewire\Dashboard\Index;
use App\Models\Badge;
use App\Models\City;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesContractFixtures;
use Tests\TestCase;

/**
 * The dashboard's "Badges and warnings" card lists what the Badges page
 * would, for the same roles: it reads through Badge::scopeVisibleTo().
 */
class DashboardBadgesCardTest extends TestCase
{
    use CreatesContractFixtures, RefreshDatabase;

    private City $aleppo;

    private City $homs;

    private Badge $aleppoBadge;

    private Badge $homsBadge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->aleppo = $this->makeCity('ALP', '001');
        $this->homs = $this->makeCity('HMS', '002');
        $this->aleppoBadge = $this->badgeFor($this->makeNamed($this->aleppo, 'Aleppobadged'));
        $this->homsBadge = $this->badgeFor($this->makeNamed($this->homs, 'Homsbadged'));
    }

    public function test_city_hr_sees_only_its_own_city(): void
    {
        $this->dashboardAs($this->makeUser($this->aleppo, 'HR-ALP', ['badges.view']))
            ->assertViewHas('canViewBadges', true)
            ->assertViewHas('myBadges', fn ($badges) => $badges->count() === 1)
            ->assertSee(__('general.badges-warnings'))
            ->assertViewHas('myBadges', fn ($badges) => $this->ids($badges) === [$this->aleppoBadge->id]);
    }

    public function test_hr_syr_sees_every_city(): void
    {
        $this->dashboardAs($this->makeUser($this->aleppo, 'HR-SYR'))
            ->assertViewHas('myBadges', fn ($badges) => $badges->count() === 2)
            ->assertViewHas('myBadges', fn ($badges) => $this->ids($badges) === $this->ids([$this->aleppoBadge, $this->homsBadge]));
    }

    public function test_general_manager_sees_every_city(): void
    {
        $this->dashboardAs($this->makeUser($this->aleppo, 'general-manager'))
            ->assertViewHas('myBadges', fn ($badges) => $badges->count() === 2)
            ->assertViewHas('myBadges', fn ($badges) => $this->ids($badges) === $this->ids([$this->aleppoBadge, $this->homsBadge]));
    }

    public function test_employee_and_manager_do_not_get_the_card(): void
    {
        foreach (['employee', 'manager'] as $role) {
            $this->dashboardAs($this->makeUser($this->aleppo, $role))
                ->assertViewHas('canViewBadges', false)
                ->assertDontSee(__('general.badges-warnings'))
                ->assertViewHas('myBadges', fn ($badges) => $badges->isEmpty());
        }
    }

    public function test_employee_no_longer_sees_their_own_badges_on_the_card(): void
    {
        $self = $this->makeNamed($this->aleppo, 'Selfbadged');
        $this->badgeFor($self);

        $this->dashboardAs($this->makeUser($this->aleppo, 'employee', [], $self))
            ->assertViewHas('canViewBadges', false)
            ->assertDontSee(__('general.badges-warnings'))
            ->assertViewHas('myBadges', fn ($badges) => $badges->isEmpty());
    }

    public function test_manager_granted_badges_view_sees_only_their_reports(): void
    {
        $managerEmployee = $this->makeNamed($this->aleppo, 'Managerperson');
        $managerPosition = $this->makePosition($this->aleppo);
        $this->makeContractFor($managerEmployee, [
            'position_id' => $managerPosition->id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        $directReport = $this->makeNamed($this->aleppo, 'Directreport');
        $this->makeContractFor($directReport, [
            'position_id' => $this->makePosition($this->aleppo, ['direct_manager_id' => $managerPosition->id])->id,
        ]);
        $techReport = $this->makeNamed($this->homs, 'Techreport');
        $this->makeContractFor($techReport, [
            'position_id' => $this->makePosition($this->homs, ['tech_manager_id' => $managerPosition->id])->id,
        ]);
        $directBadge = $this->badgeFor($directReport);
        $techBadge = $this->badgeFor($techReport);

        $this->dashboardAs($this->makeUser($this->aleppo, 'manager', ['badges.view'], $managerEmployee))
            ->assertViewHas('canViewBadges', true)
            ->assertViewHas('myBadges', fn ($badges) => $badges->count() === 2)
            ->assertViewHas('myBadges', fn ($badges) => $this->ids($badges) === $this->ids([$directBadge, $techBadge]))
            ->assertSee(__('general.badges-warnings'));
    }

    private function dashboardAs(User $user)
    {
        return Livewire::actingAs($user)->test(Index::class);
    }

    /** Sorted ids, so the card's newest-first order does not matter. */
    private function ids(iterable $badges): array
    {
        return collect($badges)->pluck('id')->sort()->values()->all();
    }

    private function makeNamed(City $city, string $firstName): Employee
    {
        // Both columns: the suite renders in whichever locale is current.
        return $this->makeEmployee($city, strtolower($firstName), [
            'first_name_en' => $firstName,
            'first_name_ar' => $firstName,
        ]);
    }

    private function badgeFor(Employee $employee): Badge
    {
        return $employee->badges()->create([
            'type' => BadgeType::Warning,
            'date' => now(),
            'reason' => 'Private badge reason',
        ]);
    }
}
