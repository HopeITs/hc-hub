<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedTinyInteger('daily_hours')->nullable()->after('weekly_hours');
            $table->unsignedTinyInteger('work_days_per_week')->nullable()->after('daily_hours');
            $table->string('shift_type', 20)->nullable()->after('work_days_per_week');
            $table->boolean('is_split_shift')->nullable()->after('shift_type');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'daily_hours',
                'work_days_per_week',
                'shift_type',
                'is_split_shift',
            ]);
        });
    }
};
