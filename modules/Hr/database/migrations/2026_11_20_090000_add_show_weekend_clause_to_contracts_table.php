<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Study Zone print toggle: "Friday is considered a day off…", the day taken
 * from the centers' weekend. Off by default, so contracts printed before it
 * existed keep reading as they were signed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->boolean('show_weekend_clause')->default(false)->after('show_day_range');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropColumn('show_weekend_clause');
        });
    }
};
