<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Positions\SavePosition;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Position;
use Spatie\Permission\Models\Permission;

/*
| Characterization tests for editing a position (roadmap 2.1, D13), before
| its changes start being logged (2.6): an edit saves its new department,
| center and manager; a bad value or a login without the right changes nothing.
*/

beforeEach(function () {
    $this->position = Position::factory()->create(['max_emp' => 3, 'exist_emp' => 1, 'qualifications' => 'Degree', 'responsibilities' => 'Lead the team']);

    $this->hr = User::factory()->role('HR-SYR')->create();
    $this->hr->givePermissionTo(Permission::findOrCreate('positions.create', 'web'), Permission::findOrCreate('positions.update', 'web'));
    $this->hr = $this->hr->fresh();

    $this->form = fn (array $overrides = []): array => $overrides + [
        'title_en' => $this->position->title_en,
        'title_ar' => $this->position->title_ar,
        'program_id' => $this->position->program_id,
        'city_id' => $this->position->city_id,
        'center_id' => null,
        'direct_manager_id' => null,
        'tech_manager_id' => null,
        'job_level_id' => $this->position->job_level_id,
        'department_id' => $this->position->department_id,
        'qualifications' => 'Degree',
        'responsibilities' => 'Lead the team',
        'max_emp' => 3,
        'is_active' => true,
    ];
});

test('editing a position saves its new department and direct manager', function () {
    $department = Department::factory()->create();
    $manager = Position::factory()->create();

    app(SavePosition::class)->handle($this->position, ($this->form)(['department_id' => $department->id, 'direct_manager_id' => $manager->id]), $this->hr);

    $this->position->refresh();

    expect((int) $this->position->department_id)->toBe($department->id)
        ->and((int) $this->position->direct_manager_id)->toBe($manager->id);
});

test('a position cannot be its own manager, and the refused edit changes nothing', function () {
    $before = (int) $this->position->department_id;

    expect(fn () => app(SavePosition::class)->handle($this->position, ($this->form)(['direct_manager_id' => $this->position->id, 'department_id' => Department::factory()->create()->id]), $this->hr))
        ->toThrow(ValidationException::class);

    expect((int) $this->position->fresh()->department_id)->toBe($before)
        ->and($this->position->fresh()->direct_manager_id)->toBeNull();
});

test('capacity cannot drop below the seats already filled', function () {
    expect(fn () => app(SavePosition::class)->handle($this->position, ($this->form)(['max_emp' => 0]), $this->hr))
        ->toThrow(ValidationException::class);

    expect((int) $this->position->fresh()->max_emp)->toBe(3);
});

test('a login without positions.update cannot edit a position', function () {
    expect(fn () => app(SavePosition::class)->handle($this->position, ($this->form)(['department_id' => Department::factory()->create()->id]), User::factory()->create()))
        ->toThrow(AuthorizationException::class);
});
