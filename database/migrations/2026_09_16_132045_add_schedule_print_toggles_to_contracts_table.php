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
        Schema::table('contracts', function (Blueprint $table) {
            // Fixed mode print toggles — same idea as show_days_per_week /
            // show_hours_per_week (see the 2026_09_15 restructure migration):
            // never edited directly, only ever checked/unchecked in the composer.
            $table->boolean('show_hours_per_day')->default(false)->after('show_hours_per_week');
            $table->boolean('show_day_range')->default(false)->after('show_hours_per_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['show_hours_per_day', 'show_day_range']);
        });
    }
};
