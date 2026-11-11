<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\User;
use Modules\Tracking\Models\AnnualPlan;

/**
 * Insert a annual plan for the given program, manager and year.
 */
function insertAnnualPlan(int $programId, int $managerId, int $year = 2026): void
{
    AnnualPlan::factory()->create([
        'program_id' => $programId,
        'manager_id' => $managerId,
        'year' => $year,
    ]);
}

test('a annual plan is stored against its program and manager', function () {
    $manager = User::factory()->create();
    $programId = insertProgram(insertStrategy(), $manager->employee_id);

    insertAnnualPlan($programId, $manager->employee_id, 2026);

    $this->assertDatabaseHas('annual_plans', [
        'program_id' => $programId,
        'manager_id' => $manager->employee_id,
        'year' => 2026,
    ]);
});

test('deleting a program deletes the annual plans beneath it', function () {
    $manager = User::factory()->create();
    $programId = insertProgram(insertStrategy(), $manager->employee_id);
    insertAnnualPlan($programId, $manager->employee_id);

    DB::table('programs')->where('id', $programId)->delete();

    $this->assertDatabaseCount('annual_plans', 0);
});

test('a annual plan stores its manager hr employee id with a foreign key to employees', function () {
    $foreignKeyColumns = collect(Schema::getForeignKeys('annual_plans'))->pluck('columns')->flatten();

    expect($foreignKeyColumns)->toContain('manager_id');
});

test('a program cannot hold two plans for the same year', function () {
    $manager = User::factory()->create();
    $programId = insertProgram(insertStrategy(), $manager->employee_id);
    insertAnnualPlan($programId, $manager->employee_id, 2026);

    expect(fn () => insertAnnualPlan($programId, $manager->employee_id, 2026))
        ->toThrow(QueryException::class);

    $this->assertDatabaseCount('annual_plans', 1);
});

test('two programs may each hold a plan for the same year', function () {
    $manager = User::factory()->create();
    $strategyId = insertStrategy();
    $wash = insertProgram($strategyId, $manager->employee_id, 'WASH');
    $health = insertProgram($strategyId, $manager->employee_id, 'Health');

    insertAnnualPlan($wash, $manager->employee_id, 2026);
    insertAnnualPlan($health, $manager->employee_id, 2026);

    $this->assertDatabaseCount('annual_plans', 2);
});
