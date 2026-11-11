<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\User;

test('a program is stored against its strategy and manager', function () {
    $strategyId = insertStrategy();
    $manager = User::factory()->create();

    $programId = insertProgram($strategyId, $manager->employee_id);

    $this->assertDatabaseHas('programs', [
        'id' => $programId,
        'strategy_id' => $strategyId,
        'manager_id' => $manager->employee_id,
    ]);
});

test('a program cannot reference a strategy that does not exist', function () {
    $manager = User::factory()->create();

    expect(fn () => insertProgram(999, $manager->employee_id))->toThrow(QueryException::class);

    $this->assertDatabaseCount('programs', 0);
});

test('a strategy is not deletable while a program runs under it', function () {
    $strategyId = insertStrategy();
    $manager = User::factory()->create();
    insertProgram($strategyId, $manager->employee_id);

    expect(fn () => DB::table('strategies')->where('id', $strategyId)->delete())
        ->toThrow(QueryException::class);

    $this->assertDatabaseHas('strategies', ['id' => $strategyId]);
});

test('a program stores its manager hr employee id with a foreign key to employees', function () {
    $foreignKeyColumns = collect(Schema::getForeignKeys('programs'))->pluck('columns')->flatten();

    expect($foreignKeyColumns)->toContain('manager_id');
});
