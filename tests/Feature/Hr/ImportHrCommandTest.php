<?php

use App\Models\Role;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\City;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\News;

/**
 * A tiny HR database: an in-memory SQLite connection with HR's `hchr_`
 * prefix, holding only the tables the import needs for these tests. Every
 * other HR table is missing and is reported as skipped.
 */
function createHrImportFixture(): void
{
    config(['database.connections.hr_fixture' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => 'hchr_',
        'foreign_key_constraints' => false,
    ]]);

    $schema = Schema::connection('hr_fixture');

    $schema->create('cities', function (Blueprint $table): void {
        $table->id();
        $table->string('name_en');
        $table->string('name_ar');
        $table->string('slug');
        $table->string('code');
        $table->string('hr_hotline')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    $schema->create('users', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('employee_id')->nullable();
        $table->string('email');
        $table->string('password');
        $table->timestamp('email_verified_at')->nullable();
        $table->string('remember_token')->nullable();
        $table->timestamp('last_seen_at')->nullable();
        $table->timestamps();
    });

    foreach (['roles', 'permissions'] as $name) {
        $schema->create($name, function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
    }

    $schema->create('role_has_permissions', function (Blueprint $table): void {
        $table->unsignedBigInteger('permission_id');
        $table->unsignedBigInteger('role_id');
    });

    $schema->create('model_has_roles', function (Blueprint $table): void {
        $table->unsignedBigInteger('role_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->boolean('is_active')->default(false);
    });

    $schema->create('employees', function (Blueprint $table): void {
        $table->id();
        $table->string('first_name_en');
        $table->string('first_name_ar');
        $table->string('last_name_en');
        $table->string('last_name_ar');
        $table->unsignedBigInteger('city_id')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    $schema->create('news', function (Blueprint $table): void {
        $table->id();
        $table->string('title_en');
        $table->string('title_ar');
        $table->text('description_ar');
        $table->string('newsable_type')->nullable();
        $table->unsignedBigInteger('newsable_id')->nullable();
        $table->timestamps();
    });

    $hr = DB::connection('hr_fixture');
    $hr->table('cities')->insert([
        ['id' => 1, 'name_en' => 'Syria', 'name_ar' => 'سوريا', 'slug' => 'SYR', 'code' => '000'],
        ['id' => 2, 'name_en' => 'Aleppo', 'name_ar' => 'حلب', 'slug' => 'ALP', 'code' => '002'],
    ]);
    $hr->table('employees')->insert([
        ['id' => 7, 'first_name_en' => 'Rami', 'first_name_ar' => 'رامي', 'last_name_en' => 'Haddad', 'last_name_ar' => 'حداد', 'city_id' => 2],
    ]);
    $hr->table('users')->insert([
        ['id' => 1, 'employee_id' => null, 'email' => 'one@hr.test', 'password' => 'hash-1'],
        ['id' => 2, 'employee_id' => 7, 'email' => 'two@hr.test', 'password' => 'hash-2'],
        ['id' => 9, 'employee_id' => null, 'email' => 'nine@hr.test', 'password' => 'hash-9'],
    ]);
    $hr->table('roles')->insert([
        ['id' => 1, 'name' => 'HR manager', 'guard_name' => 'web'],
    ]);
    $hr->table('permissions')->insert([
        ['id' => 1, 'name' => 'employees.view', 'guard_name' => 'web'],
    ]);
    $hr->table('role_has_permissions')->insert(['permission_id' => 1, 'role_id' => 1]);
    $hr->table('model_has_roles')->insert(['role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => 2, 'is_active' => true]);
    $hr->table('news')->insert([
        'id' => 3, 'title_en' => 'Opening', 'title_ar' => 'افتتاح', 'description_ar' => 'افتتاح المركز', 'newsable_type' => 'App\\Models\\City', 'newsable_id' => 2,
    ]);
}

/**
 * Two tracking users whose tracking ids are each other's HR ids, so the
 * renumbering has to swap them, each owning a to-do and a role.
 *
 * @return array{first: int, second: int}
 */
function insertCrossedTrackingUsers(): array
{
    DB::table('users')->insert([
        ['id' => 1, 'hr_user_id' => 2],
        ['id' => 2, 'hr_user_id' => 1],
    ]);
    DB::table('user_todos')->insert([
        ['user_id' => 1, 'title' => 'todo of HR user 2'],
        ['user_id' => 2, 'title' => 'todo of HR user 1'],
    ]);
    $role = Role::query()->firstOrCreate(['name' => 'Team Member', 'guard_name' => 'web']);
    DB::table('model_has_roles')->insert(['role_id' => $role->id, 'model_type' => 'user', 'model_id' => 1]);

    return ['first' => 1, 'second' => 2];
}

beforeEach(fn () => createHrImportFixture());

test('the import renumbers crossed tracking users to their HR ids and moves what they own', function () {
    insertCrossedTrackingUsers();

    $this->artisan('hub:import-hr', ['--source' => 'hr_fixture'])->assertSuccessful();

    expect(DB::table('users')->whereColumn('id', '!=', 'hr_user_id')->count())->toBe(0)
        ->and(DB::table('user_todos')->where('user_id', 2)->value('title'))->toBe('todo of HR user 2')
        ->and(DB::table('user_todos')->where('user_id', 1)->value('title'))->toBe('todo of HR user 1')
        ->and(DB::table('model_has_roles')->where('model_id', 2)->pluck('role_id')->all())
        ->toContain(Role::query()->where('name', 'Team Member')->value('id'));
});

test('the import loads HR accounts, cities, roles and HR-owned rows', function () {
    insertCrossedTrackingUsers();

    $this->artisan('hub:import-hr', ['--source' => 'hr_fixture'])->assertSuccessful();

    $hrManager = Role::query()->where('name', 'HR manager')->value('id');

    expect(DB::table('users')->where('id', 2)->first(['email', 'password', 'employee_id', 'active_role_id']))
        ->toEqual((object) ['email' => 'two@hr.test', 'password' => 'hash-2', 'employee_id' => 7, 'active_role_id' => $hrManager])
        ->and(DB::table('users')->where('id', 9)->value('hr_user_id'))->toBe(9)
        ->and(City::query()->pluck('name_ar', 'id')->all())->toBe([1 => 'سوريا', 2 => 'حلب', 17 => 'الوادي'])
        ->and(Employee::query()->forCity(2)->pluck('id')->all())->toBe([7])
        ->and(DB::table('model_has_roles')->where('role_id', $hrManager)->first(['model_type', 'model_id']))
        ->toEqual((object) ['model_type' => 'user', 'model_id' => 2])
        ->and(News::query()->findOrFail(3)->newsable)->toBeInstanceOf(City::class);
});

test('running the import again changes nothing', function () {
    insertCrossedTrackingUsers();
    $this->artisan('hub:import-hr', ['--source' => 'hr_fixture'])->assertSuccessful();
    $before = DB::table('user_todos')->orderBy('id')->get(['id', 'user_id'])->all();

    $this->artisan('hub:import-hr', ['--source' => 'hr_fixture'])
        ->expectsOutputToContain('no renumbering needed')
        ->assertSuccessful();

    expect(DB::table('user_todos')->orderBy('id')->get(['id', 'user_id'])->all())->toEqual($before)
        ->and(DB::table('users')->count())->toBe(3)
        ->and(DB::table('employees')->count())->toBe(1);
});

test('an import that would leave orphan rows is rolled back', function () {
    DB::connection('hr_fixture')->table('employees')->where('id', 7)->update(['city_id' => 99]);

    $this->artisan('hub:import-hr', ['--source' => 'hr_fixture'])->assertFailed();

    expect(DB::table('employees')->count())->toBe(0)
        ->and(DB::table('cities')->count())->toBe(0);
});
