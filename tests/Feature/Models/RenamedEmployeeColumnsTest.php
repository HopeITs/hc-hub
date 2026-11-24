<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tracking\Models\Project;

/*
| Roadmap 1.9, expand step: projects.owner_employee_id and
| sits next to the old misnamed column,
| which stays until a later PR drops it. The migration copied the old
| values in, and every model save keeps each pair equal.
*/

/**
 * @return array{0: mixed, 1: mixed}
 */
function storedProjectOwner(int $projectId): array
{
    $row = DB::table('projects')->find($projectId);

    return [$row->user_id, $row->owner_employee_id];
}

test('the new columns restrict deleting the employee, like the old ones', function (string $table, string $old, string $new) {
    $keys = collect(Schema::getForeignKeys($table))->keyBy(fn (array $key): string => implode(',', $key['columns']));

    expect($keys[$new]['foreign_table'])->toBe('employees')
        ->and(strtolower($keys[$new]['on_delete']))->toBe(strtolower($keys[$old]['on_delete']))
        ->and(strtolower($keys[$new]['on_delete']))->toBe('restrict');
})->with([
    ['projects', 'user_id', 'owner_employee_id'],
]);

test('the migration copies the old columns into the new ones', function () {
    $owner = insertHrEmployee();
    $projectId = insertProject(insertHrCity(), insertProgram(insertStrategy(), insertHrEmployee()), $owner);

    // A row written before the new columns existed.
    DB::table('projects')->where('id', $projectId)->update(['owner_employee_id' => null]);

    (require base_path('database/migrations/2026_11_16_170931_add_employee_columns_to_projects_and_milestones.php'))->backfill();

    expect(storedProjectOwner($projectId))->toBe([$owner, $owner]);
});

test('saving a project keeps the old owner column equal to the new one', function () {
    [$first, $second, $third] = [insertHrEmployee(), insertHrEmployee(firstNameEn: 'Rana'), insertHrEmployee(firstNameEn: 'Omar')];

    $project = Project::factory()->create(['owner_employee_id' => $first]);
    expect(storedProjectOwner($project->id))->toBe([$first, $first]);

    $project->update(['owner_employee_id' => $second]);
    expect(storedProjectOwner($project->id))->toBe([$second, $second]);

    // A writer still using the old name is mirrored the other way.
    $project->update(['user_id' => $third]);
    expect(storedProjectOwner($project->id))->toBe([$third, $third])
        ->and($project->fresh()->user->id)->toBe($third);
});

test('an owner change is logged once, under the new column', function () {
    $project = Project::factory()->create();
    $next = insertHrEmployee(firstNameEn: 'Rana');

    $project->update(['owner_employee_id' => $next]);

    $activity = DB::table('activities')->where('event', 'project.owner_changed')->sole();
    $changes = json_decode($activity->changes, true);

    expect($changes)->toHaveKey('owner_employee_id')
        ->and($changes)->not->toHaveKey('user_id');
});
