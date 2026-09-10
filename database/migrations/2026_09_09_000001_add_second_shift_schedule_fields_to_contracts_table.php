<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'second_shift_work_hours_from')) {
                $table->time('second_shift_work_hours_from')->nullable()->after('work_hours_to');
            }

            if (! Schema::hasColumn('contracts', 'second_shift_work_hours_to')) {
                $table->time('second_shift_work_hours_to')->nullable()->after('second_shift_work_hours_from');
            }
        });

        DB::table('contracts')
            ->where(function ($query) {
                $query->where('shift_type', 'split')
                    ->orWhere('is_split_shift', true);
            })
            ->update([
                'second_shift_work_hours_from' => '15:00',
                'second_shift_work_hours_to' => '19:00',
            ]);

        DB::table('contracts')
            ->where(function ($query) {
                $query->where('shift_type', 'split')
                    ->orWhere('is_split_shift', true);
            })
            ->where(function ($query) {
                $query->whereNull('work_hours_from')
                    ->orWhere('work_hours_from', '09:00')
                    ->orWhere('work_hours_from', '09:00:00');
            })
            ->where(function ($query) {
                $query->whereNull('work_hours_to')
                    ->orWhere('work_hours_to', '17:00')
                    ->orWhere('work_hours_to', '17:00:00');
            })
            ->update([
                'work_hours_from' => '08:00',
                'work_hours_to' => '12:00',
            ]);

        if (Schema::hasColumn('contracts', 'daily_hours') && DB::getDriverName() === 'mysql') {
            DB::table('contracts')
                ->whereNotNull('daily_hours')
                ->update(['daily_hours' => DB::raw('ROUND(daily_hours)')]);

            $contractsTable = DB::getTablePrefix().'contracts';

            DB::statement("ALTER TABLE `{$contractsTable}` MODIFY daily_hours TINYINT UNSIGNED NULL DEFAULT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'second_shift_work_hours_from')) {
                $table->dropColumn('second_shift_work_hours_from');
            }

            if (Schema::hasColumn('contracts', 'second_shift_work_hours_to')) {
                $table->dropColumn('second_shift_work_hours_to');
            }
        });

        if (Schema::hasColumn('contracts', 'daily_hours') && DB::getDriverName() === 'mysql') {
            $contractsTable = DB::getTablePrefix().'contracts';

            DB::statement("ALTER TABLE `{$contractsTable}` MODIFY daily_hours TINYINT UNSIGNED NULL DEFAULT NULL");
        }
    }
};
