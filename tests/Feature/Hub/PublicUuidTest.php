<?php

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Hr\Models\Employee;

function publicUuidMigration(): Migration
{
    return require database_path('migrations/2026_10_30_000001_add_uuid_to_shared_master_tables.php');
}

/**
 * One fresh row of each shared master model, keyed by table. City, center,
 * program and department have no factories, so they are created directly.
 *
 * @return array<string, Model>
 */
function sharedMasterModels(): array
{
    $sequence = Str::lower(Str::random(6));

    $city = City::forceCreate([
        'name_en' => 'Uuid City', 'name_ar' => 'مدينة', 'slug' => 'U'.$sequence, 'code' => 'c'.$sequence,
    ]);

    return [
        'users' => User::factory()->create(),
        'employees' => Employee::factory()->create(),
        'cities' => $city,
        'centers' => Center::forceCreate(['name_en' => 'Uuid Center', 'name_ar' => 'مركز', 'city_id' => $city->id]),
        'programs' => Program::forceCreate(['name' => 'Uuid Program', 'code' => 'p'.$sequence, 'slug' => 'uuid-'.$sequence]),
        'departments' => Department::forceCreate(['name' => 'Uuid Department', 'code' => 'd'.$sequence]),
    ];
}

test('the migration covers exactly the six shared master tables', function () {
    expect(publicUuidMigration()::TABLES)
        ->toEqualCanonicalizing(['users', 'employees', 'cities', 'centers', 'programs', 'departments']);
});

test('every shared master model gets a uuid on create, unique across rows', function () {
    $first = sharedMasterModels();
    $second = sharedMasterModels();

    foreach ($first as $table => $model) {
        $uuid = $model->getAttribute('uuid');

        expect(Str::isUuid($uuid))->toBeTrue("{$table} has no uuid")
            ->and($model->getKey())->toBeInt()
            ->and($model->getRouteKeyName())->toBe('id')
            ->and(DB::table($table)->where('id', $model->getKey())->value('uuid'))->toBe($uuid)
            ->and($second[$table]->getAttribute('uuid'))->not->toBe($uuid);
    }
});

test('a uuid stays the same on update, even when something tries to replace it', function () {
    foreach (sharedMasterModels() as $table => $model) {
        $uuid = $model->getAttribute('uuid');

        $model->forceFill(['uuid' => (string) Str::uuid7()])->touch();
        $model->forceFill(['updated_at' => now()->addMinute()])->save();

        expect($model->getAttribute('uuid'))->toBe($uuid, "{$table} uuid changed")
            ->and(DB::table($table)->where('id', $model->getKey())->value('uuid'))->toBe($uuid);
    }
});

test('a uuid passed before the first save is kept', function () {
    $uuid = (string) Str::uuid7();

    $user = User::factory()->make();
    $user->forceFill(['uuid' => $uuid])->save();

    expect($user->fresh()->uuid)->toBe($uuid);
});

test('the migration backfills existing rows and down removes the column', function () {
    $models = sharedMasterModels();
    $migration = publicUuidMigration();

    $migration->down();

    foreach ($migration::TABLES as $table) {
        expect(Schema::hasColumn($table, 'uuid'))->toBeFalse();
    }

    $migration->up();

    foreach ($models as $table => $model) {
        $uuids = DB::table($table)->pluck('uuid');

        expect($uuids)->not->toBeEmpty()
            ->and($uuids->every(fn (?string $uuid) => Str::isUuid($uuid)))->toBeTrue("{$table} has a row without a uuid")
            ->and($uuids->unique()->count())->toBe($uuids->count());
    }
});
