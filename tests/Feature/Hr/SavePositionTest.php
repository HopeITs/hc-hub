<?php

use App\Models\Activity;
use App\Notifications\ActivityParameters;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\Center;
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

/*
| Roadmap 2.6: a change to a position's department, center or manager is in
| the activity log with the old and the new value, by name.
*/

test('moving a position to another department records the old and new department', function () {
    $old = Department::factory()->create(['name' => 'Finance']);
    $new = Department::factory()->create(['name' => 'Programs']);
    $this->position->update(['department_id' => $old->id]);

    $this->actingAs($this->hr);
    app(SavePosition::class)->handle($this->position, ($this->form)(['department_id' => $new->id]), $this->hr);

    $activity = Activity::query()->where('subject_type', 'position')->where('subject_id', $this->position->id)->latest('id')->firstOrFail();

    expect($activity->event)->toBe('position.department_changed')
        ->and($activity->module)->toBe('hr-positions')
        ->and($activity->actor_id)->toBe($this->hr->id)
        ->and(array_map('intval', $activity->changes['department_id']))->toBe([$old->id, $new->id])
        ->and($activity->properties['names']['department_id'])->toEqual([['en' => 'Finance', 'ar' => 'Finance'], ['en' => 'Programs', 'ar' => 'Programs']])
        ->and(app(ActivityParameters::class)->for($activity)['from'])->toEqual(['localized' => ['en' => 'Finance', 'ar' => 'Finance']]);
});

test('changing a position’s center and manager records both, old and new', function () {
    $oldManager = Position::factory()->create(['title_en' => 'Area lead', 'title_ar' => 'قائد المنطقة']);
    $newManager = Position::factory()->create(['title_en' => 'Program lead', 'title_ar' => 'قائد البرنامج']);
    $center = Center::factory()->create(['name_en' => 'North', 'name_ar' => 'الشمال', 'city_id' => $this->position->city_id]);
    $this->position->update(['direct_manager_id' => $oldManager->id]);

    $this->actingAs($this->hr);
    app(SavePosition::class)->handle($this->position, ($this->form)(['direct_manager_id' => $newManager->id, 'center_id' => $center->id]), $this->hr);

    $activity = Activity::query()->where('subject_type', 'position')->where('subject_id', $this->position->id)->latest('id')->firstOrFail();

    expect($activity->event)->toBe('position.center_changed')
        ->and(array_map(fn (mixed $id): ?int => $id === null ? null : (int) $id, $activity->changes['center_id']))->toBe([null, $center->id])
        ->and(array_map('intval', $activity->changes['direct_manager_id']))->toBe([$oldManager->id, $newManager->id])
        ->and($activity->properties['names']['center_id'])->toEqual([null, ['en' => 'North', 'ar' => 'الشمال']])
        ->and($activity->properties['names']['direct_manager_id'])->toEqual([['en' => 'Area lead', 'ar' => 'قائد المنطقة'], ['en' => 'Program lead', 'ar' => 'قائد البرنامج']]);
});

test('a seat being filled is not logged as a position change', function () {
    $this->position->update(['exist_emp' => 2]);

    expect(Activity::query()->where('subject_type', 'position')->where('event', 'position.updated')->exists())->toBeFalse();
});
