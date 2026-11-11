<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\User;

test('a project is stored against its city, program and user', function () {
    $user = User::factory()->create();
    $cityId = insertCity();
    $programId = insertProgram(insertStrategy(), $user->employee_id);

    insertProject($cityId, $programId, $user->employee_id);

    $this->assertDatabaseHas('projects', [
        'name' => 'Water Network Rehabilitation',
        'city_id' => $cityId,
        'program_id' => $programId,
        'user_id' => $user->id,
    ]);
});

test('deleting a program deletes the projects beneath it', function () {
    $user = User::factory()->create();
    $programId = insertProgram(insertStrategy(), $user->employee_id);
    insertProject(insertCity(), $programId, $user->employee_id);

    DB::table('programs')->where('id', $programId)->delete();

    $this->assertDatabaseCount('projects', 0);
});

test('projects store their city id with a foreign key to the shared cities table', function () {
    $foreignKeyColumns = collect(Schema::getForeignKeys('projects'))->pluck('columns')->flatten();

    expect(Schema::hasTable('cities'))->toBeTrue()
        ->and($foreignKeyColumns)->toContain('city_id');
});

test('a project stores its responsible hr employee id with a foreign key to employees', function () {
    $foreignKeyColumns = collect(Schema::getForeignKeys('projects'))->pluck('columns')->flatten();

    expect($foreignKeyColumns)->toContain('user_id');
});
