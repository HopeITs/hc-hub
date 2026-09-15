<?php

namespace Tests\Feature\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        // Schema::withoutForeignKeyConstraints() uses) is documented as a
        // no-op once a transaction is already open, so it cannot be used to
        // let this deliberately-orphaned fixture row past the FK on
        // employee_id/position_id here. `PRAGMA defer_foreign_keys`, unlike
        // `foreign_keys`, can be toggled mid-transaction: it defers FK
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
        $migration = $this->migrationInstance();

        // RefreshDatabase's initial migrate:fresh already ran this
        // migration's up(), but contract_types has no seed data of its own
        // (2025_06_20_115710_basic_tables.php only creates the empty table,
        // and this test does not opt into $seed/$seeder), so the table is
        // still empty at this point. Recreate the post-up() state — 3
        // survivor rows at their final bilingual names — that down() is
        // meant to expand back to 6 rows.
        DB::table('contract_types')->delete();
        DB::table('contract_types')->insert([
            ['id' => 1, 'name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name_en' => 'Trial Volunteer Contract', 'name_ar' => 'عقد تطوّع تجريبي', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name_en' => 'Specific Voluntary Service Contract', 'name_ar' => 'عقد تقديم خدمة تطوعية محددة', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration->down();

        $this->assertSame(6, DB::table('contract_types')->count());
        foreach ([2, 4, 6] as $droppedId) {
            $this->assertDatabaseHas('contract_types', ['id' => $droppedId]);
        }
    }

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

    private function migrationInstance(): Migration
    {
        return require database_path('migrations/2026_09_15_000000_restructure_contracts_schedule_pay_and_draft.php');
    }
}
