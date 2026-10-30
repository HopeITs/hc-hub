<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columns = [
            'leave_start_date',
            'leave_end_date',
            'leave_date',
            'leave_start_time',
            'leave_end_time',
        ];

        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('badges', $column),
        ));

        if ($existingColumns === []) {
            return;
        }

        Schema::table('badges', function (Blueprint $table) use ($existingColumns): void {
            $table->dropColumn($existingColumns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $missingColumns = [
            'leave_start_date' => ! Schema::hasColumn('badges', 'leave_start_date'),
            'leave_end_date' => ! Schema::hasColumn('badges', 'leave_end_date'),
            'leave_date' => ! Schema::hasColumn('badges', 'leave_date'),
            'leave_start_time' => ! Schema::hasColumn('badges', 'leave_start_time'),
            'leave_end_time' => ! Schema::hasColumn('badges', 'leave_end_time'),
        ];

        if (! in_array(true, $missingColumns, true)) {
            return;
        }

        Schema::table('badges', function (Blueprint $table) use ($missingColumns): void {
            if ($missingColumns['leave_start_date']) {
                $table->date('leave_start_date')->nullable();
            }

            if ($missingColumns['leave_end_date']) {
                $table->date('leave_end_date')->nullable();
            }

            if ($missingColumns['leave_date']) {
                $table->date('leave_date')->nullable();
            }

            if ($missingColumns['leave_start_time']) {
                $table->time('leave_start_time')->nullable();
            }

            if ($missingColumns['leave_end_time']) {
                $table->time('leave_end_time')->nullable();
            }
        });
    }
};
