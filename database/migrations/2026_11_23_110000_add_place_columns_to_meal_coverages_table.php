<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A MEAL officer can cover one reporting place (governorate, program and
 * center, or city-wide) instead of a whole project.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_coverages', function (Blueprint $table): void {
            $table->foreignId('program_id')->nullable()->constrained('programs')->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->cascadeOnDelete();
            $table->boolean('city_wide')->default(false);
            $table->string('scope_key', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('meal_coverages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('program_id');
            $table->dropConstrainedForeignId('center_id');
            $table->dropColumn(['city_wide', 'scope_key']);
        });
    }
};
