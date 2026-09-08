<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('daily_hours', 4, 2)->default(8)->after('weekly_hours');
            $table->unsignedTinyInteger('work_days_per_week')->default(5)->after('daily_hours');
            $table->string('shift_type', 20)->default('standard')->after('work_days_per_week');
            $table->boolean('is_split_shift')->default(false)->after('shift_type');
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
