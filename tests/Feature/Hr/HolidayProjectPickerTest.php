<?php

use App\Models\Role;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Leave\TeamCoverage;

/*
| The holiday dialog's project picker: one group per governorate, whose
| header ticks or clears every project there and leaves the rest alone.
| A project is a program in one governorate, headed by a project-manager
| position (HolidayProjects).
*/

beforeEach(function () {
    $this->hr = User::factory()->create();
    $this->hr->assignRole(Role::findOrCreate('HR-SYR', 'web'));
    $this->hr = $this->hr->fresh();

    $level = JobLevel::factory()->create(['grade' => TeamCoverage::PROJECT_MANAGER_GRADE]);
    $this->aleppo = City::factory()->create(['name_en' => 'Aleppo', 'name_ar' => 'حلب']);
    $this->homs = City::factory()->create(['name_en' => 'Homs', 'name_ar' => 'حمص']);
    $this->leap = Program::factory()->unassigned()->create(['name' => 'LEAP']);
    $this->studyZone = Program::factory()->unassigned()->create(['name' => 'Study Zone']);

    foreach ([[$this->studyZone, $this->aleppo], [$this->leap, $this->aleppo], [$this->studyZone, $this->homs]] as [$program, $city]) {
        Position::factory()->create(['program_id' => $program->id, 'city_id' => $city->id, 'job_level_id' => $level->id, 'is_active' => true]);
    }

    $this->key = fn (Program $program, City $city): string => $program->id.':'.$city->id;
    $this->dialog = fn () => Livewire::actingAs($this->hr)->test('hr::leave.holidays')
        ->call('openCreateHoliday')
        ->set('holidayForm.scope', 'projects');
});

test('the picker groups the projects by governorate, each under its program name', function () {
    $groups = ($this->dialog)()->instance()->projectGroups();

    expect(array_column($groups, 'name'))->toBe(['Aleppo', 'Homs'])
        ->and($groups[0]['id'])->toBe($this->aleppo->id)
        ->and($groups[0]['options'])->toBe([
            ['value' => ($this->key)($this->leap, $this->aleppo), 'label' => 'LEAP'],
            ['value' => ($this->key)($this->studyZone, $this->aleppo), 'label' => 'Study Zone'],
        ])
        ->and($groups[1]['options'])->toBe([
            ['value' => ($this->key)($this->studyZone, $this->homs), 'label' => 'Study Zone'],
        ]);
});

test('select all ticks every project of that governorate and keeps the others', function () {
    $homsKey = ($this->key)($this->studyZone, $this->homs);

    $component = ($this->dialog)()
        ->set('holidayForm.projects', [$homsKey])
        ->call('selectCityProjects', $this->aleppo->id);

    expect($component->get('holidayForm.projects'))->toEqualCanonicalizing([
        $homsKey,
        ($this->key)($this->leap, $this->aleppo),
        ($this->key)($this->studyZone, $this->aleppo),
    ]);
});

test('clear all unticks only that governorate, so moving to another one does not pile up projects', function () {
    $homsKey = ($this->key)($this->studyZone, $this->homs);

    $component = ($this->dialog)()
        ->call('selectCityProjects', $this->aleppo->id)
        ->call('selectCityProjects', $this->homs->id)
        ->call('clearCityProjects', $this->aleppo->id);

    expect($component->get('holidayForm.projects'))->toBe([$homsKey]);
});

test('the dialog draws the type as choice cards and a select-all and clear-all per governorate', function () {
    ($this->dialog)()
        ->assertSeeHtml('data-holiday-kinds')
        ->assertSeeHtml('name="holiday_kind"')
        ->assertSee(__('hr_leave.calendar.holidays.kind_hints.grant'))
        ->assertSeeHtml('name="holiday_scope"')
        ->assertSeeHtml('data-holiday-city="'.$this->aleppo->id.'"')
        ->assertSeeHtml('wire:click="selectCityProjects('.$this->homs->id.')"')
        ->assertSeeHtml('wire:click="clearCityProjects('.$this->homs->id.')"')
        ->assertSee(__('hr_leave.calendar.holidays.selected_of', ['selected' => 0, 'total' => 2]));
});
