<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * hc-hr's schedule columns the hub lacked: the working days picked one by
 * one ("0,1,3", Carbon numbering) and whether the contract states the
 * centre's weekend clause.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('work_weekdays', 20)->nullable()->after('work_day_to');
            $table->boolean('show_weekend_clause')->default(false)->after('show_day_range');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['work_weekdays', 'show_weekend_clause']);
        });
    }
};
