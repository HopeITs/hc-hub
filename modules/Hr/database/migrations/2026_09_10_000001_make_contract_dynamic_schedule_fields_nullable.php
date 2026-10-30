<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('contracts')) {
            return;
        }

        $contractsTable = DB::getTablePrefix().'contracts';

        if (Schema::hasColumn('contracts', 'daily_hours')) {
            DB::statement("ALTER TABLE `{$contractsTable}` MODIFY `daily_hours` TINYINT UNSIGNED NULL DEFAULT NULL");
        }

        if (Schema::hasColumn('contracts', 'work_days_per_week')) {
            DB::statement("ALTER TABLE `{$contractsTable}` MODIFY `work_days_per_week` TINYINT UNSIGNED NULL DEFAULT NULL");
        }

        if (Schema::hasColumn('contracts', 'shift_type')) {
            DB::statement("ALTER TABLE `{$contractsTable}` MODIFY `shift_type` VARCHAR(20) NULL DEFAULT NULL");
        }

        if (Schema::hasColumn('contracts', 'is_split_shift')) {
            DB::statement("ALTER TABLE `{$contractsTable}` MODIFY `is_split_shift` TINYINT(1) NULL DEFAULT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('contracts')) {
            return;
        }

        $contractsTable = DB::getTablePrefix().'contracts';

        if (Schema::hasColumn('contracts', 'daily_hours')) {
            DB::table('contracts')->whereNull('daily_hours')->update(['daily_hours' => 8]);
            DB::statement("ALTER TABLE `{$contractsTable}` MODIFY `daily_hours` TINYINT UNSIGNED NOT NULL DEFAULT 8");
        }

        if (Schema::hasColumn('contracts', 'work_days_per_week')) {
            DB::table('contracts')->whereNull('work_days_per_week')->update(['work_days_per_week' => 5]);
            DB::statement("ALTER TABLE `{$contractsTable}` MODIFY `work_days_per_week` TINYINT UNSIGNED NOT NULL DEFAULT 5");
        }

        if (Schema::hasColumn('contracts', 'shift_type')) {
            DB::table('contracts')->whereNull('shift_type')->update(['shift_type' => 'standard']);
            DB::statement("ALTER TABLE `{$contractsTable}` MODIFY `shift_type` VARCHAR(20) NOT NULL DEFAULT 'standard'");
        }

        if (Schema::hasColumn('contracts', 'is_split_shift')) {
            DB::table('contracts')->whereNull('is_split_shift')->update(['is_split_shift' => false]);
            DB::statement("ALTER TABLE `{$contractsTable}` MODIFY `is_split_shift` TINYINT(1) NOT NULL DEFAULT 0");
        }
    }
};
