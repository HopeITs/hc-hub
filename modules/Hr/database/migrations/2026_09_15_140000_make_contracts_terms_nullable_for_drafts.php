<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A draft contract (type = Draft, created from the employee-create
     * screen with only a position/center chosen) has no schedule or pay yet
     * — start_date, end_date, weekly_hours and currency were all NOT NULL
     * with no default, which made a genuinely empty draft impossible to
     * insert. Every real (non-draft) contract already supplies all four, so
     * relaxing the constraint changes nothing for them.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dateTime('start_date')->nullable()->change();
            $table->dateTime('end_date')->nullable()->change();
            $table->integer('weekly_hours')->nullable()->change();
            $table->unsignedTinyInteger('currency')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dateTime('start_date')->nullable(false)->change();
            $table->dateTime('end_date')->nullable(false)->change();
            $table->integer('weekly_hours')->nullable(false)->change();
            $table->unsignedTinyInteger('currency')->nullable(false)->change();
        });
    }
};
