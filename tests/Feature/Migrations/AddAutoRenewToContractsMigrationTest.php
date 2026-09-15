<?php

namespace Tests\Feature\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddAutoRenewToContractsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_column_exists_and_defaults_true(): void
    {
        $this->assertTrue(Schema::hasColumn('contracts', 'auto_renew'));
    }

    public function test_up_backfills_existing_trial_category_contracts_to_false_and_leaves_others_true(): void
    {
        $migration = $this->migrationInstance();
        $migration->down();

        // See RestructureContractsScheduleAndPayMigrationTest for why this is
        // PRAGMA defer_foreign_keys rather than Schema::withoutForeignKeyConstraints():
        // the latter is a documented no-op once RefreshDatabase's per-test
        // transaction is already open.
        DB::statement('PRAGMA defer_foreign_keys = ON');
        DB::table('contracts')->insert([
            ['id' => 701, 'employee_id' => 1, 'position_id' => 1, 'contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'currency' => 2, 'salary_per' => 1, 'weekly_hours' => 40, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 702, 'employee_id' => 1, 'position_id' => 1, 'contract_type_id' => 3, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'currency' => 2, 'salary_per' => 1, 'weekly_hours' => 40, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 703, 'employee_id' => 1, 'position_id' => 1, 'contract_type_id' => 5, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'currency' => 2, 'salary_per' => 1, 'weekly_hours' => 40, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration->up();

        $this->assertDatabaseHas('contracts', ['id' => 701, 'auto_renew' => true]);
        $this->assertDatabaseHas('contracts', ['id' => 702, 'auto_renew' => false]);
        $this->assertDatabaseHas('contracts', ['id' => 703, 'auto_renew' => true]);
    }

    public function test_down_drops_the_column(): void
    {
        $migration = $this->migrationInstance();

        $migration->down();

        $this->assertFalse(Schema::hasColumn('contracts', 'auto_renew'));
    }

    private function migrationInstance(): Migration
    {
        return require database_path('migrations/2026_09_15_130000_add_auto_renew_to_contracts.php');
    }
}
