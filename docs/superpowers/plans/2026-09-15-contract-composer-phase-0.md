# Contract Composer Phase 0 — Database & Core Models Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Land the schema and model layer the rest of the Contract Composer restructure builds on: consolidate `contract_types` from 6 rows to 3, add the schedule/pay columns and the `contract_pay_tiers` table to `contracts`, and expose them on `Contract`/`ContractPayTier` plus two new enums. No behavior in the composer, `ContractObserver`, or `LeaveBalanceService` changes in this phase — that is Phase 1.

**Architecture:** One new migration file performs both the `contract_types` data consolidation and the additive schema changes, matching this repo's existing pattern of pairing a schema change with its one-time data backfill in a single migration (see `2026_09_14_151040_move_type_and_place_from_contract_types_to_contracts.php`). Because the migration mixes DDL and DML, it is tested by `require`-ing the file directly and calling `up()`/`down()` on the returned anonymous `Migration` instance — the same resolution Laravel's own migrator uses — rather than by asserting on the schema state `RefreshDatabase` already produced.

**Tech Stack:** Laravel 12 migrations/Eloquent, PHPUnit 11 (`RefreshDatabase`, sqlite `:memory:`), `bensampo/laravel-enum`.

**Spec:** [docs/superpowers/specs/2026-09-15-contract-composer-restructure.md](../specs/2026-09-15-contract-composer-restructure.md) — this plan implements **Phase 0** only.

## Global Constraints

- Translatable columns follow the `{attribute}_{locale}` convention (`condition_en`/`condition_ar`) with a `HasTranslation`-backed virtual accessor — see `App\Models\ContractType::name()` for the exact shape to copy.
- Enums extend `BenSampo\Enum\Enum`, are `final`, and expose `getLocalizedName(int $value): array` returning `['value' => string, 'color' => string]` keyed off `app()->getLocale()` — see `App\Enums\SalaryPer` for the exact shape to copy. (`App\Enums\Currency` uses a different, older `['en' => ..., 'ar' => ...]` shape — do not copy that one.)
- Tests run on sqlite `:memory:` (`phpunit.xml`) via `RefreshDatabase`; the `sqlite` connection has `foreign_key_constraints` **on** by default, so any row inserted directly into `contracts`/`positions`/etc. inside a test must either satisfy real foreign keys or run inside `Schema::withoutForeignKeyConstraints(...)`.
- Run a single test with `php artisan test --filter=SomeTest` (per `CLAUDE.md`); run the whole suite with `composer test`.
- No `app/Http/Kernel.php` in this Laravel 12 app — none of this phase touches routing/middleware, so this is only relevant as a reminder not to look for one.
- This phase must not change any composer/observer/service behavior — if a step in this plan seems to require touching `ContractObserver.php`, `LeaveBalanceService.php`, or any `app/Livewire/Contract/**` file, stop; that belongs to a later phase.
- **Already fixed, commit `ea287764`:** `2026_09_14_151040_move_type_and_place_from_contract_types_to_contracts.php` originally used MySQL-only `UPDATE ... INNER JOIN ... SET` syntax, which made every `RefreshDatabase`-based test in the whole suite fail (confirmed: 53/53 in `ContractRenewalTest`) since that migration runs while building the test schema. It's now correlated subqueries, equivalent on MySQL and SQLite. If a task's test run still shows every test failing with `SQLSTATE[HY000] ... near "INNER"`, your checkout predates this fix — do not re-diagnose it, just confirm you're on top of commit `ea287764` or later.
- **Environment:** the `php` resolved on `PATH` in a plain shell here is 8.3.21, but this project's composer dependencies require >= 8.4.1, so plain `php artisan test`/`composer test` fail with a platform_check error. Use `/c/laragon/bin/php/php-8.4.21/php` (bash) or `C:\laragon\bin\php\php-8.4.21\php` (PowerShell) explicitly for every `artisan`/`composer` command in this plan.

---

### Task 1: Migration — consolidate `contract_types` to 3 rows

**Files:**
- Create: `database/migrations/2026_09_15_000000_restructure_contracts_schedule_pay_and_draft.php`
- Test: `tests/Feature/Migrations/RestructureContractsScheduleAndPayMigrationTest.php`

**Interfaces:**
- Consumes: nothing new — operates on the existing `contract_types` (`id`, `name_en`, `name_ar`) and `contracts` (`contract_type_id`) columns from `2025_06_20_115710_basic_tables.php`.
- Produces: `contract_types` left with exactly ids `1` (`Volunteer Contract` / `عقد تطوّع`), `3` (`Trial Volunteer Contract` / `عقد تطوّع تجريبي`), `5` (`Specific Voluntary Service Contract` / `عقد تقديم خدمة تطوعية محددة`); every `contracts.contract_type_id` that pointed at `2`, `4`, or `6` remapped to `1`, `3`, `5` respectively. Task 2 appends to the **same** migration file's `up()`/`down()` — do not create a second migration file.

- [x] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestructureContractsScheduleAndPayMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_remaps_legacy_contract_type_ids_and_drops_the_redundant_rows(): void
    {
        $migration = $this->migrationInstance();

        // RefreshDatabase already ran up() once while building the schema for
        // this test. Roll it back to the pre-consolidation, 6-row shape so a
        // legacy contract pointing at the redundant id=2 row can be recreated.
        $migration->down();

        DB::table('contract_types')->delete();
        DB::table('contract_types')->insert([
            ['id' => 1, 'name_en' => 'Full Annual', 'name_ar' => 'سنوي كامل', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name_en' => 'Partial Annual', 'name_ar' => 'سنوي جزئي', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // RefreshDatabase wraps every test in an uncommitted transaction, and
        // SQLite's `PRAGMA foreign_keys` toggle (what
        // Schema::withoutForeignKeyConstraints() uses) is a documented no-op
        // once a transaction is already open, so it cannot let this
        // deliberately-orphaned fixture row past the FK on
        // employee_id/position_id. `PRAGMA defer_foreign_keys`, unlike
        // `foreign_keys`, CAN be toggled mid-transaction: it defers FK
        // enforcement to commit time, which this rolled-back test transaction
        // never reaches.
        DB::statement('PRAGMA defer_foreign_keys = ON');
        DB::table('contracts')->insert([
            'id' => 501,
            'employee_id' => 1,
            'position_id' => 1,
            'contract_type_id' => 2,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'currency' => 2,
            'salary_per' => 1,
            'weekly_hours' => 40,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertDatabaseMissing('contract_types', ['id' => 2]);
        $this->assertDatabaseHas('contracts', ['id' => 501, 'contract_type_id' => 1]);
        $this->assertSame(1, DB::table('contract_types')->count());
    }

    public function test_up_renames_the_three_surviving_rows_to_their_final_bilingual_names(): void
    {
        $migration = $this->migrationInstance();
        $migration->down();

        DB::table('contract_types')->delete();
        DB::table('contract_types')->insert([
            ['id' => 1, 'name_en' => 'Full Annual', 'name_ar' => 'سنوي كامل', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name_en' => 'Trial Full', 'name_ar' => 'تجريبي كامل', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name_en' => 'Online Agreement', 'name_ar' => 'اتفاق اونلاين', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration->up();

        $this->assertDatabaseHas('contract_types', ['id' => 1, 'name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع']);
        $this->assertDatabaseHas('contract_types', ['id' => 3, 'name_en' => 'Trial Volunteer Contract', 'name_ar' => 'عقد تطوّع تجريبي']);
        $this->assertDatabaseHas('contract_types', ['id' => 5, 'name_en' => 'Specific Voluntary Service Contract', 'name_ar' => 'عقد تقديم خدمة تطوعية محددة']);
    }

    public function test_down_restores_a_six_row_contract_types_table_as_a_best_effort(): void
    {
        // RefreshDatabase's initial migrate:fresh already ran this
        // migration's up(), but contract_types has no seed data of its own —
        // 2025_06_20_115710_basic_tables.php only creates the empty table,
        // and this test class does not opt into $seed/$seeder — so the table
        // is actually still empty at this point. Recreate the post-up()
        // state (3 survivor rows at their final names) that down() is meant
        // to expand back to 6 rows.
        DB::table('contract_types')->delete();
        DB::table('contract_types')->insert([
            ['id' => 1, 'name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name_en' => 'Trial Volunteer Contract', 'name_ar' => 'عقد تطوّع تجريبي', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name_en' => 'Specific Voluntary Service Contract', 'name_ar' => 'عقد تقديم خدمة تطوعية محددة', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration = $this->migrationInstance();

        $migration->down();

        $this->assertSame(6, DB::table('contract_types')->count());
        foreach ([2, 4, 6] as $droppedId) {
            $this->assertDatabaseHas('contract_types', ['id' => $droppedId]);
        }
    }

    private function migrationInstance(): Migration
    {
        return require database_path('migrations/2026_09_15_000000_restructure_contracts_schedule_pay_and_draft.php');
    }
}
```

- [x] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=RestructureContractsScheduleAndPayMigrationTest`
Expected: FAIL — `require` cannot find the migration file (it does not exist yet).

- [x] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * contract_types ids 2, 4 and 6 (Partial Annual / Trial Partial / the
     * second service-agreement row) are redundant now that `type` and
     * `place` live on contracts itself (see the 2026_09_14 migration this
     * one follows): every contract pointing at one of them is remapped to
     * its category-mate (1, 3, 5) before the redundant rows are dropped,
     * and the three survivors are renamed to the category names the
     * composer now uses everywhere.
     *
     * Consolidating destroys which of the two original rows a contract used
     * to point at, so down() cannot restore the exact original assignment —
     * it recreates 2, 4 and 6 as best-effort duplicates of 1, 3 and 5 (same
     * names) without reassigning any contract back to them, the same
     * best-effort tradeoff the 2026_09_14 migration already accepted.
     */
    private const MERGES = [2 => 1, 4 => 3, 6 => 5];

    private const FINAL_NAMES = [
        1 => ['name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع'],
        3 => ['name_en' => 'Trial Volunteer Contract', 'name_ar' => 'عقد تطوّع تجريبي'],
        5 => ['name_en' => 'Specific Voluntary Service Contract', 'name_ar' => 'عقد تقديم خدمة تطوعية محددة'],
    ];

    public function up(): void
    {
        foreach (self::MERGES as $from => $to) {
            DB::table('contracts')->where('contract_type_id', $from)->update(['contract_type_id' => $to]);
        }

        DB::table('contract_types')->whereIn('id', array_keys(self::MERGES))->delete();

        foreach (self::FINAL_NAMES as $id => $names) {
            DB::table('contract_types')->where('id', $id)->update($names);
        }
    }

    public function down(): void
    {
        foreach (self::MERGES as $droppedId => $survivorId) {
            $survivor = DB::table('contract_types')->where('id', $survivorId)->first();

            if ($survivor) {
                DB::table('contract_types')->insert([
                    'id' => $droppedId,
                    'name_en' => $survivor->name_en,
                    'name_ar' => $survivor->name_ar,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
```

- [x] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter=RestructureContractsScheduleAndPayMigrationTest`
Expected: PASS (all 3 tests)

- [x] **Step 5: Commit**

```bash
git add database/migrations/2026_09_15_000000_restructure_contracts_schedule_pay_and_draft.php tests/Feature/Migrations/RestructureContractsScheduleAndPayMigrationTest.php
git commit -m "feat(contracts): consolidate contract_types to 3 categories"
```

---

### Task 2: Migration — add schedule/pay columns and `contract_pay_tiers` table

**Files:**
- Modify: `database/migrations/2026_09_15_000000_restructure_contracts_schedule_pay_and_draft.php` (same file as Task 1)
- Modify: `tests/Feature/Migrations/RestructureContractsScheduleAndPayMigrationTest.php` (same file as Task 1)

**Interfaces:**
- Consumes: the `contracts` table from `2025_06_20_115710_basic_tables.php` (columns up to `weekly_hours`) plus `type`/`place` from `2026_09_14_151040_...php`.
- Produces: `contracts` gains `schedule_mode` (tinyint, default 1), `work_day_from`/`work_day_to` (nullable tinyint), `show_days_per_week`/`show_hours_per_week` (boolean, default false), `flex_days_per_week` (nullable tinyint), `flex_hours_per_week` (nullable smallint), `flex_hours_per_day` (nullable tinyint), `flex_varies_by_requirements`/`flex_coordinated_with_manager` (boolean, default false), `flex_custom_en`/`flex_custom_ar` (nullable string), `pay_mode` (tinyint, default 1), `session_duration` (nullable tinyint). New table `contract_pay_tiers` (`id`, `contract_id` FK cascade-delete, `condition_en`, `condition_ar`, `amount` decimal(10,2), `sort_order` unsigned int default 0, timestamps) — consumed by Task 6's `Contract::payTiers()` and `ContractPayTier` model.

- [x] **Step 1: Write the failing tests**

Append to `tests/Feature/Migrations/RestructureContractsScheduleAndPayMigrationTest.php`. Task 1 left this file's imports without `Illuminate\Support\Facades\Schema` (it wasn't needed there) — add `use Illuminate\Support\Facades\Schema;` back to the `use` block at the top of the file, since these new tests need it:

```php
    public function test_up_adds_the_new_schedule_and_pay_columns_to_contracts(): void
    {
        foreach ([
            'schedule_mode', 'work_day_from', 'work_day_to',
            'show_days_per_week', 'show_hours_per_week',
            'flex_days_per_week', 'flex_hours_per_week', 'flex_hours_per_day',
            'flex_varies_by_requirements', 'flex_coordinated_with_manager',
            'flex_custom_en', 'flex_custom_ar',
            'pay_mode', 'session_duration',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('contracts', $column), "contracts.$column should exist after migrating.");
        }
    }

    public function test_up_creates_the_contract_pay_tiers_table(): void
    {
        $this->assertTrue(Schema::hasTable('contract_pay_tiers'));

        foreach (['id', 'contract_id', 'condition_en', 'condition_ar', 'amount', 'sort_order'] as $column) {
            $this->assertTrue(Schema::hasColumn('contract_pay_tiers', $column), "contract_pay_tiers.$column should exist.");
        }
    }

    public function test_down_drops_the_new_columns_and_the_pay_tiers_table(): void
    {
        $migration = $this->migrationInstance();

        $migration->down();

        $this->assertFalse(Schema::hasColumn('contracts', 'schedule_mode'));
        $this->assertFalse(Schema::hasTable('contract_pay_tiers'));
    }
```

- [x] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=RestructureContractsScheduleAndPayMigrationTest`
Expected: FAIL — `schedule_mode` and the other new columns/table do not exist yet (the first two new tests fail; the `down()` test passes vacuously since the columns are already absent, which is why the next step's assertions must be checked against the full, updated file).

- [x] **Step 3: Extend the migration**

Replace the migration's `up()`/`down()` bodies (keep `MERGES`/`FINAL_NAMES` and the existing consolidation code from Task 1 — only add what follows):

```php
    public function up(): void
    {
        foreach (self::MERGES as $from => $to) {
            DB::table('contracts')->where('contract_type_id', $from)->update(['contract_type_id' => $to]);
        }

        DB::table('contract_types')->whereIn('id', array_keys(self::MERGES))->delete();

        foreach (self::FINAL_NAMES as $id => $names) {
            DB::table('contract_types')->where('id', $id)->update($names);
        }

        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedTinyInteger('schedule_mode')->default(1)->after('place');
            $table->unsignedTinyInteger('work_day_from')->nullable()->after('work_hours_to');
            $table->unsignedTinyInteger('work_day_to')->nullable()->after('work_day_from');
            $table->boolean('show_days_per_week')->default(false)->after('work_day_to');
            $table->boolean('show_hours_per_week')->default(false)->after('show_days_per_week');
            $table->unsignedTinyInteger('flex_days_per_week')->nullable()->after('show_hours_per_week');
            $table->unsignedSmallInteger('flex_hours_per_week')->nullable()->after('flex_days_per_week');
            $table->unsignedTinyInteger('flex_hours_per_day')->nullable()->after('flex_hours_per_week');
            $table->boolean('flex_varies_by_requirements')->default(false)->after('flex_hours_per_day');
            $table->boolean('flex_coordinated_with_manager')->default(false)->after('flex_varies_by_requirements');
            $table->string('flex_custom_en')->nullable()->after('flex_coordinated_with_manager');
            $table->string('flex_custom_ar')->nullable()->after('flex_custom_en');
            $table->unsignedTinyInteger('pay_mode')->default(1)->after('salary_per');
            $table->unsignedTinyInteger('session_duration')->nullable()->after('pay_mode');
        });

        Schema::create('contract_pay_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('condition_en');
            $table->string('condition_ar');
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_pay_tiers');

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'schedule_mode', 'work_day_from', 'work_day_to',
                'show_days_per_week', 'show_hours_per_week',
                'flex_days_per_week', 'flex_hours_per_week', 'flex_hours_per_day',
                'flex_varies_by_requirements', 'flex_coordinated_with_manager',
                'flex_custom_en', 'flex_custom_ar',
                'pay_mode', 'session_duration',
            ]);
        });

        foreach (self::MERGES as $droppedId => $survivorId) {
            $survivor = DB::table('contract_types')->where('id', $survivorId)->first();

            if ($survivor) {
                DB::table('contract_types')->insert([
                    'id' => $droppedId,
                    'name_en' => $survivor->name_en,
                    'name_ar' => $survivor->name_ar,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
```

- [x] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter=RestructureContractsScheduleAndPayMigrationTest`
Expected: PASS (all 6 tests)

- [x] **Step 5: Run migrate + rollback against a real (non-test) database copy**

This is the one step in the whole plan that must run against a real MySQL dev database, never production, per `CLAUDE.md`'s test/dev split.

Run: `php artisan migrate` then `php artisan migrate:rollback` then `php artisan migrate` again on a dev database copy.
Expected: no errors either direction; after the final `migrate`, `contract_types` has exactly 3 rows and `contracts`/`contract_pay_tiers` have the new columns/table.

- [x] **Step 6: Commit**

```bash
git add database/migrations/2026_09_15_000000_restructure_contracts_schedule_pay_and_draft.php tests/Feature/Migrations/RestructureContractsScheduleAndPayMigrationTest.php
git commit -m "feat(contracts): add schedule/pay columns and contract_pay_tiers table"
```

---

### Task 3: Config and seeder for the 3 consolidated categories

**Files:**
- Modify: `config/contracts.php`
- Modify: `config/samples/sampleData.php`
- Modify: `database/seeders/ContractTypeSeeder.php`
- Test: `tests/Feature/ContractTypeSeederTest.php`

**Interfaces:**
- Consumes: `App\Models\ContractType::getCategoryAttribute()` (`app/Models/ContractType.php:26-31`, unchanged), which reads `config('contracts.map_by_id')[$this->id]`.
- Produces: seeding `ContractTypeSeeder` on a **fresh, empty** `contract_types` table (a brand-new dev machine, CI, or `RefreshDatabase`) creates rows at ids `1`, `3`, `5` — matching the ids the Task 1 migration leaves behind on an **existing** database. Without forcing these ids, `ContractType::create()` would auto-increment to `1, 2, 3` on an empty table, which would not match `config('contracts.map_by_id')`'s keys and every fresh install's contract types would silently resolve to the `'annual'` fallback category. Later phases (2-4) read `->category` to decide which composer step to show — this must be right everywhere, not just against the existing production data.

- [x] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\ContractType;
use Database\Seeders\ContractTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTypeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_a_fresh_database_creates_exactly_the_three_consolidated_rows_at_their_canonical_ids(): void
    {
        $this->seed(ContractTypeSeeder::class);

        $this->assertSame(3, ContractType::count());
        $this->assertDatabaseHas('contract_types', ['id' => 1, 'name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع']);
        $this->assertDatabaseHas('contract_types', ['id' => 3, 'name_en' => 'Trial Volunteer Contract', 'name_ar' => 'عقد تطوّع تجريبي']);
        $this->assertDatabaseHas('contract_types', ['id' => 5, 'name_en' => 'Specific Voluntary Service Contract', 'name_ar' => 'عقد تقديم خدمة تطوعية محددة']);
    }

    public function test_each_freshly_seeded_type_resolves_to_its_expected_category(): void
    {
        $this->seed(ContractTypeSeeder::class);

        $this->assertSame('annual', ContractType::find(1)->category);
        $this->assertSame('trial', ContractType::find(3)->category);
        $this->assertSame('service', ContractType::find(5)->category);
    }
}
```

- [x] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ContractTypeSeederTest`
Expected: FAIL — the seeder currently produces 6 rows at ids `1..6` with the pre-consolidation names, so both `assertDatabaseHas` calls and the category assertions fail.

- [x] **Step 3: Update the config and seeder**

`config/contracts.php` — replace the file:

```php
<?php

return [

    'map_by_id' => [
        1 => 'annual',
        3 => 'trial',
        5 => 'service',
    ],
];
```

`config/samples/sampleData.php` — replace the `'ContractType' => [...]` array (leave every other key in the file untouched):

```php
    'ContractType' => [
        ['id' => 1, 'name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع'],
        ['id' => 3, 'name_en' => 'Trial Volunteer Contract', 'name_ar' => 'عقد تطوّع تجريبي'],
        ['id' => 5, 'name_en' => 'Specific Voluntary Service Contract', 'name_ar' => 'عقد تقديم خدمة تطوعية محددة'],
    ],
```

`database/seeders/ContractTypeSeeder.php` — replace the file (switches `create()` to `forceCreate()` so the explicit `id` in each row is honored instead of silently dropped as a non-`$fillable` attribute):

```php
<?php

namespace Database\Seeders;

use App\Models\ContractType;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = config('samples.sampleData.ContractType');

        foreach ($types as $type) {
            ContractType::query()->forceCreate($type);
        }
    }
}
```

- [x] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=ContractTypeSeederTest`
Expected: PASS

- [x] **Step 5: Commit**

```bash
git add config/contracts.php config/samples/sampleData.php database/seeders/ContractTypeSeeder.php tests/Feature/ContractTypeSeederTest.php
git commit -m "feat(contracts): seed the 3 consolidated contract types at their canonical ids"
```

---

### Task 4: `ContractType` enum — add `Draft`

**Files:**
- Modify: `app/Enums/ContractType.php`
- Test: `tests/Unit/ContractTypeEnumTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Enums\ContractType::Draft === 4`, `getLocalizedName(4)` returns `['value' => 'Draft'|'مسودة', 'color' => ...]`. Phase 1's employee-create draft flow and `ContractObserver` will compare `$contract->type` against this constant — not built in this phase.

- [x] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Enums\ContractType;
use Tests\TestCase;

class ContractTypeEnumTest extends TestCase
{
    public function test_draft_is_type_value_four_localized_in_both_languages(): void
    {
        $this->assertSame(4, ContractType::Draft);

        app()->setLocale('en');
        $this->assertSame('Draft', ContractType::getLocalizedName(ContractType::Draft)['value']);

        app()->setLocale('ar');
        $this->assertSame('مسودة', ContractType::getLocalizedName(ContractType::Draft)['value']);
    }
}
```

- [x] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ContractTypeEnumTest`
Expected: FAIL — `ContractType::Draft` is an undefined class constant.

- [x] **Step 3: Add the enum value**

```php
<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ContractType extends Enum
{
    const Unknown = 0;

    const FullTime = 1;

    const PartTime = 2;

    const Freelance = 3;

    const Draft = 4;

    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::FullTime => ['text' => ['en' => 'Full Time', 'ar' => 'دوام كامل'], 'color' => 'green'],
            self::PartTime => ['text' => ['en' => 'Part Time', 'ar' => 'دوام جزئي'], 'color' => 'blue'],
            self::Freelance => ['text' => ['en' => 'Freelance', 'ar' => 'دوام حر'], 'color' => 'yellow'],
            self::Draft => ['text' => ['en' => 'Draft', 'ar' => 'مسودة'], 'color' => 'gray'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'gray'],
        };
        $result = [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];

        return $result;
    }
}
```

- [x] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=ContractTypeEnumTest`
Expected: PASS

- [x] **Step 5: Commit**

```bash
git add app/Enums/ContractType.php tests/Unit/ContractTypeEnumTest.php
git commit -m "feat(contracts): add the Draft contract type value"
```

---

### Task 5: New `SessionDuration` enum

**Files:**
- Create: `app/Enums/SessionDuration.php`
- Test: `tests/Unit/SessionDurationEnumTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Enums\SessionDuration::{Hour=1, HourAndHalf=2, TwoHours=3}` + `getLocalizedName()`. Phase 4's pay step reads this when `salary_per === SalaryPer::Session`; the `session_duration` column added in Task 2 stores its value — not wired up in this phase.

- [x] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Enums\SessionDuration;
use Tests\TestCase;

class SessionDurationEnumTest extends TestCase
{
    public function test_the_three_session_lengths_are_localized_in_both_languages(): void
    {
        app()->setLocale('en');
        $this->assertSame('Hour', SessionDuration::getLocalizedName(SessionDuration::Hour)['value']);
        $this->assertSame('Hour and a Half', SessionDuration::getLocalizedName(SessionDuration::HourAndHalf)['value']);
        $this->assertSame('Two Hours', SessionDuration::getLocalizedName(SessionDuration::TwoHours)['value']);

        app()->setLocale('ar');
        $this->assertSame('ساعة', SessionDuration::getLocalizedName(SessionDuration::Hour)['value']);
        $this->assertSame('ساعة ونصف', SessionDuration::getLocalizedName(SessionDuration::HourAndHalf)['value']);
        $this->assertSame('ساعتان', SessionDuration::getLocalizedName(SessionDuration::TwoHours)['value']);
    }
}
```

- [x] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=SessionDurationEnumTest`
Expected: FAIL — `App\Enums\SessionDuration` does not exist.

- [x] **Step 3: Write the enum**

```php
<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SessionDuration extends Enum
{
    const Unknown = 0;

    const Hour = 1;

    const HourAndHalf = 2;

    const TwoHours = 3;

    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Hour => ['text' => ['en' => 'Hour', 'ar' => 'ساعة'], 'color' => 'green'],
            self::HourAndHalf => ['text' => ['en' => 'Hour and a Half', 'ar' => 'ساعة ونصف'], 'color' => 'blue'],
            self::TwoHours => ['text' => ['en' => 'Two Hours', 'ar' => 'ساعتان'], 'color' => 'yellow'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'gray'],
        };
        $result = [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];

        return $result;
    }
}
```

- [x] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=SessionDurationEnumTest`
Expected: PASS

- [x] **Step 5: Commit**

```bash
git add app/Enums/SessionDuration.php tests/Unit/SessionDurationEnumTest.php
git commit -m "feat(contracts): add the SessionDuration enum"
```

---

### Task 6: `Contract` model additions + new `ContractPayTier` model

**Files:**
- Modify: `app/Models/Contract.php`
- Create: `app/Models/ContractPayTier.php`
- Test: `tests/Feature/ContractScheduleAndPayColumnsTest.php`

**Interfaces:**
- Consumes: the columns/table from Task 2 (`contracts.schedule_mode`...`session_duration`, `contract_pay_tiers`); `App\Traits\HasTranslation` (`app/Traits/HasTranslation.php`) for `ContractPayTier::condition()`, the same way `App\Models\ContractType::name()` uses it.
- Produces: `Contract::payTiers(): HasMany<ContractPayTier>` ordered by `sort_order`; `App\Models\ContractPayTier` with `$fillable = ['contract_id', 'condition_en', 'condition_ar', 'amount', 'sort_order']`, a `condition` virtual attribute, and a `contract()` `belongsTo` relation. Phase 4's pay-tier repeater UI (`_pay.blade.php`) will call `$contract->payTiers()->create([...])` against this relation — not built in this phase.

Two model changes are deliberately **not** made here, to keep this task's scope to exactly what the spec asks for: `ContractPayTier` does not use `LogsActivity` (the spec's Phase 0 file list only calls for a plain model), and `Contract::getActivitylogOptions()`'s `logOnly` array is not extended to include the new columns (the spec does not ask for activity-log coverage of the new fields in this phase).

- [x] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Contract;
use App\Models\ContractPayTier;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class ContractScheduleAndPayColumnsTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_the_new_schedule_and_pay_columns_are_mass_assignable_and_cast(): void
    {
        $contract = $this->makeContract([
            'schedule_mode' => 2,
            'work_day_from' => 0,
            'work_day_to' => 4,
            'show_days_per_week' => true,
            'show_hours_per_week' => false,
            'flex_days_per_week' => 5,
            'flex_hours_per_week' => 40,
            'flex_hours_per_day' => 8,
            'flex_varies_by_requirements' => true,
            'flex_coordinated_with_manager' => false,
            'flex_custom_en' => 'By arrangement',
            'flex_custom_ar' => 'حسب الاتفاق',
            'pay_mode' => 2,
            'session_duration' => 3,
        ]);

        $fresh = $contract->fresh();

        $this->assertSame(2, $fresh->schedule_mode);
        $this->assertTrue($fresh->show_days_per_week);
        $this->assertFalse($fresh->show_hours_per_week);
        $this->assertTrue($fresh->flex_varies_by_requirements);
        $this->assertFalse($fresh->flex_coordinated_with_manager);
        $this->assertSame('By arrangement', $fresh->flex_custom_en);
        $this->assertSame(3, $fresh->session_duration);
    }

    public function test_pay_tiers_relation_returns_tiers_ordered_by_sort_order(): void
    {
        $contract = $this->makeContract();

        $contract->payTiers()->create(['condition_en' => 'Second tier', 'condition_ar' => 'الشريحة الثانية', 'amount' => 300, 'sort_order' => 2]);
        $contract->payTiers()->create(['condition_en' => 'First tier', 'condition_ar' => 'الشريحة الأولى', 'amount' => 200, 'sort_order' => 1]);

        $ordered = $contract->fresh()->payTiers;

        $this->assertCount(2, $ordered);
        $this->assertInstanceOf(ContractPayTier::class, $ordered->first());
        $this->assertSame('First tier', $ordered->first()->condition_en);
        $this->assertSame('Second tier', $ordered->last()->condition_en);
    }

    public function test_condition_resolves_to_the_current_locale_like_contract_type_name_does(): void
    {
        $contract = $this->makeContract();
        $tier = $contract->payTiers()->create(['condition_en' => 'If 60h/month', 'condition_ar' => 'إذا 60 ساعة/شهر', 'amount' => 200, 'sort_order' => 1]);

        app()->setLocale('en');
        $this->assertSame('If 60h/month', $tier->fresh()->condition);

        app()->setLocale('ar');
        $this->assertSame('إذا 60 ساعة/شهر', $tier->fresh()->condition);
    }

    public function test_deleting_a_contract_cascades_to_its_pay_tiers(): void
    {
        $contract = $this->makeContract();
        $contract->payTiers()->create(['condition_en' => 'A', 'condition_ar' => 'أ', 'amount' => 100, 'sort_order' => 1]);

        $contract->delete();

        $this->assertDatabaseCount('contract_pay_tiers', 0);
    }

    private function makeContract(array $overrides = []): Contract
    {
        $city = $this->makeCity('ALP', '001');

        $center = Center::query()->create([
            'name_en' => 'Hope Center',
            'name_ar' => 'مركز الرجاء',
            'city_id' => $city->id,
            'is_active' => true,
            'weekends' => 'friday',
            'open_at' => '08:00:00',
            'close_at' => '16:00:00',
        ]);

        $department = Department::query()->forceCreate(['name' => 'Programs', 'code' => 'PRG']);
        $jobLevel = JobLevel::query()->forceCreate(['name_en' => 'Officer', 'name_ar' => 'مسؤول', 'grade' => 2]);
        $program = Program::query()->forceCreate(['name' => 'Core Program', 'slug' => 'core-program', 'code' => 'CORE', 'is_active' => true]);

        $position = Position::query()->forceCreate([
            'center_id' => $center->id,
            'city_id' => $city->id,
            'department_id' => $department->id,
            'job_level_id' => $jobLevel->id,
            'program_id' => $program->id,
            'title_en' => 'Program Officer',
            'title_ar' => 'مسؤول برنامج',
            'max_emp' => 2,
            'exist_emp' => 0,
            'is_active' => true,
        ]);

        $employee = $this->makeEmployee($city, 'schedule-pay-test-employee');
        $contractType = ContractType::query()->forceCreate(['id' => 1, 'name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع']);

        return Contract::query()->create(array_merge([
            'employee_id' => $employee->id,
            'position_id' => $position->id,
            'contract_type_id' => $contractType->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'currency' => 2,
            'salary_per' => 1,
            'weekly_hours' => 40,
            'is_active' => true,
        ], $overrides));
    }
}
```

- [x] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=ContractScheduleAndPayColumnsTest`
Expected: FAIL — the new columns are not in `Contract::$fillable`/`$casts` (mass-assignment silently drops them, so the first test's assertions fail), `Contract::payTiers()` does not exist (fatal error on the other three tests), and `App\Models\ContractPayTier` does not exist.

- [x] **Step 3: Write `ContractPayTier`**

```php
<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ContractPayTier extends Model
{
    use HasTranslation;

    protected $fillable = [
        'contract_id',
        'condition_en',
        'condition_ar',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    protected function condition(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getTranslatableAttribute('condition'),
            set: fn ($value) => $this->setTranslatableAttribute('condition', $value),
        );
    }
}
```

- [x] **Step 4: Extend `Contract`**

In `app/Models/Contract.php`, add to `$fillable` (after `'weekly_hours'`, before `'is_active'`):

```php
        'schedule_mode',
        'work_day_from',
        'work_day_to',
        'show_days_per_week',
        'show_hours_per_week',
        'flex_days_per_week',
        'flex_hours_per_week',
        'flex_hours_per_day',
        'flex_varies_by_requirements',
        'flex_coordinated_with_manager',
        'flex_custom_en',
        'flex_custom_ar',
        'pay_mode',
        'session_duration',
```

Add to `$casts` (after `'place' => 'integer',`):

```php
        'schedule_mode' => 'integer',
        'work_day_from' => 'integer',
        'work_day_to' => 'integer',
        'show_days_per_week' => 'boolean',
        'show_hours_per_week' => 'boolean',
        'flex_days_per_week' => 'integer',
        'flex_hours_per_week' => 'integer',
        'flex_hours_per_day' => 'integer',
        'flex_varies_by_requirements' => 'boolean',
        'flex_coordinated_with_manager' => 'boolean',
        'pay_mode' => 'integer',
        'session_duration' => 'integer',
```

Add the relation (near `leaveBalances()`):

```php
    public function payTiers()
    {
        return $this->hasMany(ContractPayTier::class)->orderBy('sort_order');
    }
```

- [x] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=ContractScheduleAndPayColumnsTest`
Expected: PASS (all 4 tests)

- [x] **Step 6: Run the full suite**

Run: `composer test`
Expected: PASS — no pre-existing test relied on 6 `contract_types` rows or an id outside `{1, 3, 5}` (verified during planning: `ContractCreatePerformanceTest` and `ContractRenewalTest` force-create `id => 1` and `id => 3` only; `CenterEmployeeDirectoryTest` creates its own `ContractType` rows without pinning ids).

- [x] **Step 7: Commit**

```bash
git add app/Models/Contract.php app/Models/ContractPayTier.php tests/Feature/ContractScheduleAndPayColumnsTest.php
git commit -m "feat(contracts): expose schedule/pay columns and pay tiers on the Contract model"
```

---

## Addendum: second fixed shift (added after this plan shipped)

While reviewing the finished Phase 0 work, the user asked for one more thing the original spec
didn't cover: a fixed schedule can run two periods in the same day (e.g. 9:00-13:00 and
17:00-21:00), not just one continuous block. Handled as a small follow-up migration rather than
amending Task 2's migration, since that one had already been applied to the real dev database by
the time this came up:

- `database/migrations/2026_09_15_120000_add_second_fixed_shift_to_contracts.php` — adds nullable
  `work_hours_2_from`/`work_hours_2_to` (time) to `contracts`. A contract has a second shift only
  when `work_hours_2_from` is set — no separate boolean flag, per the user's explicit choice.
- `Contract::$fillable` gained both columns (no cast — matches `work_hours_from`/`work_hours_to`,
  which also have none).
- Tests: `tests/Feature/Migrations/AddSecondFixedShiftToContractsMigrationTest.php` (schema) and
  two new methods in `tests/Feature/ContractScheduleAndPayColumnsTest.php` (mass-assignment +
  optionality).
- Commit `b679abdb`. Applied to the real dev database via `php artisan migrate --force`, verified
  green on the full suite (306 passed, the same 2 pre-existing unrelated failures as before).

Phase 3 (the schedule step UI) must render this as a second optional time-range input in fixed
mode — see the updated note in the spec's Phase 3 section.

## Explicitly out of scope for this plan

Confirmed while researching this plan, left untouched on purpose:

- `app/Livewire/Dashboard/ContractTypeEmployeesStatistics.php`'s `GROUPS` constant still lists `[1, 2]`, `[3, 4]`, `[5, 6]` — harmless after Task 1 (ids `2`, `4`, `6` just never match anything and contribute `0`), not part of the spec's Phase 0 file list. Worth a follow-up cleanup when Phase 2 or later touches contract-type display, not before.
- `ContractObserver`, `LeaveBalanceService`, `ContractForm::updatedContractTypeId()`'s `in_array($value, [3, 4])` check, and every `app/Livewire/Contract/**` file — all Phase 1+ per the spec.
