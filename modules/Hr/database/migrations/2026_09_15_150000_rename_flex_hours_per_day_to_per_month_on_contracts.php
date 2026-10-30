<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The flexible schedule's "hours/day" box never matched how anyone actually
 * writes a flexible clause — real contracts state a monthly hours figure
 * (teaching stipends tiered by "60 hours/month", "70 hours/month", etc.),
 * never a daily one outside of fixed mode, which already has its own time
 * range. Renamed rather than added alongside, since the feature shipped in
 * this same branch with no contracts using the old column yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->renameColumn('flex_hours_per_day', 'flex_hours_per_month');
        });

        Schema::table('contracts', function (Blueprint $table) {
            // A monthly figure can run well past unsignedTinyInteger's 255 ceiling.
            $table->unsignedSmallInteger('flex_hours_per_month')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedTinyInteger('flex_hours_per_month')->nullable()->change();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->renameColumn('flex_hours_per_month', 'flex_hours_per_day');
        });
    }
};
