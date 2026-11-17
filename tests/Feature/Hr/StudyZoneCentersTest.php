<?php

use Livewire\Livewire;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Spatie\Permission\Models\Permission;

/*
| A Study Zone position has no center: its volunteers move between the
| program's centers in their city (center_program). Such a position works at
| every center linked to its program there: the center page counts its
| people, the program page lists the linked centers, and the centers list
| notes the shared positions apart.
*/

beforeEach(function () {
    $this->travelTo('2026-10-21 10:00:00');

    $this->homs = City::factory()->create(['name_en' => 'Homs'])->id;
    $this->aleppo = City::factory()->create(['name_en' => 'Aleppo'])->id;
    $this->studyZone = Program::factory()->unassigned()->create(['name' => 'Study Zone'])->id;
    $this->hope = Program::factory()->unassigned()->create(['name' => 'Hope Center'])->id;

    $this->viewer = User::factory()->create();
    foreach (['programs.view', 'centers.view', 'employees.view'] as $permission) {
        $this->viewer->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->viewer = $this->viewer->fresh();

    /** A center in a city, serving the given programs. */
    $this->center = function (int $cityId, string $name, array $programs = []): Center {
        $center = Center::factory()->create(['city_id' => $cityId, 'name_en' => $name, 'name_ar' => $name]);
        $center->programs()->sync($programs);

        return $center;
    };

    /** Someone working now on a position of $programId in $cityId, at $centerId or none. */
    $this->worker = function (int $programId, int $cityId, ?int $centerId = null, string $name = 'Rami'): Employee {
        $employee = Employee::factory()->create(['city_id' => $cityId, 'first_name_en' => $name]);
        Contract::factory()->for($employee)
            ->for(Position::factory()->create(['program_id' => $programId, 'city_id' => $cityId, 'center_id' => $centerId, 'max_emp' => 2]))
            ->create(['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);

        return $employee;
    };
});

test('a center counts the shared volunteers of a program it serves in its city', function () {
    $scienceZone = ($this->center)($this->homs, 'Science Zone Homs', [$this->studyZone]);
    ($this->worker)($this->studyZone, $this->homs, name: 'Shared');
    ($this->worker)($this->hope, $this->homs, $scienceZone->id, name: 'Own');
    // Elsewhere, or a program the center does not serve: not working here.
    ($this->worker)($this->studyZone, $this->aleppo, name: 'Aleppo');
    ($this->worker)($this->hope, $this->homs, name: 'Unlinked');

    $page = Livewire::actingAs($this->viewer)->test('hr::settings.center', ['center' => $scienceZone]);

    expect($page->get('totals'))->toBe(['positions' => 2, 'seats' => 4, 'employees' => 2, 'assignments' => 2])
        ->and($page->get('byProgram'))->toBe(['Hope Center' => 1, 'Study Zone' => 1])
        ->and($page->get('assignments')->total())->toBe(2);
});

test('a center serving no program counts only its own positions', function () {
    $center = ($this->center)($this->homs, 'Hope Homs');
    ($this->worker)($this->studyZone, $this->homs);

    $page = Livewire::actingAs($this->viewer)->test('hr::settings.center', ['center' => $center]);

    expect($page->get('totals')['employees'])->toBe(0);
});

test('the program page lists the centers linked to it, with their shared positions and people', function () {
    $homs = ($this->center)($this->homs, 'Study Zone Homs', [$this->studyZone]);
    $aleppo = ($this->center)($this->aleppo, 'Study Zone Aleppo', [$this->studyZone]);
    ($this->worker)($this->studyZone, $this->homs);
    ($this->worker)($this->studyZone, $this->homs);

    $page = Livewire::actingAs($this->viewer)->test('hr::settings.program', ['program' => Program::query()->findOrFail($this->studyZone)]);
    $centers = collect($page->get('breakdown')['centers'])->keyBy('id');

    expect($centers->keys()->sort()->values()->all())->toBe(collect([$homs->id, $aleppo->id])->sort()->values()->all())
        ->and($centers[$homs->id])->toMatchArray(['positions' => 2, 'employees' => 2])
        ->and($centers[$aleppo->id])->toMatchArray(['positions' => 0, 'employees' => 0])
        ->and($page->get('totals')['centers'])->toBe(2);
});

test('the centers list notes the shared positions apart from its own', function () {
    ($this->center)($this->homs, 'Study Zone Homs', [$this->studyZone]);
    ($this->worker)($this->studyZone, $this->homs);
    ($this->worker)($this->studyZone, $this->homs);

    Livewire::actingAs($this->viewer)->test('hr::settings.centers')
        ->assertSeeHtml('data-test="center-shared-positions"')
        ->assertSee('+ 2 shared');
});

test('the people list puts head office first, then cities by code, each by job level', function () {
    $national = City::query()->where('slug', City::NATIONAL_SLUG)->first()
        ?? City::factory()->create(['slug' => City::NATIONAL_SLUG, 'code' => '000']);
    City::query()->whereKey($this->aleppo)->update(['code' => '001']);
    City::query()->whereKey($this->homs)->update(['code' => '002']);
    $manager = JobLevel::factory()->create(['grade' => 1]);
    $officer = JobLevel::factory()->create(['grade' => 5]);

    $place = function (int $cityId, JobLevel $level, string $title): void {
        Contract::factory()->for(Employee::factory()->create())
            ->for(Position::factory()->create(['program_id' => $this->hope, 'city_id' => $cityId, 'center_id' => null, 'job_level_id' => $level->id, 'title_en' => $title]))
            ->create(['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
    };
    $place($this->homs, $manager, 'Homs manager');
    $place($this->aleppo, $officer, 'Aleppo officer');
    $place($national->id, $officer, 'Head office officer');
    $place($this->aleppo, $manager, 'Aleppo manager');

    $page = Livewire::actingAs($this->viewer)->test('hr::settings.program', ['program' => Program::query()->findOrFail($this->hope)]);

    expect($page->get('assignments')->getCollection()->map(fn (Contract $contract): string => $contract->position->title_en)->all())
        ->toBe(['Head office officer', 'Aleppo manager', 'Aleppo officer', 'Homs manager']);
});

test('the people list filters by center, department and job level, offering only what it uses', function () {
    $homs = ($this->center)($this->homs, 'Hope Homs');
    $aleppo = ($this->center)($this->aleppo, 'Hope Aleppo');
    $unused = ($this->center)($this->aleppo, 'Hope Idle');
    $teaching = Department::factory()->create(['name' => 'Teaching']);
    $finance = Department::factory()->create(['name' => 'Finance']);

    $place = function (Center $center, Department $department): void {
        Contract::factory()->for(Employee::factory()->create())
            ->for(Position::factory()->create(['program_id' => $this->hope, 'city_id' => $center->city_id, 'center_id' => $center->id, 'department_id' => $department->id]))
            ->create(['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
    };
    $place($homs, $teaching);
    $place($aleppo, $teaching);
    $place($aleppo, $finance);

    $page = Livewire::actingAs($this->viewer)->test('hr::settings.program', ['program' => Program::query()->findOrFail($this->hope)]);

    expect(array_keys($page->get('assignmentFilters')['centers']))->not->toContain($unused->id)
        ->and($page->get('assignmentFilters')['centers'])->toHaveCount(2);

    $page->set('employeeCenter', (string) $aleppo->id);
    expect($page->get('assignments')->total())->toBe(2);

    $page->set('employeeDepartment', (string) $finance->id);
    expect($page->get('assignments')->total())->toBe(1);

    // A choice the list does not offer is ignored rather than trusted.
    $page->call('resetEmployeeFilters')->set('employeeCenter', (string) $unused->id);
    expect($page->get('assignments')->total())->toBe(3);
});
