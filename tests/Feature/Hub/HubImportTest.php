<?php

use App\Services\HubImport\HrImporter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Hr\Services\SalaryCipher;

/*
 * hub:import (plan 3.1, HR part) against an hc-hr fixture: a second SQLite
 * :memory: database behind the `hr_source` connection, prefixed `hchr_` like
 * the real one, with the hub's columns (minus `uuid`, plus a column the hub
 * dropped) and a handful of rows that exercise every mapping rule.
 */

function source(): Illuminate\Database\Connection
{
    return DB::connection(HrImporter::CONNECTION);
}

/**
 * @param  list<array<string, mixed>>  $rows
 */
function sourceRows(string $table, array $rows): void
{
    source()->table($table)->insert($rows);
}

function hrFixture(): void
{
    $cipher = app(SalaryCipher::class);
    $now = '2026-06-01 01:00:00';

    sourceRows('cities', [
        ['id' => 1, 'name_en' => 'Syria', 'name_ar' => 'سوريا', 'slug' => 'SYR', 'code' => '000', 'is_active' => 1],
        ['id' => 11, 'name_en' => ' Ar-raqqa', 'name_ar' => 'الرقة', 'slug' => 'RQA', 'code' => '010', 'is_active' => 0],
    ]);
    sourceRows('centers', [['id' => 15, 'name_en' => 'Study Zone Al-Mahata', 'name_ar' => 'Study Zone Al-Mahata', 'city_id' => 11]]);
    sourceRows('programs', [
        ['id' => 3, 'name' => 'Relief Programs', 'code' => '003', 'slug' => 'RLF', 'is_active' => 0],
        ['id' => 4, 'name' => 'Family to Family', 'code' => '003', 'slug' => 'F2F', 'is_active' => 1],
        ['id' => 11, 'name' => 'Sha8er', 'code' => '009', 'slug' => 'Sha8er', 'is_active' => 0],
        ['id' => 14, 'name' => 'CYG', 'code' => '009', 'slug' => 'CYG', 'is_active' => 0],
    ]);
    sourceRows('departments', [['id' => 1, 'code' => 'CT', 'name' => 'Content']]);
    sourceRows('job_levels', [['id' => 1, 'name_en' => 'Officer', 'name_ar' => 'موظف']]);
    sourceRows('contract_types', [['id' => 1, 'name_en' => 'Full time', 'name_ar' => 'دوام كامل']]);
    sourceRows('employees', [[
        'id' => 7, 'first_name_en' => 'Fredy', 'first_name_ar' => 'فريدي', 'last_name_en' => 'Youssef', 'last_name_ar' => 'يوسف',
        'username' => 'fredy.youssef', 'city_id' => 1, 'last_saved_by' => 20, 'created_at' => $now, 'updated_at' => $now,
    ]]);
    sourceRows('users', [[
        'id' => 20, 'email' => 'fredy@example.test', 'password' => 'hash', 'employee_id' => 7, 'last_seen_at' => $now,
    ]]);
    sourceRows('positions', [
        ['id' => 5, 'title_en' => 'Officer', 'title_ar' => 'موظف', 'department_id' => 1, 'job_level_id' => 1, 'program_id' => 3, 'city_id' => 1, 'direct_manager_id' => 6],
        ['id' => 6, 'title_en' => 'Manager', 'title_ar' => 'مدير', 'department_id' => 1, 'job_level_id' => 1, 'program_id' => 3, 'city_id' => 1, 'direct_manager_id' => null],
    ]);
    sourceRows('contracts', [['id' => 9, 'contract_type_id' => 1, 'employee_id' => 7, 'position_id' => 5, 'salary' => $cipher->encrypt('1500000')]]);
    sourceRows('permissions', [
        ['id' => 1, 'name' => 'access-management.view', 'guard_name' => 'web'],
        ['id' => 6, 'name' => 'employees.view', 'guard_name' => 'web'],
        ['id' => 64, 'name' => 'recruitment-requests.view', 'guard_name' => 'web'],
    ]);
    sourceRows('roles', [
        ['id' => 3, 'name' => 'HR-SYR', 'guard_name' => 'web'],
        ['id' => 10, 'name' => 'employee', 'guard_name' => 'web'],
    ]);
    sourceRows('role_has_permissions', [
        ['permission_id' => 6, 'role_id' => 3],
        ['permission_id' => 64, 'role_id' => 3],
        ['permission_id' => 1, 'role_id' => 10],
    ]);
    sourceRows('model_has_roles', [['role_id' => 3, 'model_type' => 'App\\Models\\User', 'model_id' => 20, 'is_active' => 1]]);
    sourceRows('folders', [['id' => 1, 'name' => 'Archive', 'user_id' => 20, 'parent_id' => null]]);
    sourceRows('folder_user', [['folder_id' => 1, 'user_id' => 20]]);
    sourceRows('activity_log', [
        ['id' => 1, 'description' => 'updated', 'subject_type' => 'App\\Models\\Employee', 'subject_id' => 7, 'causer_type' => 'App\\Models\\User', 'causer_id' => 20],
        ['id' => 2, 'description' => 'deleted', 'subject_type' => 'App\\Models\\Contract', 'subject_id' => 999, 'causer_type' => 'App\\Models\\User', 'causer_id' => 20],
    ]);
}

beforeEach(function () {
    config(['database.connections.'.HrImporter::CONNECTION => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => 'hchr_',
        'foreign_key_constraints' => false,
    ]]);
    DB::purge(HrImporter::CONNECTION);

    foreach (HrImporter::TABLES as $table) {
        $columns = collect(Schema::getColumns($table))
            ->reject(fn (array $column): bool => $column['name'] === 'uuid'
                || ($table === 'programs' && in_array($column['name'], ['parent_id', 'top_level_code'], true)))
            ->map(fn (array $column): string => '"'.$column['name'].'"'.($column['default'] === null ? '' : ' DEFAULT '.$column['default']))
            ->when($table === 'users', fn ($columns) => $columns->push('"last_seen_at"'));

        source()->statement('CREATE TABLE "hchr_'.$table.'" ('.$columns->implode(', ').')');
    }

    source()->statement('CREATE TABLE "hchr_contracts_bak_20261019" ("id", "salary")');
    source()->statement('CREATE TABLE "hchr_vacancies" ("id")');

    hrFixture();
});

test('a dry run reports counts, orphans and open questions and writes nothing', function () {
    $hubUsers = DB::table('users')->count();

    $report = app(HrImporter::class)->run(dryRun: true);

    expect($report->blocking)->toBe([])
        ->and($report->tables['employees'])->toMatchArray(['source' => 1, 'inserted' => 1, 'expected' => 1, 'target' => null])
        ->and($report->tables['permissions'])->toMatchArray(['source' => 3, 'skipped' => 1, 'expected' => 2])
        ->and($report->skippedRows['role_has_permissions'])->toBe(['grants a permission that is not imported' => 1])
        ->and($report->skippedSourceTables)->toMatchArray([
            'contracts_bak_20261019' => 'backup copy (decided: skip)',
            'vacancies' => 'no such table in the hub (0 rows)',
        ])
        ->and($report->droppedColumns)->toBe(['users' => ['last_seen_at']])
        ->and($report->orphans)->toBe(['activity_log.subject_id (contract) -> contracts' => 1])
        ->and($report->openIssues)->toHaveCount(1)
        ->and($report->openIssues[0])->toContain('no user without an employee');

    expect(DB::table('users')->count())->toBe($hubUsers)
        ->and(DB::table('employees')->count())->toBe(0)
        ->and(DB::table('entity_xref')->count())->toBe(0)
        ->and(DB::table('review_decisions')->count())->toBe(0);
});

test('it copies HR rows with their ids, aliases, prefixed permissions and public uuids', function () {
    $report = app(HrImporter::class)->run(dryRun: false);

    expect($report->blocking)->toBe([])
        ->and($report->countsMatch())->toBeTrue();

    // Ids kept; the users ⇄ employees cycle and the position self reference filled in the second pass.
    expect(DB::table('employees')->where('id', 7)->value('last_saved_by'))->toBe(20)
        ->and(DB::table('users')->where('id', 20)->value('employee_id'))->toBe(7)
        ->and(DB::table('positions')->where('id', 5)->value('direct_manager_id'))->toBe(6);

    // Morph class names → aliases.
    expect(DB::table('model_has_roles')->where('model_id', 20)->value('model_type'))->toBe('user')
        ->and(DB::table('activity_log')->where('id', 1)->first(['subject_type', 'causer_type']))
        ->toEqual((object) ['subject_type' => 'employee', 'causer_type' => 'user']);

    // Permissions renamed like migration 2026_10_29_000002; recruitment ones are not in the hub.
    expect(DB::table('permissions')->whereIn('id', [1, 6, 64])->pluck('name', 'id')->all())
        ->toBe([1 => 'hub.access-management.view', 6 => 'hr.employees.view'])
        ->and(DB::table('role_has_permissions')->where('role_id', 3)->pluck('permission_id')->all())->toBe([6]);

    // The hub's seeded roles gave way to the source's ids.
    expect(DB::table('roles')->where('name', 'HR-SYR')->value('id'))->toBe(3)
        ->and(DB::table('roles')->where('name', 'employee')->value('id'))->toBe(10)
        ->and($report->replacedTargetRows)->not->toBe([]);

    // Public uuids are UUIDv7.
    expect(DB::table('employees')->where('id', 7)->value('uuid'))->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-/')
        ->and(DB::table('cities')->whereNull('uuid')->count())->toBe(0);

    // entity_xref in the Core shape.
    expect(DB::table('entity_xref')->where('source_table', 'hchr_employees')->first(['source_system', 'source_id', 'entity_type', 'entity_id', 'uuid']))
        ->toEqual((object) [
            'source_system' => 'hr',
            'source_id' => '7',
            'entity_type' => 'employee',
            'entity_id' => '7',
            'uuid' => DB::table('employees')->where('id', 7)->value('uuid'),
        ])
        ->and(DB::table('entity_xref')->where('source_table', 'hchr_activity_log')->value('entity_type'))->toBe('activity_log');
});

test('it applies the decided data fixes and logs each in review_decisions', function () {
    app(HrImporter::class)->run(dryRun: false);

    expect(DB::table('cities')->where('id', 11)->value('name_en'))->toBe('Ar-raqqa')
        ->and(DB::table('cities')->where('id', 1)->value('name_en'))->toBe('Syria')
        ->and(DB::table('centers')->where('id', 15)->value('name_ar'))->toBe('ستادي زون المحطة')
        ->and(DB::table('programs')->orderBy('id')->pluck('parent_id', 'id')->all())->toBe([3 => null, 4 => 3, 11 => 14, 14 => null]);

    expect(DB::table('review_decisions')->orderBy('source_table')->orderBy('source_id')->get(['source_table', 'source_id', 'column_name', 'decision'])->map(fn ($row) => (array) $row)->all())
        ->toBe([
            ['source_table' => 'hchr_centers', 'source_id' => '15', 'column_name' => 'name_ar', 'decision' => 'set'],
            ['source_table' => 'hchr_cities', 'source_id' => '1', 'column_name' => '', 'decision' => 'keep'],
            ['source_table' => 'hchr_cities', 'source_id' => '11', 'column_name' => 'name_en', 'decision' => 'trim'],
            ['source_table' => 'hchr_programs', 'source_id' => '11', 'column_name' => 'parent_id', 'decision' => 'set'],
            ['source_table' => 'hchr_programs', 'source_id' => '4', 'column_name' => 'parent_id', 'decision' => 'set'],
        ]);
});

test('pay figures travel as the source ciphertext, never decrypted or logged', function () {
    $envelope = source()->table('contracts')->where('id', 9)->value('salary');

    app(HrImporter::class)->run(dryRun: false);

    expect(DB::table('contracts')->where('id', 9)->value('salary'))->toBe($envelope)
        ->and(app(SalaryCipher::class)->decrypt(DB::table('contracts')->where('id', 9)->value('salary')))->toBe('1500000')
        ->and(json_encode(DB::table('review_decisions')->get()))->not->toContain($envelope);
});

test('a plaintext pay figure blocks the run before anything is written', function (string $table, array $values) {
    source()->table($table)->updateOrInsert(['id' => 9], $values);

    $report = app(HrImporter::class)->run(dryRun: false);

    expect($report->blocking)->toHaveCount(1)
        ->and($report->blocking[0])->not->toContain('1500000')
        ->and(DB::table('employees')->count())->toBe(0);
})->with([
    'unencrypted salary' => ['contracts', ['salary' => '1500000']],
    'badge amount' => ['badges', ['amount' => '1500000', 'employee_id' => 7, 'date' => '2026-01-01', 'type' => 1]],
]);

test('top-level programs sharing a code block the run', function () {
    sourceRows('programs', [['id' => 6, 'name' => 'School to School', 'code' => '009', 'slug' => 'S2S', 'is_active' => 0]]);

    $report = app(HrImporter::class)->run(dryRun: false);

    expect($report->blocking)->toBe(['Top-level programs 6, 14 share code 009; programs.code is unique among top-level programs.'])
        ->and(DB::table('programs')->count())->toBe(0);
});

test('the hub refuses a second top-level program with the same code, not a sub-program', function () {
    DB::table('programs')->insert(['id' => 3, 'uuid' => 'a', 'name' => 'Relief', 'code' => '003', 'slug' => 'RLF']);
    DB::table('programs')->insert(['id' => 4, 'uuid' => 'b', 'parent_id' => 3, 'name' => 'F2F', 'code' => '003', 'slug' => 'F2F']);

    expect(fn () => DB::table('programs')->insert(['id' => 5, 'uuid' => 'c', 'name' => 'Other', 'code' => '003', 'slug' => 'OTH']))
        ->toThrow(QueryException::class);
});

test('a NULL the hub column refuses blocks the run and names the rows', function () {
    source()->table('employees')->where('id', 7)->update(['first_name_en' => null]);

    $report = app(HrImporter::class)->run(dryRun: false);

    expect($report->blocking)->toBe(['1 employees row(s) have no first_name_en in the source, but the hub column is NOT NULL (ids 7).'])
        ->and(DB::table('employees')->count())->toBe(0);
});

test('a second run changes nothing, and rows gone from the source are pruned', function () {
    app(HrImporter::class)->run(dryRun: false);
    $uuid = DB::table('employees')->where('id', 7)->value('uuid');
    $snapshot = collect(HrImporter::TABLES)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all();

    $second = app(HrImporter::class)->run(dryRun: false);

    expect($second->tables['employees'])->toMatchArray(['inserted' => 0, 'updated' => 1, 'target' => 1])
        ->and(DB::table('employees')->where('id', 7)->value('uuid'))->toBe($uuid)
        ->and(collect(HrImporter::TABLES)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all())->toBe($snapshot)
        ->and(DB::table('review_decisions')->count())->toBe(5);

    source()->statement('PRAGMA query_only = OFF');
    source()->table('activity_log')->where('id', 2)->delete();

    $third = app(HrImporter::class)->run(dryRun: false);

    expect($third->tables['activity_log']['pruned'])->toBe(1)
        ->and(DB::table('activity_log')->where('id', 2)->exists())->toBeFalse()
        ->and(DB::table('entity_xref')->where('source_table', 'hchr_activity_log')->pluck('source_id')->all())->toBe(['1']);
});

test('the source connection is read-only', function () {
    app(HrImporter::class)->run(dryRun: true);

    expect(fn () => source()->table('cities')->insert(['id' => 99, 'name_en' => 'X']))->toThrow(QueryException::class);
});

test('the hub:import command prints the dry-run report', function () {
    $this->artisan('hub:import', ['--dry-run' => true])
        ->expectsOutputToContain('DRY RUN')
        ->expectsOutputToContain('programs #4 parent_id: set')
        ->assertSuccessful();
});
