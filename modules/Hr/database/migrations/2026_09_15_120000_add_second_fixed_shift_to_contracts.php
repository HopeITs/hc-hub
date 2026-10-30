<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Some contracts run a fixed schedule split into two periods in the same
     * day (e.g. 9:00-13:00 and 17:00-21:00) instead of one continuous block.
     * `work_hours_from`/`work_hours_to` (existing) stay the first shift;
     * these two nullable columns are the optional second shift — a contract
     * has one only when `work_hours_2_from` is set, no separate flag needed.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->time('work_hours_2_from')->nullable()->after('work_hours_to');
            $table->time('work_hours_2_to')->nullable()->after('work_hours_2_from');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['work_hours_2_from', 'work_hours_2_to']);
        });
    }
};
