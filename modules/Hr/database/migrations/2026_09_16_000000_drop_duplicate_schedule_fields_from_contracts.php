<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * daily_hours/work_days_per_week/shift_type/is_split_shift and the second
 * shift's own time-range pair were a parallel, independently-built schedule
 * model — added on a separate branch around the same time as schedule_mode/
 * work_day_from/to/work_hours_2_from/to, which already cover the same
 * ground (fixed daily hours and days/week are computed from the day+hour
 * range; a second shift is work_hours_2_from/to in both fixed sub-cases).
 * Dropped in favour of that single structured model rather than keeping two
 * schedule representations in sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'daily_hours',
                'work_days_per_week',
                'shift_type',
                'is_split_shift',
                'second_shift_work_hours_from',
                'second_shift_work_hours_to',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedTinyInteger('daily_hours')->nullable()->after('weekly_hours');
            $table->unsignedTinyInteger('work_days_per_week')->nullable()->after('daily_hours');
            $table->string('shift_type', 20)->nullable()->after('work_days_per_week');
            $table->boolean('is_split_shift')->nullable()->after('shift_type');
            $table->time('second_shift_work_hours_from')->nullable()->after('work_hours_to');
            $table->time('second_shift_work_hours_to')->nullable()->after('second_shift_work_hours_from');
        });
    }
};
